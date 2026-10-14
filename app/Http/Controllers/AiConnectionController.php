<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\AuthCode;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * The discovery surface for the MCP server.
 *
 * Without this page the feature is undiscoverable: the endpoint is invisible in
 * the UI. It explains how to connect, lists the apps that signed in over OAuth
 * so they can be disconnected, and mints scoped API tokens for clients that
 * cannot sign in.
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
            'apps' => $this->signedInApps($user),
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

    /**
     * Disconnect an app that signed in over OAuth.
     *
     * Only this user's tokens are revoked, never the client itself: a client
     * registered itself without an owner, and its id is not a secret.
     */
    public function disconnect(Request $request, string $client): RedirectResponse
    {
        $userId = $request->user()->getKey();

        $tokenIds = Token::query()
            ->where('user_id', $userId)
            ->where('client_id', $client)
            ->where('revoked', false)
            ->pluck('id');

        if ($tokenIds->isEmpty()) {
            return back()->with('error', 'That app is no longer connected.');
        }

        Token::query()->whereKey($tokenIds)->update(['revoked' => true]);
        RefreshToken::query()->whereIn('access_token_id', $tokenIds)->update(['revoked' => true]);

        // A code issued moments ago but not yet exchanged would otherwise mint
        // a fresh token after the disconnect.
        AuthCode::query()
            ->where('user_id', $userId)
            ->where('client_id', $client)
            ->update(['revoked' => true]);

        return back()->with('success', 'Disconnected. It loses access immediately and has to sign in again to reconnect.');
    }

    /**
     * The apps this user has signed in to over OAuth and not disconnected.
     *
     * Each Claude connection registers its own client, so one client is one
     * connection. It stays live while it holds an unrevoked access token that is
     * unexpired or can still be refreshed.
     *
     * @return list<array{id: string, name: string, connected_at: string|null}>
     */
    private function signedInApps(User $user): array
    {
        return Token::query()
            ->with('client')
            ->where('user_id', $user->getKey())
            ->where('revoked', false)
            ->where(fn (Builder $query) => $query
                ->where('expires_at', '>', now())
                ->orWhereHas('refreshToken', fn (Builder $query) => $query
                    ->where('revoked', false)
                    ->where('expires_at', '>', now())))
            ->latest()
            ->get()
            ->filter(fn (Token $token): bool => $token->client !== null)
            ->unique('client_id')
            ->map(fn (Token $token): array => [
                'id' => (string) $token->client_id,
                'name' => $token->client->name,
                'connected_at' => $token->client->created_at?->toDayDateTimeString(),
            ])
            ->values()
            ->all();
    }
}
