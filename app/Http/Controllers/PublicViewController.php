<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicViewController extends Controller
{
    public function showInvoice(string $ulid): View
    {
        $invoice = Invoice::with(['items', 'companyLocation', 'customerLocation'])
            ->where('ulid', $ulid)
            ->where('type', 'invoice')
            ->firstOrFail();

        return view('public.invoice', compact('invoice'));
    }

    public function showEstimate(string $ulid): View
    {
        $estimate = Invoice::with(['items', 'companyLocation', 'customerLocation'])
            ->where('ulid', $ulid)
            ->where('type', 'estimate')
            ->firstOrFail();

        return view('public.estimate', compact('estimate'));
    }
}