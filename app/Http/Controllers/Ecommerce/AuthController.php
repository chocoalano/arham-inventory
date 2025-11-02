<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'string'],
        ]);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $remember = ! empty($data['remember']);

        if (Auth::guard('customer')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(url('/'));
        }

        return back()->withErrors(['email' => 'Email atau password tidak valid'])->withInput($request->only('email'));
    }

    public function register_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
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

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function profile()
    {
        $user = Auth::guard('customer')->user();
        $orders = $user->getOrders()->paginate(5);

        return view('ecommerce.pages.auth.profile', compact('user', 'orders'));
    }

    public function profile_update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'phone' => ['required', 'string', 'max:30'],

            // opsional sesuai form/model
            'company' => ['nullable', 'string', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:100'],

            // metode bayar pilihan user
            'preferred_payment_method' => ['required', 'in:bank_transfer,cod,cash,check'],

            // ganti password (opsional)
            'current_password' => ['required_with:password', 'current_password:customer'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'], // butuh password_confirmation
        ];

        $messages = [
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
        ];

        // gunakan Validator agar bisa kontrol redirect saat gagal
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()
                ->route('auth.profile')
                ->withFragment('account-info')   // tanpa tanda '#'
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        // Update field profil utama
        $customer->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'company' => $data['company'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
            'preferred_payment_method' => $data['preferred_payment_method'],
        ]);

        // Ganti password jika diisi
        if (! empty($data['password'])) {
            $customer->password = Hash::make($data['password']);
        }

        $customer->save();

        return redirect()
            ->route('auth.profile')
            ->withFragment('account-info')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
