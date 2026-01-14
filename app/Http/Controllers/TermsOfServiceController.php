<?php

namespace App\Http\Controllers;

use App\Support\Jetstream;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TermsOfServiceController extends Controller
{
    public function show(Request $request)
    {
        $termsFile = Jetstream::localizedMarkdownPath('terms.md');

        return view('terms', [
            'terms' => Str::markdown(file_get_contents($termsFile)),
        ]);
    }
}
