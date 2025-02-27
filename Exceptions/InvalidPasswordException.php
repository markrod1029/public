<?php
namespace App\Exceptions;

use RuntimeException;

class InvalidPasswordException extends RuntimeException
{
    protected $message = 'The provided password does not match your current password.';
    protected $code = 422;

    // Maaari kang magdagdag ng mga custom methods dito...
}
