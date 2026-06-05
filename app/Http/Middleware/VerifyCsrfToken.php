<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * @var array<int, string>
     */
    protected $except = [
        // Razorpay cannot send Laravel CSRF tokens; the controller requires X-Razorpay-Signature.
        'razorpay/webhook',
    ];
}
