<?php

namespace App\Http\Controllers;

use App\Support\Jetstream;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrivacyPolicyController extends Controller
{
    public function show(Request $request)
    {
        $policyFile = Jetstream::localizedMarkdownPath('policy.md');

        return view('policy', [
            'policy' => Str::markdown(file_get_contents($policyFile)),
        ]);
    }
}
