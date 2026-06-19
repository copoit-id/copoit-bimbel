<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserBillingController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = BillInvoice::where('user_id', $request->user()->id)
            ->orderByRaw("FIELD(status, 'overdue', 'unpaid', 'paid', 'cancelled')")
            ->orderBy('due_date')
            ->paginate(15);

        return view('user.pages.billing.index', compact('invoices'));
    }
}
