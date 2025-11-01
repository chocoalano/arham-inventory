<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login_register(Request $request)
    {
        // Logic untuk menampilkan halaman login
        return view('ecommerce.pages.auth.login-register');
    }
    public function login_submit(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
            'remember' => ['nullable', 'string'],
        ]);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $remember = !empty($data['remember']);

        if (Auth::guard('customer')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(url('/'));
        }

        return back()->withErrors(['email' => 'Email atau password tidak valid'])->withInput($request->only('email'));
    }
    public function register_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['nullable','string','max:100'],
            'last_name' => ['nullable','string','max:100'],
            'email' => ['required','email','max:150','unique:customers,email'],
            'phone' => ['nullable','string','max:30'],
            'password' => ['required','string','min:6','confirmed'],
            // optional address fields can be passed as JSON or individual keys
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $payload = $validator->validated();

        $customer = Customer::create([
            'uuid' => (string) Str::uuid(),
            'first_name' => $payload['first_name'] ?? null,
            'last_name' => $payload['last_name'] ?? null,
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'password' => Hash::make($payload['password']),
            'billing_address' => null,
            'shipping_address' => null,
            'is_active' => true,
        ]);

    // Log the customer in using the customer guard
    Auth::guard('customer')->login($customer);
    $request->session()->regenerate();

    return redirect()->intended(url('/'));
    }
}
