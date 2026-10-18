<?php

namespace App\Services\Booking;

use App\Exceptions\CarBookingValidationException;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingHandoverRecord;
use App\Models\BookingSurcharge;
use App\Models\BookingTransaction;
use App\Models\Car;
use App\Models\User;
use App\Services\Payment\BookingPaymentService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingHandoverService
{
    // Quy tắc cấu hình đơn giá phụ phí thực tế (Configured Surcharge Rules - VND integer)
    public const LATE_FEE_HOURLY            = 100000; // 100.000đ / mỗi giờ trả xe trễ
    public const LATE_FEE_DAILY_CAP         = 600000; // Tối đa 600.000đ / ngày trễ
    public const EXCESS_KM_FEE_PER_KM       = 3000;   // 3.000đ / mỗi km vượt hạn mức
    public const DAILY_KM_LIMIT             = 350;    // Hạn mức 350 km / ngày thuê
    public const CLEANING_FEE_STANDARD      = 100000; // Vệ sinh ngoại thất tiêu chuẩn
    public const CLEANING_FEE_HEAVY         = 300000; // Vệ sinh khoang nội thất bẩn nặng / có mùi
    public const FUEL_ELECTRIC_PER_PERCENT  = 5000;   // 5.000đ / 1% pin thiếu hụt so với lúc bàn giao
    public const FUEL_GASOLINE_PER_PERCENT  = 15000;  // 15.000đ / 1% xăng thiếu hụt (~30.000đ/lít)

    // Giới hạn tệp ảnh tải lên
    public const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    public const MAX_IMAGE_SIZE_KB        = 5120; // 5MB

    protected BookingLifecycleService $lifecycleService;
    protected BookingPaymentService $paymentService;

    public function __construct(
        BookingLifecycleService $lifecycleService,
        BookingPaymentService $paymentService
    ) {
        $this->lifecycleService = $lifecycleService;
        $this->paymentService   = $paymentService;
    }

    /**
     * Tạo biên bản bàn giao xe cho khách hàng (Delivery Handover Record)
     * Chỉ thực hiện khi đơn ở trạng thái STATUS_CONFIRMED (1 - Đã duyệt)
     *
     * @param Booking $booking
     * @param Admin $staff
     * @param array $data
     * @param array<UploadedFile> $images
     * @return BookingHandoverRecord
     * @throws CarBookingValidationException
     */
    public function createDeliveryRecord(Booking $booking, Admin $staff, array $data, array $images = []): BookingHandoverRecord
    {
        return DB::transaction(function () use ($booking, $staff, $data, $images) {
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();
            if (!$lockedBooking) {
                throw new CarBookingValidationException('Không tìm thấy đơn đặt xe!', 404);
            }

            // 1. Kiểm tra trạng thái đơn hợp lệ
            if ((int) $lockedBooking->tinh_trang !== Booking::STATUS_CONFIRMED) {
                throw new CarBookingValidationException(
                    "Chỉ có thể bàn giao xe khi đơn ở trạng thái 'Đã duyệt' (tinh_trang = 1). Trạng thái hiện tại: " .
                    $this->lifecycleService->getStatusMeta($lockedBooking->tinh_trang)['label'],
                    422
                );
            }

            // 2. Chặn thao tác trùng lặp (Idempotency)
            $existingDelivery = BookingHandoverRecord::where('booking_id', $lockedBooking->id)
                ->where('type', BookingHandoverRecord::TYPE_DELIVERY)
                ->first();
            if ($existingDelivery) {
                throw new CarBookingValidationException('Đơn đặt xe này đã có biên bản bàn giao xe ban đầu!', 422);
            }

            // 3. Kiểm tra số km bàn giao
            $currentKm = (int) ($data['current_km'] ?? 0);
            if ($currentKm < 0) {
                throw new CarBookingValidationException('Số km đồng hồ táp-lô lúc bàn giao không được âm!', 422);
            }

            // 4. Kiểm tra mức pin / nhiên liệu
            $fuelOrBatteryLevel = (int) ($data['fuel_or_battery_level'] ?? 100);
            if ($fuelOrBatteryLevel < 0 || $fuelOrBatteryLevel > 100) {
                throw new CarBookingValidationException('Mức pin hoặc nhiên liệu phải nằm trong khoảng từ 0% đến 100%!', 422);
            }

            // 5. Xác định loại nhiên liệu từ xe
            $car = Car::find($lockedBooking->car_id);
            $fuelType = $data['fuel_type'] ?? ($car->fuel_type ?? BookingHandoverRecord::FUEL_ELECTRIC);

            // 6. Xử lý lưu ảnh an toàn vào thư mục riêng tư
            $savedImageFilenames = $this->storePrivateImages($lockedBooking->id, 'delivery', $images);

            // 7. Tạo biên bản bàn giao
            $handoverTime = !empty($data['handover_time']) 
                ? Carbon::parse($data['handover_time']) 
                : Carbon::now();

            $record = BookingHandoverRecord::create([
                'booking_id'                   => $lockedBooking->id,
                'type'                         => BookingHandoverRecord::TYPE_DELIVERY,
                'handover_time'                => $handoverTime,
                'staff_id'                     => $staff->id,
                'current_km'                   => $currentKm,
                'fuel_type'                    => $fuelType,
                'fuel_or_battery_level'        => $fuelOrBatteryLevel,
                'exterior_status'              => $data['exterior_status'] ?? 'Xe nguyên vẹn, không trầy xước đáng kể.',
                'interior_status'              => $data['interior_status'] ?? 'Nội thất sạch sẽ, các thiết bị hoạt động tốt.',
                'checklist_items'              => $data['checklist_items'] ?? $this->getDefaultChecklist($fuelType),
                'notes'                        => $data['notes'] ?? null,
                'images'                       => $savedImageFilenames,
                'is_odometer_adjusted'         => false,
                'customer_confirmed'           => !empty($data['customer_confirmed']),
                'customer_confirmed_at'        => !empty($data['customer_confirmed']) ? Carbon::now() : null,
                'customer_confirmation_method' => !empty($data['customer_confirmed']) 
                    ? ($data['customer_confirmation_method'] ?? BookingHandoverRecord::CONFIRM_DIRECT) 
                    : null,
                'customer_notes'               => $data['customer_notes'] ?? null,
            ]);

            // 8. Cập nhật số km của xe nếu cần
            if ($car && ($car->current_km === null || $car->current_km < $currentKm)) {
                $car->current_km = $currentKm;
                $car->save();
            }

            // 9. Chuyển trạng thái đơn sang STATUS_IN_PROGRESS (3 - Đang thuê / Đã giao xe)
            $this->lifecycleService->transition(
                $lockedBooking, 
                Booking::STATUS_IN_PROGRESS, 
                $staff, 
                ['status_note' => "Đã lập Biên bản bàn giao xe #{$record->id} lúc " . $handoverTime->format('d/m/Y H:i')]
            );

            return $record;
        });
    }

    /**
     * Tạo biên bản nhận xe trả từ khách hàng (Return Handover Record)
     * Chỉ thực hiện khi đơn ở trạng thái STATUS_IN_PROGRESS (3 - Đang thuê)
     *
     * @param Booking $booking
     * @param Admin $staff
     * @param array $data
     * @param array<UploadedFile> $images
     * @param array $surcharges Mảng phụ phí cấu hình
     * @return BookingHandoverRecord
     * @throws CarBookingValidationException
     */
    public function createReturnRecord(Booking $booking, Admin $staff, array $data, array $images = [], array $surcharges = []): BookingHandoverRecord
    {
        return DB::transaction(function () use ($booking, $staff, $data, $images, $surcharges) {
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();
            if (!$lockedBooking) {
                throw new CarBookingValidationException('Không tìm thấy đơn đặt xe!', 404);
            }

            // 1. Kiểm tra trạng thái đơn
            if ((int) $lockedBooking->tinh_trang !== Booking::STATUS_IN_PROGRESS) {
                throw new CarBookingValidationException(
                    "Chỉ có thể nhận trả xe khi đơn ở trạng thái 'Đang thuê / Đã giao xe' (tinh_trang = 3). Trạng thái hiện tại: " .
                    $this->lifecycleService->getStatusMeta($lockedBooking->tinh_trang)['label'],
                    422
                );
            }

            // 2. Kiểm tra biên bản giao xe ban đầu
            $deliveryRecord = BookingHandoverRecord::where('booking_id', $lockedBooking->id)
                ->where('type', BookingHandoverRecord::TYPE_DELIVERY)
                ->first();
            if (!$deliveryRecord) {
                throw new CarBookingValidationException('Không tìm thấy biên bản bàn giao xe ban đầu để đối chiếu!', 422);
            }

            // 3. Chặn tạo trùng lặp biên bản nhận trả
            $existingReturn = BookingHandoverRecord::where('booking_id', $lockedBooking->id)
                ->where('type', BookingHandoverRecord::TYPE_RETURN)
                ->first();
            if ($existingReturn) {
                throw new CarBookingValidationException('Đơn đặt xe này đã có biên bản nhận trả xe rồi!', 422);
            }

            // 4. Kiểm tra số km nhận trả
            $deliveryKm = (int) $deliveryRecord->current_km;
            $returnKm   = (int) ($data['current_km'] ?? 0);
            $isAdjusted = !empty($data['is_odometer_adjusted']);
            $adjustReason = trim($data['odometer_adjustment_reason'] ?? '');

            if ($returnKm < $deliveryKm) {
                if (!$isAdjusted || empty($adjustReason)) {
                    throw new CarBookingValidationException(
                        "Số km nhận trả ({$returnKm} km) không được nhỏ hơn số km lúc bàn giao ({$deliveryKm} km). " .
                        "Nếu do thay thế đồng hồ táp-lô hoặc bảo trì sự cố, bắt buộc phải kích hoạt quy trình điều chỉnh và ghi rõ lý do!",
                        422
                    );
                }
            }

            // 5. Kiểm tra mức pin / nhiên liệu
            $fuelOrBatteryLevel = (int) ($data['fuel_or_battery_level'] ?? 100);
            if ($fuelOrBatteryLevel < 0 || $fuelOrBatteryLevel > 100) {
                throw new CarBookingValidationException('Mức pin hoặc nhiên liệu phải nằm trong khoảng từ 0% đến 100%!', 422);
            }

            // 6. Lưu ảnh chứng từ lúc nhận trả an toàn
            $savedImageFilenames = $this->storePrivateImages($lockedBooking->id, 'return', $images);

            // 7. Tạo biên bản nhận trả xe
            $handoverTime = !empty($data['handover_time']) 
                ? Carbon::parse($data['handover_time']) 
                : Carbon::now();

            $record = BookingHandoverRecord::create([
                'booking_id'                   => $lockedBooking->id,
                'type'                         => BookingHandoverRecord::TYPE_RETURN,
                'handover_time'                => $handoverTime,
                'staff_id'                     => $staff->id,
                'current_km'                   => $returnKm,
                'fuel_type'                    => $deliveryRecord->fuel_type,
                'fuel_or_battery_level'        => $fuelOrBatteryLevel,
                'exterior_status'              => $data['exterior_status'] ?? 'Ngoại thất bình thường.',
                'interior_status'              => $data['interior_status'] ?? 'Nội thất bình thường.',
                'checklist_items'              => $data['checklist_items'] ?? $deliveryRecord->checklist_items,
                'notes'                        => $data['notes'] ?? null,
                'images'                       => $savedImageFilenames,
                'is_odometer_adjusted'         => $isAdjusted,
                'odometer_adjustment_reason'   => $isAdjusted ? $adjustReason : null,
                'customer_confirmed'           => !empty($data['customer_confirmed']),
                'customer_confirmed_at'        => !empty($data['customer_confirmed']) ? Carbon::now() : null,
                'customer_confirmation_method' => !empty($data['customer_confirmed']) 
                    ? ($data['customer_confirmation_method'] ?? BookingHandoverRecord::CONFIRM_DIRECT) 
                    : null,
                'customer_notes'               => $data['customer_notes'] ?? null,
            ]);

            // 8. Cập nhật số km hiện tại của xe trong đội xe
            $car = Car::find($lockedBooking->car_id);
            if ($car) {
                $car->current_km = $returnKm;
                $car->save();
            }

            // 9. Xử lý các phụ phí phát sinh (Surcharges) theo quy tắc thực tế
            $this->processSurcharges($lockedBooking, $record, $staff, $surcharges);

            // 10. Chuyển trạng thái đơn sang STATUS_RETURNED (4 - Đã nhận trả xe)
            $kmDiff = max(0, $returnKm - $deliveryKm);
            $this->lifecycleService->transition(
                $lockedBooking,
                Booking::STATUS_RETURNED,
                $staff,
                ['status_note' => "Đã lập Biên bản nhận trả xe #{$record->id} lúc " . $handoverTime->format('d/m/Y H:i') . " (Quãng đường: {$kmDiff} km)."]
            );

            return $record;
        });
    }

    /**
     * Ghi nhận xác nhận của khách hàng đối với biên bản giao hoặc nhận trả
     *
     * @param Booking $booking
     * @param User $user
     * @param string $type 'delivery' | 'return'
     * @param array $params ['notes' => string, 'method' => string]
     * @return BookingHandoverRecord
     * @throws CarBookingValidationException
     */
    public function confirmByCustomer(Booking $booking, User $user, string $type, array $params = []): BookingHandoverRecord
    {
        if ((int) $booking->user_id !== (int) $user->id) {
            throw new CarBookingValidationException('Bạn không có quyền xác nhận biên bản của đơn đặt xe khác!', 403);
        }

        $record = BookingHandoverRecord::where('booking_id', $booking->id)
            ->where('type', $type)
            ->first();

        if (!$record) {
            throw new CarBookingValidationException("Không tìm thấy biên bản {$type} để xác nhận!", 404);
        }

        $record->customer_confirmed           = true;
        $record->customer_confirmed_at        = Carbon::now();
        $record->customer_confirmation_method = $params['method'] ?? BookingHandoverRecord::CONFIRM_ACCOUNT;
        $record->customer_notes               = $params['notes'] ?? $record->customer_notes;
        $record->save();

        return $record;
    }

    /**
     * Lấy báo cáo đối chiếu chi tiết Trước / Sau (Comparison Diff) giữa bàn giao và nhận trả
     *
     * @param Booking $booking
     * @return array
     */
    public function getHandoverComparison(Booking $booking): array
    {
        $delivery = $booking->deliveryRecord()->with('staff:id,ho_ten')->first();
        $return   = $booking->returnRecord()->with('staff:id,ho_ten')->first();
        $surcharges = $booking->surcharges()->with('creator:id,ho_ten')->get();

        $diff = null;
        if ($delivery) {
            $currentReturnTime = $return ? $return->handover_time : Carbon::now();
            $expectedEndTime   = Carbon::parse($booking->end_date);

            $kmDriven = $return ? max(0, $return->current_km - $delivery->current_km) : null;
            $fuelDiff = $return ? ($return->fuel_or_battery_level - $delivery->fuel_or_battery_level) : null;

            // Tính số giờ trễ hạn hợp đồng
            $lateHours = 0;
            if ($currentReturnTime->gt($expectedEndTime)) {
                $lateHours = (int) ceil($expectedEndTime->diffInMinutes($currentReturnTime) / 60);
            }

            // Gợi ý tính toán phụ phí theo bảng giá thực tế đã cấu hình
            $suggested = $this->calculateSuggestedSurcharges($booking, [
                'delivery_km'    => $delivery->current_km,
                'return_km'      => $return ? $return->current_km : null,
                'delivery_level' => $delivery->fuel_or_battery_level,
                'return_level'   => $return ? $return->fuel_or_battery_level : null,
                'fuel_type'      => $delivery->fuel_type,
                'late_hours'     => $lateHours,
            ]);

            $diff = [
                'km_driven'            => $kmDriven,
                'fuel_or_battery_diff' => $fuelDiff,
                'late_hours'           => $lateHours,
                'is_late'              => $lateHours > 0,
                'suggested_surcharges' => $suggested,
            ];
        }

        return [
            'booking_id'    => $booking->id,
            'ma_dat_xe'     => $booking->ma_dat_xe,
            'car_name'      => $booking->car?->name,
            'delivery'      => $delivery ? $this->formatHandoverRecord($delivery) : null,
            'return'        => $return ? $this->formatHandoverRecord($return) : null,
            'diff'          => $diff,
            'surcharges'    => $surcharges,
            'total_surcharge' => (int) $surcharges->sum('amount'),
            'disclaimer'    => 'Biên bản ghi nhận hiện trạng phương tiện tại thời điểm bàn giao/nhận trả giữa hai bên, phục vụ đối soát dịch vụ thuê xe DriveRent; không thay thế cho văn bản chứng thực pháp lý cơ quan nhà nước.',
        ];
    }

    /**
     * Tính toán các phụ phí gợi ý dựa trên quy tắc thực tế cấu hình
     */
    public function calculateSuggestedSurcharges(Booking $booking, array $metrics): array
    {
        $suggestions = [];

        // 1. Phụ phí trễ giờ
        $lateHours = (int) ($metrics['late_hours'] ?? 0);
        if ($lateHours > 0) {
            $lateAmount = min($lateHours * self::LATE_FEE_HOURLY, self::LATE_FEE_DAILY_CAP);
            $suggestions[] = [
                'type'   => BookingSurcharge::TYPE_LATE_RETURN,
                'amount' => $lateAmount,
                'reason' => "Trả xe trễ {$lateHours} giờ so với thời hạn hợp đồng (Đơn giá: " . number_format(self::LATE_FEE_HOURLY) . "đ/giờ).",
            ];
        }

        // 2. Phụ phí hao hụt pin hoặc xăng
        $deliveryLevel = (int) ($metrics['delivery_level'] ?? 100);
        $returnLevel   = isset($metrics['return_level']) ? (int) $metrics['return_level'] : null;
        $fuelType      = $metrics['fuel_type'] ?? BookingHandoverRecord::FUEL_ELECTRIC;

        if ($returnLevel !== null && $returnLevel < $deliveryLevel) {
            $dropPercent = $deliveryLevel - $returnLevel;
            $unitRate = ($fuelType === BookingHandoverRecord::FUEL_ELECTRIC) 
                ? self::FUEL_ELECTRIC_PER_PERCENT 
                : self::FUEL_GASOLINE_PER_PERCENT;
            $amount = $dropPercent * $unitRate;

            $fuelName = ($fuelType === BookingHandoverRecord::FUEL_ELECTRIC) ? 'pin điện' : 'nhiên liệu xăng';
            $suggestions[] = [
                'type'   => BookingSurcharge::TYPE_FUEL_DIFFERENCE,
                'amount' => $amount,
                'reason' => "Mức {$fuelName} lúc nhận ({$returnLevel}%) thấp hơn lúc giao ({$deliveryLevel}%). Thiếu hụt {$dropPercent}% (Đơn giá bù sạc: " . number_format($unitRate) . "đ/%).",
            ];
        }

        // 3. Phụ phí vượt km hợp đồng (nếu có hạn mức)
        $deliveryKm = (int) ($metrics['delivery_km'] ?? 0);
        $returnKm   = isset($metrics['return_km']) ? (int) $metrics['return_km'] : null;
        if ($returnKm !== null && $returnKm > $deliveryKm) {
            $kmDriven = $returnKm - $deliveryKm;
            $days = max(1, (int) ceil(Carbon::parse($booking->start_date)->diffInDays(Carbon::parse($booking->end_date))));
            $allowedKm = $days * self::DAILY_KM_LIMIT;

            if ($kmDriven > $allowedKm) {
                $excessKm = $kmDriven - $allowedKm;
                $suggestions[] = [
                    'type'   => BookingSurcharge::TYPE_EXCESS_KM,
                    'amount' => $excessKm * self::EXCESS_KM_FEE_PER_KM,
                    'reason' => "Vượt hạn mức {$excessKm} km (Tổng chạy: {$kmDriven} km / Hạn mức {$days} ngày: {$allowedKm} km, đơn giá: " . number_format(self::EXCESS_KM_FEE_PER_KM) . "đ/km).",
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Xử lý lưu các phụ phí và liên kết khấu trừ tiền cọc bảo đảm
     */
    protected function processSurcharges(Booking $booking, BookingHandoverRecord $record, Admin $staff, array $surcharges): void
    {
        if (empty($surcharges)) {
            return;
        }

        foreach ($surcharges as $sc) {
            $type   = $sc['type'] ?? BookingSurcharge::TYPE_OTHER;
            $amount = (int) ($sc['amount'] ?? 0);
            $reason = trim($sc['reason'] ?? '');
            $deductDeposit = !empty($sc['is_deducted_from_deposit']);

            if ($amount <= 0) {
                continue; // Bỏ qua nếu số tiền không hợp lệ
            }

            if (empty($reason)) {
                throw new CarBookingValidationException("Phụ phí loại '{$type}' bắt buộc phải nêu rõ lý do cụ thể!", 422);
            }

            // Lưu phụ phí
            $surcharge = BookingSurcharge::create([
                'booking_id'               => $booking->id,
                'handover_record_id'       => $record->id,
                'type'                     => $type,
                'amount'                   => $amount,
                'reason'                   => $reason,
                'evidence_images'          => $sc['evidence_images'] ?? [],
                'is_deducted_from_deposit' => $deductDeposit,
                'created_by'               => $staff->id,
            ]);

            // Nếu admin chỉ định khấu trừ vào tiền cọc đang giữ
            if ($deductDeposit) {
                $txResult = $this->paymentService->recordTransaction($booking, [
                    'type'             => BookingTransaction::TYPE_DEPOSIT_DEDUCTION,
                    'amount'           => $amount,
                    'method'           => 'other',
                    'reference_code'   => 'SC-' . $surcharge->id,
                    'note'             => "Khấu trừ phụ phí: {$surcharge->type_label}",
                    'deduction_reason' => $reason,
                    'idempotency_key'  => 'SURCHARGE_DEDUCT_' . $surcharge->id,
                ], $staff);

                $surcharge->transaction_id = $txResult['transaction']->id ?? null;
                $surcharge->save();
            }
        }
    }

    /**
     * Lưu ảnh vào thư mục riêng tư storage/app/private/handovers/{booking_id}/
     */
    public function storePrivateImages(int $bookingId, string $prefix, array $files): array
    {
        $savedFilenames = [];
        $privateDir = storage_path("app/private/handovers/{$bookingId}");

        if (!File::exists($privateDir)) {
            File::makeDirectory($privateDir, 0755, true);
        }

        foreach ($files as $file) {
            if (!($file instanceof UploadedFile) || !$file->isValid()) {
                continue;
            }

            $ext = strtolower($file->guessExtension() ?: 'jpg');
            if (!in_array($ext, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
                throw new CarBookingValidationException("Định dạng ảnh không được hỗ trợ: .{$ext}. Chỉ chấp nhận jpg, jpeg, png, webp!", 422);
            }

            if ($file->getSize() > (self::MAX_IMAGE_SIZE_KB * 1024)) {
                throw new CarBookingValidationException("Kích thước tệp ảnh vượt quá " . (self::MAX_IMAGE_SIZE_KB / 1024) . "MB!", 422);
            }

            $filename = "{$prefix}_" . time() . '_' . Str::random(12) . ".{$ext}";
            $file->move($privateDir, $filename);
            $savedFilenames[] = $filename;
        }

        return $savedFilenames;
    }

    /**
     * Lấy đường dẫn file ảnh riêng tư an toàn
     */
    public function getPrivateImagePath(int $bookingId, string $filename): ?string
    {
        $cleanFilename = basename($filename);
        $path = storage_path("app/private/handovers/{$bookingId}/{$cleanFilename}");

        return File::exists($path) ? $path : null;
    }

    /**
     * Checklist mẫu mặc định phù hợp loại xe
     */
    public function getDefaultChecklist(string $fuelType): array
    {
        $isElectric = $fuelType === BookingHandoverRecord::FUEL_ELECTRIC;

        return [
            ['id' => 'papers', 'name' => 'Giấy tờ xe (Đăng ký, Đăng kiểm, Bảo hiểm TNDS)', 'checked' => true],
            ['id' => 'keys', 'name' => 'Chìa khóa thông minh / Thẻ từ (01 chiếc)', 'checked' => true],
            ['id' => 'charger', 'name' => $isElectric ? 'Bộ sạc di động chính hãng (2.2kW/3.5kW)' : 'Nắp bình nhiên liệu an toàn', 'checked' => true],
            ['id' => 'spare_tire', 'name' => 'Lốp dự phòng / Bộ keo vá lốp khẩn cấp', 'checked' => true],
            ['id' => 'jack', 'name' => 'Kích nâng và tay vặn ốc', 'checked' => true],
            ['id' => 'warning_triangle', 'name' => 'Tam giác phản quang cảnh báo sự cố', 'checked' => true],
            ['id' => 'camera', 'name' => 'Camera hành trình hoạt động tốt', 'checked' => true],
            ['id' => 'cleanliness', 'name' => 'Xe sạch sẽ, không có mùi lạ', 'checked' => true],
        ];
    }

    /**
     * Định dạng dữ liệu biên bản kèm URL xem ảnh an toàn
     */
    protected function formatHandoverRecord(BookingHandoverRecord $record): array
    {
        $imagesWithUrls = [];
        foreach ($record->images ?? [] as $img) {
            $imagesWithUrls[] = [
                'filename' => $img,
                'url'      => url("/api/bookings/{$record->booking_id}/handovers/images/{$img}"),
            ];
        }

        return [
            'id'                           => $record->id,
            'booking_id'                   => $record->booking_id,
            'type'                         => $record->type,
            'type_label'                   => $record->type_label,
            'type_badge_color'             => $record->type_badge_color,
            'handover_time'                => $record->handover_time,
            'staff'                        => $record->staff ? ['id' => $record->staff->id, 'ho_ten' => $record->staff->ho_ten] : null,
            'current_km'                   => $record->current_km,
            'fuel_type'                    => $record->fuel_type,
            'fuel_label'                   => $record->fuel_label,
            'fuel_or_battery_level'        => $record->fuel_or_battery_level,
            'exterior_status'              => $record->exterior_status,
            'interior_status'              => $record->interior_status,
            'checklist_items'              => $record->checklist_items,
            'notes'                        => $record->notes,
            'images'                       => $imagesWithUrls,
            'is_odometer_adjusted'         => $record->is_odometer_adjusted,
            'odometer_adjustment_reason'   => $record->odometer_adjustment_reason,
            'customer_confirmed'           => $record->customer_confirmed,
            'customer_confirmed_at'        => $record->customer_confirmed_at,
            'customer_confirmation_method' => $record->customer_confirmation_method,
            'customer_notes'               => $record->customer_notes,
            'created_at'                   => $record->created_at,
        ];
    }
}
