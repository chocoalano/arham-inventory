<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Secara default, kita akan menggunakan guard 'customer'
        $guard = 'customer';

        // Cek apakah customer sudah terautentikasi menggunakan guard 'customer'
        if (! Auth::guard($guard)->check()) {

            // Jika belum login, redirect ke halaman login customer
            return redirect()->route('login.register'); // Ganti 'customer.login' dengan nama route login Anda
        }

        return $next($request);
    }
}
