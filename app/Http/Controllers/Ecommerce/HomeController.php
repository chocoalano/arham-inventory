<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('ecommerce.pages.index');
    }
    public function about()
    {
        return view('ecommerce.pages.about');
    }
    public function articles()
    {
        return view('ecommerce.pages.articles');
    }
}
