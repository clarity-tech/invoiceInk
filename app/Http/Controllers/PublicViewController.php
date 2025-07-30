<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicViewController extends Controller
{
    public function showInvoice(string $uuid): View
    {
        $invoice = Invoice::with(['items', 'companyLocation', 'customerLocation'])
            ->where('uuid', $uuid)
            ->where('type', 'invoice')
            ->firstOrFail();

        return view('public.invoice', compact('invoice'));
    }

    public function showEstimate(string $uuid): View
    {
        $estimate = Invoice::with(['items', 'companyLocation', 'customerLocation'])
            ->where('uuid', $uuid)
            ->where('type', 'estimate')
            ->firstOrFail();

        return view('public.estimate', compact('estimate'));
    }
}