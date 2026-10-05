<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * The discovery surface for the MCP server.
 *
 * Without this page the feature is undiscoverable: the endpoint is invisible in
 * the UI and there is no other way for a user to mint the token it requires.
 */
class AiConnectionController extends Controller
{
    /**
     * The ability every MCP token carries.
     *
     * Tokens are scoped rather than general-purpose so that a credential minted
     * here cannot be repurposed, and so a future write-capable tool can require
     * a different ability without widening these.
     */
    public const ABILITY = 'mcp:read';

    private const TOKEN_NAME_PREFIX = 'AI assistant';

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/AiConnection', [
            'endpoint' => url('/mcp'),
            'enabled' => (bool) config('mcp.web_enabled'),
            'organization' => $user->currentTeam?->name,
            // Read straight from the session rather than adding a key to the
            // shared flash payload: this value must reach exactly one render.
            'newToken' => $request->session()->get('mcpToken'),
            'tokens' => $user->tokens()
                ->where('abilities', 'like', '%'.self::ABILITY.'%')
                ->latest()
                ->get()
                ->map(fn (PersonalAccessToken $token): array => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at?->toDayDateTimeString(),
                    'last_used_at' => $token->last_used_at?->diffForHumans(),
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:60'],
        ]);

        $name = $validated['name'] ?: self::TOKEN_NAME_PREFIX;

        $token = $request->user()->createToken($name, [self::ABILITY]);

        // The plaintext value exists only here — Sanctum stores a hash — so it
        // is flashed for a single render and never persisted or logged.
        return back()->with('mcpToken', $token->plainTextToken);
    }

    public function destroy(Request $request, string $token): RedirectResponse
    {
        $deleted = $request->user()->tokens()
            ->where('id', $token)
            ->where('abilities', 'like', '%'.self::ABILITY.'%')
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'That connection no longer exists.');
        }

        return back()->with('success', 'Connection revoked. Any AI tool using it loses access immediately.');
    }
}
