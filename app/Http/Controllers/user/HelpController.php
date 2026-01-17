<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index()
    {
        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('user.pages.help.index', compact('faqs'));
    }
}
