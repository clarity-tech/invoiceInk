<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceDraftBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Exceptions\AiException;

class InvoiceDraftController extends Controller
{
    /**
     * Read a pasted message or an uploaded document and open the invoice or
     * estimate create form pre-filled from it. Nothing is saved: the draft waits in the cache until
     * the form reads it.
     */
    public function store(Request $request, InvoiceDraftBuilder $builder): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $organization = $request->user()->currentTeam;

        abort_unless(InvoiceDraftBuilder::isAvailableFor($organization), 403);

        // Anthropic takes an image of up to 10 MB once base64 grows it by a
        // third, so images stop at 7 MB; PDFs only count toward the 32 MB request.
        $isImage = str_starts_with((string) $request->file('file')?->getMimeType(), 'image/');

        $validated = $request->validate([
            'type' => ['sometimes', 'in:invoice,estimate'],
            'text' => ['nullable', 'string', 'max:20000', 'required_without:file'],
            'file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp', $isImage ? 'max:7168' : 'max:10240', 'required_without:text'],
        ]);

        $type = $validated['type'] ?? 'invoice';

        try {
            $draft = $builder->fromDocument($organization, $type, $validated['text'] ?? null, $request->file('file'));
        } catch (AiException|ConnectionException|RequestException $exception) {
            report($exception);

            return back()->withErrors(['draft' => __('messages.invoice_draft.failed')]);
        }

        return to_route("{$type}s.create", ['draft' => $builder->remember($request->user(), $draft)]);
    }
}
