<?php

namespace Modules\Facebook\Services;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Facebook\DTO\FacebookTokenData;

class FacebookOAuthService
{
    public const STATE_SESSION_KEY = 'facebook_oauth_state';

    public function __construct(private readonly FacebookGraphClient $client) {}

    public function buildAuthorizationUrl(): string
    {
        $state = Str::random(48);
        session()->put(self::STATE_SESSION_KEY, $state);

        return URL::query(rtrim((string) config('facebook.oauth_base_url'), '/').'/'.config('facebook.graph_version').'/dialog/oauth', [
            'client_id' => config('facebook.app_id'),
            'redirect_uri' => config('facebook.redirect_uri'),
            'state' => $state,
            'scope' => implode(',', config('facebook.scopes', [])),
            'response_type' => 'code',
        ]);
    }

    public function validateState(?string $state): bool
    {
        $expected = session()->pull(self::STATE_SESSION_KEY);

        return is_string($state) && is_string($expected) && hash_equals($expected, $state);
    }

    public function exchangeCodeForUserToken(string $code): FacebookTokenData
    {
        $data = $this->client->get('oauth/access_token', [
            'client_id' => config('facebook.app_id'),
            'redirect_uri' => config('facebook.redirect_uri'),
            'client_secret' => config('facebook.app_secret'),
            'code' => $code,
        ]);

        return FacebookTokenData::fromResponse($data);
    }

    public function exchangeForLongLivedToken(string $shortLivedToken): FacebookTokenData
    {
        $data = $this->client->get('oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('facebook.app_id'),
            'client_secret' => config('facebook.app_secret'),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        return FacebookTokenData::fromResponse($data);
    }

    public function getGrantedPermissions(string $userToken): array
    {
        $response = $this->client->get('me/permissions', [], $userToken);
        $permissions = collect($response['data'] ?? []);

        return [
            'granted' => $permissions->where('status', 'granted')->pluck('permission')->values()->all(),
            'declined' => $permissions->where('status', 'declined')->pluck('permission')->values()->all(),
        ];
    }

    public function assertRequiredScopes(array $granted): void
    {
        $missing = array_values(array_diff(config('facebook.scopes', []), $granted));

        if ($missing === []) {
            return;
        }

        throw app(FacebookErrorMapper::class)->fromResponse(403, [
            'error' => [
                'message' => 'Tài khoản chưa cấp đủ quyền: '.implode(', ', $missing),
                'type' => 'OAuthException',
                'code' => '200',
            ],
        ]);
    }

    public function getMe(string $userToken): array
    {
        return $this->client->get('me', ['fields' => 'id,name'], $userToken);
    }
}
