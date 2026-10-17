<?php

namespace App\Exceptions;

use Exception;

class CarBookingConflictException extends Exception
{
    public function __construct(string $message = 'Xe vừa được khách hàng khác giữ chỗ trong khung giờ này. Vui lòng chọn khung giờ khác hoặc đổi xe phù hợp!', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
