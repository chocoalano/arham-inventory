<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index()
    {
        // Logic untuk menampilkan daftar pesanan customer
        return view('ecommerce.pages.auth.orders');
    }
}
