<?php

namespace App\Services\Marketing;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PostExcelImportService
{
    /**
     * Bản đồ ánh xạ tiêu đề cột nghiệp vụ (không phân biệt hoa thường, tiếng Việt không dấu/có dấu)
     */
    protected array $columnKeywords = [
        'content' => [
            'content', 'nội dung', 'noi dung', 'bài viết', 'bai viet', 'caption', 'message', 'text'
        ],
        'image_name' => [
            'tên ảnh', 'ten anh', 'tên file', 'ten file', 'image', 'photo', 'ảnh', 'anh', 'hình ảnh', 'hinh anh'
        ],
        'publish_date' => [
            'ngày đăng', 'ngay dang', 'ngày', 'ngay', 'date', 'publish_date'
        ],
        'publish_time' => [
            'giờ đăng', 'gio dang', 'giờ', 'gio', 'time', 'publish_time'
        ],
    ];

    /**
     * Giới hạn tối đa số dòng cho phép trong một file
     */
    public const MAX_ROWS = 500;

    /**
     * Phân tích và kiểm tra file Excel .xlsx 4 cột nghiệp vụ
     */
    public function parseSpreadsheet(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("Tệp Excel không tồn tại trên hệ thống.");
        }

        // Tạo Reader an toàn không thực thi macro, đọc dữ liệu thuần
        $reader = IOFactory::createReaderForFile($filePath);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(false); // Cần đọc thuộc tính kiểu dữ liệu để phân biệt Formula và Date
        }

        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        if ($highestRow < 2) {
            throw new Exception("File Excel không có dữ liệu nội dung (chỉ có dòng tiêu đề hoặc file trống).");
        }

        if ($highestRow - 1 > self::MAX_ROWS) {
            throw new Exception("File Excel vượt quá giới hạn cho phép (" . self::MAX_ROWS . " dòng). Vui lòng tách nhỏ file.");
        }

        // 1. Đọc và ánh xạ tiêu đề cột ở dòng 1
        $headerMap = $this->resolveColumnMap($worksheet, $highestColumnIndex);

        $parsedRows = [];
        $skippedEmptyCount = 0;
        $nowVietnam = Carbon::now('Asia/Ho_Chi_Minh');

        for ($row = 2; $row <= $highestRow; $row++) {
            $rawContent = $this->getRawCell($worksheet, $headerMap['content'] ?? 1, $row);
            $rawImage = $this->getRawCell($worksheet, $headerMap['image_name'] ?? 2, $row);
            $rawDate = $this->getRawCell($worksheet, $headerMap['publish_date'] ?? 3, $row);
            $rawTime = $this->getRawCell($worksheet, $headerMap['publish_time'] ?? 4, $row);

            // Bỏ qua dòng hoàn toàn trống
            if ($rawContent === null && $rawImage === null && $rawDate === null && $rawTime === null) {
                $skippedEmptyCount++;
                continue;
            }

            $errors = [];
            $warnings = [];

            // Kiểm tra công thức Excel (formula injection)
            if ($this->isFormulaCell($worksheet, $headerMap['content'] ?? 1, $row) ||
                $this->isFormulaCell($worksheet, $headerMap['image_name'] ?? 2, $row) ||
                $this->isFormulaCell($worksheet, $headerMap['publish_date'] ?? 3, $row) ||
                $this->isFormulaCell($worksheet, $headerMap['publish_time'] ?? 4, $row)
            ) {
                $errors[] = "Dòng chứa ô công thức Excel (=). Vui lòng chuyển thành giá trị văn bản thuần (Paste Special > Values).";
            }

            // 1. Kiểm tra Cột Content
            $contentStr = trim((string) ($rawContent ?? ''));
            if (empty($contentStr)) {
                $errors[] = "Thiếu nội dung bài viết (cột Content).";
            }

            // 2. Kiểm tra Cột Tên ảnh
            $imageName = trim((string) ($rawImage ?? ''));
            if (empty($imageName)) {
                $errors[] = "Thiếu tên file ảnh (cột Tên ảnh).";
            } else {
                // Kiểm tra đuôi mở rộng hợp lệ
                $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $errors[] = "Tên ảnh \"{$imageName}\" phải có phần mở rộng hợp lệ (.jpg, .png, .webp).";
                }
            }

            // 3 & 4. Kiểm tra và chuẩn hóa Ngày/Giờ đăng
            $scheduledAt = null;
            $scheduledAtIso = null;
            $displayDateTime = null;

            $dateParseResult = $this->parseScheduleDateTime($worksheet, $headerMap['publish_date'] ?? 3, $headerMap['publish_time'] ?? 4, $row);

            if (!$dateParseResult['success']) {
                $errors[] = $dateParseResult['message'];
            } else {
                $scheduledAtCarbon = $dateParseResult['datetime']; // Carbon instance timezone Asia/Ho_Chi_Minh
                $displayDateTime = $scheduledAtCarbon->format('d/m/Y H:i');

                // Kiểm tra nếu giờ đã qua trong quá khứ
                if ($scheduledAtCarbon->lessThanOrEqualTo($nowVietnam)) {
                    $errors[] = "Thời gian đăng ({$displayDateTime}) đã trôi qua. Không được phép lên lịch trong quá khứ.";
                } else {
                    $scheduledAt = $scheduledAtCarbon->clone()->utc()->format('Y-m-d H:i:s');
                    $scheduledAtIso = $scheduledAtCarbon->toIso8601String();
                }
            }

            // Đề xuất tiêu đề Website bằng quy tắc xác định (Không dùng AI)
            $suggestedTitle = !empty($contentStr) ? self::suggestTitle($contentStr) : '';

            $parsedRows[] = [
                'row_number' => $row,
                'content' => $contentStr,
                'content_preview' => mb_strlen($contentStr, 'UTF-8') > 150 ? mb_substr($contentStr, 0, 150, 'UTF-8') . '...' : $contentStr,
                'title_suggested' => $suggestedTitle,
                'title' => $suggestedTitle,
                'image_name' => $imageName,
                'scheduled_display' => $displayDateTime,
                'scheduled_at' => $scheduledAt,
                'scheduled_at_iso' => $scheduledAtIso,
                'status' => count($errors) === 0 ? 'valid' : 'error',
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        return [
            'total_rows_read' => count($parsedRows),
            'valid_rows_count' => count(array_filter($parsedRows, fn($r) => $r['status'] === 'valid')),
            'error_rows_count' => count(array_filter($parsedRows, fn($r) => $r['status'] === 'error')),
            'skipped_empty_rows' => $skippedEmptyCount,
            'rows' => $parsedRows,
        ];
    }

    /**
     * Xác định chỉ số cột từ dòng tiêu đề (Dòng 1)
     */
    protected function resolveColumnMap($worksheet, int $highestColIndex): array
    {
        $map = [];

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $cellValue = $worksheet->getCell([$col, 1])->getValue();
            if ($cellValue === null) continue;

            $header = mb_strtolower(trim((string)$cellValue), 'UTF-8');

            foreach ($this->columnKeywords as $targetField => $keywords) {
                if (!isset($map[$targetField])) {
                    foreach ($keywords as $kw) {
                        if (mb_strpos($header, $kw) !== false) {
                            $map[$targetField] = $col;
                            break 2;
                        }
                    }
                }
            }
        }

        // Mặc định fallback nếu người dùng đặt theo thứ tự 4 cột: 1=content, 2=image, 3=date, 4=time
        if (!isset($map['content'])) $map['content'] = 1;
        if (!isset($map['image_name'])) $map['image_name'] = 2;
        if (!isset($map['publish_date'])) $map['publish_date'] = 3;
        if (!isset($map['publish_time'])) $map['publish_time'] = 4;

        return $map;
    }

    /**
     * Lấy giá trị ô an toàn
     */
    protected function getRawCell($worksheet, int $col, int $row)
    {
        $cell = $worksheet->getCell([$col, $row]);
        $val = $cell->getValue();
        return $val !== null ? trim((string)$val) : null;
    }

    /**
     * Kiểm tra ô có chứa công thức hay không
     */
    protected function isFormulaCell($worksheet, int $col, int $row): bool
    {
        $cell = $worksheet->getCell([$col, $row]);
        if ($cell->isFormula()) {
            return true;
        }
        $val = (string) $cell->getValue();
        return str_starts_with($val, '=');
    }

    /**
     * Parse Ngày và Giờ đăng từ ô Excel (hỗ trợ cả Excel Serial Date và chuỗi dd/MM/yyyy)
     */
    protected function parseScheduleDateTime($worksheet, int $dateCol, int $timeCol, int $row): array
    {
        $dateCell = $worksheet->getCell([$dateCol, $row]);
        $timeCell = $worksheet->getCell([$timeCol, $row]);

        $dateVal = $dateCell->getValue();
        $timeVal = $timeCell->getValue();

        if (empty($dateVal)) {
            return ['success' => false, 'message' => 'Thiếu ngày đăng (cột Ngày).'];
        }

        if (empty($timeVal)) {
            return ['success' => false, 'message' => 'Thiếu giờ đăng (cột Giờ).'];
        }

        // 1. Phân tích Ngày (Date)
        $parsedDate = null;

        // Nếu là số thực theo hệ ngày Excel (1900/1904)
        if (is_numeric($dateVal) && ExcelDate::isDateTime($dateCell)) {
            try {
                $phpDateTime = ExcelDate::excelToDateTimeObject($dateVal);
                $parsedDate = Carbon::instance($phpDateTime)->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d');
            } catch (Exception $e) {
                // Fallback
            }
        }

        if (!$parsedDate) {
            $dateStr = trim((string) $dateVal);
            // Hỗ trợ dd/MM/yyyy hoặc dd-MM-yyyy
            if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $dateStr, $m)) {
                $d = (int) $m[1];
                $mo = (int) $m[2];
                $y = (int) $m[3];

                if (!checkdate($mo, $d, $y)) {
                    return ['success' => false, 'message' => "Ngày đăng không hợp lệ trên lịch: {$dateStr}"];
                }
                $parsedDate = sprintf('%04d-%02d-%02d', $y, $mo, $d);
            } elseif (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $dateStr, $m)) {
                $y = (int) $m[1];
                $mo = (int) $m[2];
                $d = (int) $m[3];
                if (!checkdate($mo, $d, $y)) {
                    return ['success' => false, 'message' => "Ngày đăng không hợp lệ: {$dateStr}"];
                }
                $parsedDate = sprintf('%04d-%02d-%02d', $y, $mo, $d);
            } else {
                return ['success' => false, 'message' => "Định dạng ngày không đúng. Vui lòng nhập đúng chuẩn dd/MM/yyyy (ví dụ: 25/10/2026)."];
            }
        }

        // 2. Phân tích Giờ (Time)
        $parsedTime = null;

        if (is_numeric($timeVal) && ExcelDate::isDateTime($timeCell)) {
            try {
                $phpDateTime = ExcelDate::excelToDateTimeObject($timeVal);
                $parsedTime = Carbon::instance($phpDateTime)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i:00');
            } catch (Exception $e) {
                // Fallback
            }
        }

        if (!$parsedTime) {
            $timeStr = trim((string) $timeVal);
            if (preg_match('/^(\d{1,2}):(\d{2})(:(\d{2}))?$/', $timeStr, $m)) {
                $h = (int) $m[1];
                $min = (int) $m[2];
                $sec = isset($m[4]) ? (int) $m[4] : 0;

                if ($h < 0 || $h > 23 || $min < 0 || $min > 59 || $sec < 0 || $sec > 59) {
                    return ['success' => false, 'message' => "Giờ đăng không hợp lệ ({$timeStr}). Giờ từ 00 đến 23, phút từ 00 đến 59."];
                }
                $parsedTime = sprintf('%02d:%02d:%02d', $h, $min, $sec);
            } else {
                return ['success' => false, 'message' => "Định dạng giờ không đúng. Vui lòng nhập đúng chuẩn HH:mm (ví dụ: 08:30)."];
            }
        }

        try {
            $carbonInstance = Carbon::createFromFormat('Y-m-d H:i:s', "{$parsedDate} {$parsedTime}", 'Asia/Ho_Chi_Minh');
            return [
                'success' => true,
                'datetime' => $carbonInstance,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Lỗi ghép thời gian ngày giờ đăng: " . $e->getMessage()];
        }
    }

    /**
     * Đề xuất tiêu đề bài viết Website từ câu đầu tiên (100% Deterministic, KHÔNG dùng AI)
     */
    public static function suggestTitle(string $content): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $firstLine = trim($lines[0] ?? '');

        // Loại bỏ ký tự đặc biệt đầu câu như bullet, gạch đầu dòng
        $firstLine = preg_replace('/^[\-\*\•\#\>\s]+/', '', $firstLine);

        // Tìm câu kết thúc trước dấu chấm, chấm than hoặc chấm hỏi
        if (preg_match('/^([^.!?\n\r]+[.!?])/u', $firstLine, $matches)) {
            $candidate = trim($matches[1]);
            $len = mb_strlen($candidate, 'UTF-8');
            if ($len >= 10 && $len <= 100) {
                return rtrim($candidate, '.!? ');
            }
        }

        // Nếu dòng đầu quá dài, cắt tối đa 80 ký tự theo từ nguyên vẹn
        if (mb_strlen($firstLine, 'UTF-8') > 80) {
            $truncated = mb_substr($firstLine, 0, 80, 'UTF-8');
            $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
            if ($lastSpace !== false && $lastSpace > 25) {
                return mb_substr($truncated, 0, $lastSpace, 'UTF-8');
            }
            return $truncated;
        }

        return $firstLine ?: 'Bài viết xe điện VinFast';
    }
}
