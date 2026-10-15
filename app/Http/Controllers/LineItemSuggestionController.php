<?php

namespace App\Http\Controllers;

use App\Ai\Agents\LineItemClassifier;
use App\Models\Invoice;
use App\Services\InvoiceDraftBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\AiException;

class LineItemSuggestionController extends Controller
{
    /**
     * Suggest the SAC/HSN code and tax rate for a line from its description.
     * A code that is not all digits, or a rate the organization does not
     * charge, is dropped rather than suggested.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $organization = $request->user()->currentTeam;

        abort_unless(InvoiceDraftBuilder::isAvailableFor($organization), 403);

        $validated = $request->validate([
            'description' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        try {
            $response = (new LineItemClassifier($organization))->prompt(
                "Line: {$validated['description']}",
                provider: Lab::Anthropic,
                model: config('services.item_suggestions.model'),
                timeout: config('services.item_suggestions.timeout'),
            );
        } catch (AiException|ConnectionException|RequestException $exception) {
            report($exception);

            throw ValidationException::withMessages(['suggestion' => __('messages.item_suggestion.failed')]);
        }

        $code = preg_replace('/\s+/', '', (string) ($response['code'] ?? ''));
        $percent = $response['tax_rate_percent'] ?? null;
        $taxRate = $percent === null ? null : (int) round((float) $percent * 100);
        $rates = array_column($organization->country_code?->taxOptions() ?? [], 'rate');
        $reason = Str::limit(trim((string) ($response['reason'] ?? '')), 200);

        // A reply cut short arrives without its reason; there is nothing to offer.
        if ($reason === '') {
            throw ValidationException::withMessages(['suggestion' => __('messages.item_suggestion.failed')]);
        }

        return response()->json([
            'sac_code' => preg_match('/^\d{4,8}$/', $code) ? $code : null,
            'tax_rate' => in_array($taxRate, $rates, true) ? $taxRate : null,
            'reason' => $reason,
        ]);
    }
}
