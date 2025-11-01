<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /** Tampilkan profil */
    public function show()
    {
        $customer = Auth::guard('customer')->user();
        abort_if(!$customer, 403);

        return view('ecommerce.pages.profile.show', compact('customer'));
    }

    /** Alias untuk kompatibilitas rute lama */
    public function viewProfile()
    {
        return $this->show();
    }

    /** Update profil */
    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        abort_if(!$customer, '403');

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        // Pastikan model Customer memiliki fillable: name, email, phone
        $customer->fill($validated)->save();

        return redirect()->route('profile.view')->with('success', 'Profil berhasil diperbarui.');
    }
}
