<?php

namespace App\Exceptions;

use Exception;

class CarBookingValidationException extends Exception
{
    public function __construct(string $message = 'Thông tin đặt xe không hợp lệ!', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
