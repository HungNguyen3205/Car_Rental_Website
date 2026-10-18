<?php

namespace App\Services\Booking;

use App\Exceptions\CarBookingConflictException;
use App\Exceptions\CarBookingValidationException;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingLifecycleService
{
    protected BookingService $bookingService;
    protected NotificationService $notificationService;

    public function __construct(
        BookingService $bookingService,
        ?NotificationService $notificationService = null
    ) {
        $this->bookingService = $bookingService;
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

    /**
     * Định nghĩa ma trận chuyển trạng thái hợp lệ theo vai trò (Role-based State Machine)
     */
    public const VALID_TRANSITIONS = [
        Booking::STATUS_PENDING => [
            Booking::STATUS_CONFIRMED   => ['roles' => ['admin'], 'action' => 'confirm', 'label' => 'Duyệt đơn'],
            Booking::STATUS_CANCELLED   => ['roles' => ['admin', 'user'], 'action' => 'cancel', 'label' => 'Hủy đơn'],
        ],
        Booking::STATUS_CONFIRMED => [
            Booking::STATUS_IN_PROGRESS => ['roles' => ['admin'], 'action' => 'deliver', 'label' => 'Bàn giao xe'],
            Booking::STATUS_CANCELLED   => ['roles' => ['admin'], 'action' => 'cancel', 'label' => 'Hủy đơn'],
        ],
        Booking::STATUS_IN_PROGRESS => [
            Booking::STATUS_RETURNED    => ['roles' => ['admin'], 'action' => 'receive_return', 'label' => 'Nhận trả xe'],
            Booking::STATUS_CANCELLED   => ['roles' => ['admin'], 'action' => 'cancel_emergency', 'label' => 'Hủy đơn khẩn cấp'],
        ],
        Booking::STATUS_RETURNED => [
            Booking::STATUS_COMPLETED   => ['roles' => ['admin'], 'action' => 'complete', 'label' => 'Hoàn tất đơn'],
        ],
        Booking::STATUS_COMPLETED => [
            // Trạng thái kết thúc (Terminal State) - không được chuyển sang trạng thái nào khác
        ],
        Booking::STATUS_CANCELLED => [
            // Trạng thái kết thúc (Terminal State) - không được chuyển sang trạng thái nào khác
        ],
    ];

    /**
     * Nhãn và metadata cho từng trạng thái
     */
    public const STATUS_METADATA = [
        Booking::STATUS_PENDING => [
            'key'            => 'pending',
            'label'          => 'Chờ duyệt',
            'badge_color'    => 'warning',
            'icon'           => 'fa-solid fa-hourglass-half',
            'holds_schedule' => true,
            'description'    => 'Đơn đặt xe mới tạo, đang chờ quản trị viên xác nhận và duyệt lịch xe.',
        ],
        Booking::STATUS_CONFIRMED => [
            'key'            => 'confirmed',
            'label'          => 'Đã duyệt',
            'badge_color'    => 'primary',
            'icon'           => 'fa-solid fa-calendar-check',
            'holds_schedule' => true,
            'description'    => 'Đơn đặt xe đã được duyệt, xe sẵn sàng chờ khách tới nhận hoặc bàn giao.',
        ],
        Booking::STATUS_CANCELLED => [
            'key'            => 'cancelled',
            'label'          => 'Đã hủy',
            'badge_color'    => 'danger',
            'icon'           => 'fa-solid fa-ban',
            'holds_schedule' => false,
            'description'    => 'Đơn đặt xe đã bị hủy, lịch xe được giải phóng hoàn toàn.',
        ],
        Booking::STATUS_IN_PROGRESS => [
            'key'            => 'in_progress',
            'label'          => 'Đang thuê / Đã giao xe',
            'badge_color'    => 'info',
            'icon'           => 'fa-solid fa-car-side',
            'holds_schedule' => true,
            'description'    => 'Xe đã được bàn giao cho khách hàng và đang trong hành trình thuê.',
        ],
        Booking::STATUS_RETURNED => [
            'key'            => 'returned',
            'label'          => 'Đã nhận trả xe',
            'badge_color'    => 'secondary',
            'icon'           => 'fa-solid fa-arrow-right-arrow-left',
            'holds_schedule' => true,
            'description'    => 'Khách hàng đã trả xe, nhân viên đang kiểm tra tình trạng xe trước khi chốt đơn.',
        ],
        Booking::STATUS_COMPLETED => [
            'key'            => 'completed',
            'label'          => 'Hoàn tất',
            'badge_color'    => 'success',
            'icon'           => 'fa-solid fa-circle-check',
            'holds_schedule' => true,
            'description'    => 'Hành trình thuê xe đã hoàn tất thành công và đóng đơn.',
        ],
    ];

    /**
     * Lấy thông tin metadata của trạng thái
     */
    public function getStatusMeta(int $status): array
    {
        return self::STATUS_METADATA[$status] ?? [
            'key'            => 'unknown',
            'label'          => 'Không xác định',
            'badge_color'    => 'light',
            'icon'           => 'fa-solid fa-question',
            'holds_schedule' => false,
            'description'    => 'Trạng thái không hợp lệ',
        ];
    }

    /**
     * Xác định vai trò của tác nhân thực hiện
     */
    protected function resolveActorRole($actor): ?string
    {
        if ($actor instanceof Admin) {
            return 'admin';
        }
        if ($actor instanceof User) {
            return 'user';
        }
        return null;
    }

    /**
     * Kiểm tra xem việc chuyển từ trạng thái hiện tại sang trạng thái đích có hợp lệ không
     *
     * @param Booking $booking
     * @param int $targetStatus
     * @param mixed $actor Admin hoặc User
     * @return array ['allowed' => bool, 'reason' => ?string]
     */
    public function canTransition(Booking $booking, int $targetStatus, $actor = null): array
    {
        $currentStatus = (int) $booking->tinh_trang;

        // Xử lý tính lũy đẳng (Idempotency): Nếu trạng thái đích trùng trạng thái hiện tại
        if ($currentStatus === $targetStatus) {
            return [
                'allowed' => true,
                'is_same' => true,
                'reason'  => 'Đơn đặt xe hiện tại đã ở trạng thái này.',
            ];
        }

        // Kiểm tra xem trạng thái hiện tại có chuyển tiếp sang trạng thái đích được không
        $transitions = self::VALID_TRANSITIONS[$currentStatus] ?? [];
        if (!array_key_exists($targetStatus, $transitions)) {
            $fromLabel = self::STATUS_METADATA[$currentStatus]['label'] ?? "Mã $currentStatus";
            $toLabel   = self::STATUS_METADATA[$targetStatus]['label'] ?? "Mã $targetStatus";

            if ($currentStatus === Booking::STATUS_CANCELLED) {
                return [
                    'allowed' => false,
                    'reason'  => 'Không thể kích hoạt lại đơn đặt xe đã hủy vì lịch xe đã được giải phóng. Vui lòng tạo đơn mới!',
                ];
            }

            if ($currentStatus === Booking::STATUS_COMPLETED) {
                return [
                    'allowed' => false,
                    'reason'  => 'Đơn đặt xe đã hoàn tất đóng đơn thành công, không thể thay đổi trạng thái nữa!',
                ];
            }

            if ($currentStatus === Booking::STATUS_CONFIRMED && $targetStatus === Booking::STATUS_PENDING) {
                return [
                    'allowed' => false,
                    'reason'  => 'Đơn đặt xe đã được duyệt, không thể chuyển ngược về trạng thái chờ duyệt!',
                ];
            }

            if ($targetStatus === Booking::STATUS_COMPLETED && $currentStatus !== Booking::STATUS_RETURNED) {
                return [
                    'allowed' => false,
                    'reason'  => 'Không thể hoàn tất đơn đặt xe trước khi hoàn thành bàn giao và nhận trả xe từ khách hàng!',
                ];
            }

            return [
                'allowed' => false,
                'reason'  => "Chuyển tiếp trạng thái không hợp lệ: Không thể chuyển từ '{$fromLabel}' sang '{$toLabel}'.",
            ];
        }

        // Kiểm tra phân quyền vai trò
        if ($actor) {
            $actorRole = $this->resolveActorRole($actor);
            $allowedRoles = $transitions[$targetStatus]['roles'] ?? [];

            if (!in_array($actorRole, $allowedRoles, true)) {
                return [
                    'allowed' => false,
                    'reason'  => 'Bạn không có quyền thực hiện thao tác chuyển trạng thái này!',
                ];
            }

            // Nếu là User hủy đơn: Chỉ được hủy đơn của chính mình khi đơn đang ở trạng thái Chờ duyệt
            if ($actorRole === 'user') {
                if ((int) $booking->user_id !== (int) $actor->id) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Bạn không có quyền thao tác trên đơn đặt xe của người khác!',
                    ];
                }

                if ($currentStatus !== Booking::STATUS_PENDING) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Khách hàng chỉ có thể hủy đơn khi đang ở trạng thái Chờ xác nhận!',
                    ];
                }
            }
        }

        return [
            'allowed' => true,
            'reason'  => null,
        ];
    }

    /**
     * Thực hiện chuyển trạng thái an toàn với Khóa bi quan và ghi nhận đầy đủ người thao tác, thời điểm, lý do.
     *
     * @param int|Booking $booking
     * @param int $targetStatus
     * @param mixed $actor Admin hoặc User thực hiện
     * @param array $params ['cancel_reason' => string, 'status_note' => string]
     * @return Booking
     * @throws CarBookingValidationException
     * @throws CarBookingConflictException
     */
    public function transition($booking, int $targetStatus, $actor, array $params = []): Booking
    {
        $bookingId = $booking instanceof Booking ? $booking->id : (int) $booking;

        return DB::transaction(function () use ($bookingId, $targetStatus, $actor, $params) {
            // Khóa bi quan dòng đơn đặt xe để chống race condition và chuyển trạng thái đồng thời
            $lockedBooking = Booking::where('id', $bookingId)->lockForUpdate()->first();
            if (!$lockedBooking) {
                throw new CarBookingValidationException('Không tìm thấy đơn đặt xe!', 404);
            }

            $currentStatus = (int) $lockedBooking->tinh_trang;

            // Xử lý Idempotency: Nếu trạng thái hiện tại đã là trạng thái đích
            if ($currentStatus === $targetStatus) {
                // Nếu có cập nhật thêm ghi chú mà không đổi trạng thái
                if (!empty($params['status_note'])) {
                    $note = trim($params['status_note']);
                    $actorName = $actor->ho_ten ?? ('User #' . $actor->id);
                    $timestamp = Carbon::now()->format('d/m/Y H:i');
                    $appendNote = "[$timestamp - $actorName]: $note";
                    $lockedBooking->status_note = $lockedBooking->status_note 
                        ? ($lockedBooking->status_note . "\n" . $appendNote) 
                        : $appendNote;
                    $lockedBooking->save();
                }
                return $lockedBooking;
            }

            // Kiểm tra tính hợp lệ của bước chuyển
            $check = $this->canTransition($lockedBooking, $targetStatus, $actor);
            if (!$check['allowed']) {
                throw new CarBookingValidationException($check['reason'] ?? 'Chuyển trạng thái không hợp lệ!', 422);
            }

            $now = Carbon::now();
            $actorRole = $this->resolveActorRole($actor);

            // Bổ sung ghi chú nếu có
            if (!empty($params['status_note'])) {
                $note = trim($params['status_note']);
                $actorName = $actor->ho_ten ?? ('User #' . $actor->id);
                $timestamp = $now->format('d/m/Y H:i');
                $appendNote = "[$timestamp - $actorName]: $note";
                $lockedBooking->status_note = $lockedBooking->status_note 
                    ? ($lockedBooking->status_note . "\n" . $appendNote) 
                    : $appendNote;
            }

            // Xử lý chi tiết theo từng trạng thái đích
            switch ($targetStatus) {
                case Booking::STATUS_CONFIRMED:
                    // Kiểm tra tính khả dụng của xe trước khi duyệt
                    if (!$this->bookingService->isCarAvailable(
                        $lockedBooking->car_id, 
                        $lockedBooking->start_date, 
                        $lockedBooking->end_date, 
                        $lockedBooking->id
                    )) {
                        throw new CarBookingConflictException(
                            'Không thể duyệt đơn vì xe đã bị trùng lịch với đơn khác hoặc đang bảo dưỡng!', 
                            409
                        );
                    }
                    $lockedBooking->tinh_trang = Booking::STATUS_CONFIRMED;
                    break;

                case Booking::STATUS_IN_PROGRESS:
                    // Bàn giao xe cho khách hàng
                    $lockedBooking->tinh_trang = Booking::STATUS_IN_PROGRESS;
                    $lockedBooking->delivered_at = $now;
                    if ($actorRole === 'admin') {
                        $lockedBooking->delivered_by = $actor->id;
                    }
                    break;

                case Booking::STATUS_RETURNED:
                    // Nhận xe trả từ khách
                    $lockedBooking->tinh_trang = Booking::STATUS_RETURNED;
                    $lockedBooking->returned_at = $now;
                    if ($actorRole === 'admin') {
                        $lockedBooking->returned_by = $actor->id;
                    }
                    break;

                case Booking::STATUS_COMPLETED:
                    // Hoàn tất đơn thuê xe
                    $lockedBooking->tinh_trang = Booking::STATUS_COMPLETED;
                    $lockedBooking->completed_at = $now;
                    if ($actorRole === 'admin') {
                        $lockedBooking->completed_by = $actor->id;
                    }
                    break;

                case Booking::STATUS_CANCELLED:
                    // Hủy đơn đặt xe: Bắt buộc phải có lý do hủy
                    $cancelReason = trim($params['cancel_reason'] ?? '');
                    if (empty($cancelReason)) {
                        throw new CarBookingValidationException('Vui lòng cung cấp lý do hủy đơn đặt xe!', 422);
                    }

                    $lockedBooking->tinh_trang = Booking::STATUS_CANCELLED;
                    $lockedBooking->cancelled_at = $now;
                    $lockedBooking->cancelled_by_type = $actorRole ?: 'admin';
                    $lockedBooking->cancelled_by_id = $actor->id ?? null;
                    $lockedBooking->cancel_reason = $cancelReason;
                    break;

                default:
                    throw new CarBookingValidationException('Trạng thái đích không được hệ thống hỗ trợ!', 422);
            }

            // TUYỆT ĐỐI KHÔNG CHẠM VÀO is_thanh_toan: Trạng thái tài chính độc lập hoàn toàn
            $lockedBooking->save();

            // Phát thông báo sau khi transaction commit an toàn
            if ($targetStatus === Booking::STATUS_CONFIRMED) {
                $this->notificationService->notifyBookingConfirmed($lockedBooking);
            } elseif ($targetStatus === Booking::STATUS_CANCELLED) {
                $this->notificationService->notifyBookingCancelled(
                    $lockedBooking, 
                    $lockedBooking->cancel_reason ?: 'Hủy theo yêu cầu'
                );
            }

            return $lockedBooking;
        });
    }

    /**
     * Lấy danh sách các hành động tiếp theo hợp lệ cho giao diện người dùng
     *
     * @param Booking $booking
     * @param mixed $actor Admin hoặc User
     * @return array
     */
    public function getNextActions(Booking $booking, $actor = null): array
    {
        $currentStatus = (int) $booking->tinh_trang;
        $possibleTransitions = self::VALID_TRANSITIONS[$currentStatus] ?? [];
        $actorRole = $actor ? $this->resolveActorRole($actor) : 'admin';

        $actions = [];
        foreach ($possibleTransitions as $targetStatus => $meta) {
            if (in_array($actorRole, $meta['roles'], true)) {
                $statusMeta = self::STATUS_METADATA[$targetStatus] ?? [];
                $actions[] = [
                    'action'           => $meta['action'],
                    'target_status'    => $targetStatus,
                    'label'            => $meta['label'],
                    'badge_color'      => $statusMeta['badge_color'] ?? 'secondary',
                    'icon'             => $statusMeta['icon'] ?? 'fa-solid fa-play',
                    'requires_reason'  => $targetStatus === Booking::STATUS_CANCELLED,
                    'requires_note'    => in_array($targetStatus, [Booking::STATUS_IN_PROGRESS, Booking::STATUS_RETURNED], true),
                ];
            }
        }

        return $actions;
    }

    /**
     * Lấy dòng thời gian hành trình đơn đặt xe (Timeline Events)
     *
     * @param Booking $booking
     * @return array
     */
    public function getTimelineEvents(Booking $booking): array
    {
        $events = [];

        // 1. Tạo đơn
        $events[] = [
            'step'        => 1,
            'key'         => 'created',
            'title'       => 'Tạo đơn đặt xe',
            'timestamp'   => $booking->created_at ? Carbon::parse($booking->created_at)->format('d/m/Y H:i') : null,
            'completed'   => true,
            'description' => 'Khách hàng tạo đơn đặt xe thành công. Mã đơn: ' . $booking->ma_dat_xe,
        ];

        // 2. Duyệt đơn
        $isConfirmed = (int) $booking->tinh_trang >= Booking::STATUS_CONFIRMED && (int) $booking->tinh_trang !== Booking::STATUS_CANCELLED;
        $events[] = [
            'step'        => 2,
            'key'         => 'confirmed',
            'title'       => 'Duyệt đơn thuê',
            'timestamp'   => $isConfirmed ? ($booking->delivered_at ? Carbon::parse($booking->delivered_at)->format('d/m/Y H:i') : 'Đã xác nhận') : null,
            'completed'   => $isConfirmed,
            'description' => $isConfirmed ? 'Đơn đặt xe đã được quản trị viên phê duyệt.' : 'Đang chờ quản trị viên duyệt lịch.',
        ];

        // 3. Bàn giao xe
        $isDelivered = in_array((int) $booking->tinh_trang, [Booking::STATUS_IN_PROGRESS, Booking::STATUS_RETURNED, Booking::STATUS_COMPLETED], true);
        $events[] = [
            'step'        => 3,
            'key'         => 'delivered',
            'title'       => 'Bàn giao xe',
            'timestamp'   => $booking->delivered_at ? Carbon::parse($booking->delivered_at)->format('d/m/Y H:i') : null,
            'completed'   => $isDelivered,
            'operator'    => $booking->deliveredByAdmin?->ho_ten,
            'description' => $isDelivered ? 'Xe đã được bàn giao cho khách hàng.' : 'Chờ tới thời điểm nhận xe và ký biên bản bàn giao.',
        ];

        // 4. Nhận trả xe
        $isReturned = in_array((int) $booking->tinh_trang, [Booking::STATUS_RETURNED, Booking::STATUS_COMPLETED], true);
        $events[] = [
            'step'        => 4,
            'key'         => 'returned',
            'title'       => 'Nhận trả xe',
            'timestamp'   => $booking->returned_at ? Carbon::parse($booking->returned_at)->format('d/m/Y H:i') : null,
            'completed'   => $isReturned,
            'operator'    => $booking->returnedByAdmin?->ho_ten,
            'description' => $isReturned ? 'Khách hàng đã bàn giao trả xe, hoàn tất kiểm tra tình trạng.' : 'Chờ kết thúc chuyến đi và nhận trả xe.',
        ];

        // 5. Hoàn tất đóng đơn
        $isCompleted = (int) $booking->tinh_trang === Booking::STATUS_COMPLETED;
        $events[] = [
            'step'        => 5,
            'key'         => 'completed',
            'title'       => 'Hoàn tất chuyến đi',
            'timestamp'   => $booking->completed_at ? Carbon::parse($booking->completed_at)->format('d/m/Y H:i') : null,
            'completed'   => $isCompleted,
            'operator'    => $booking->completedByAdmin?->ho_ten,
            'description' => $isCompleted ? 'Hành trình thuê xe đã hoàn tất thành công. Cảm ơn quý khách!' : 'Chờ hoàn tất tất cả các thủ tục.',
        ];

        // Nếu đơn bị hủy
        if ((int) $booking->tinh_trang === Booking::STATUS_CANCELLED) {
            $events[] = [
                'step'        => 'cancel',
                'key'         => 'cancelled',
                'title'       => 'Đơn đã hủy',
                'timestamp'   => $booking->cancelled_at ? Carbon::parse($booking->cancelled_at)->format('d/m/Y H:i') : null,
                'completed'   => true,
                'is_cancel'   => true,
                'reason'      => $booking->cancel_reason,
                'by_type'     => $booking->cancelled_by_type,
                'description' => 'Đơn đặt xe đã hủy. Lý do: ' . ($booking->cancel_reason ?: 'Không có ghi chú.'),
            ];
        }

        return $events;
    }
}
