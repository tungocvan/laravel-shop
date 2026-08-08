<?php

namespace Modules\Facebook\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Facebook\DTO\FacebookTokenData;
use Modules\Facebook\Models\FacebookConnection;
use Modules\Facebook\Models\FacebookPage;

class FacebookConnectionService
{
    public function __construct(
        private readonly FacebookOAuthService $oauth,
        private readonly FacebookPageService $pages,
    ) {}

    public function latest(): ?FacebookConnection
    {
        return FacebookConnection::query()->with('pages')->latest('id')->first();
    }

    public function completeOAuth(string $code): FacebookConnection
    {
        $shortToken = $this->oauth->exchangeCodeForUserToken($code);
        $token = $this->exchangeLongTokenSafely($shortToken);
        $permissions = $this->oauth->getGrantedPermissions($token->accessToken);
        $this->oauth->assertRequiredScopes($permissions['granted']);
        $me = $this->oauth->getMe($token->accessToken);

        return DB::transaction(function () use ($token, $permissions, $me): FacebookConnection {
            $connection = FacebookConnection::query()->create([
                'user_id' => Auth::guard('admin')->id(),
                'facebook_user_id' => $me['id'] ?? null,
                'facebook_user_name' => $me['name'] ?? null,
                'user_access_token' => $token->accessToken,
                'token_type' => $token->tokenType,
                'token_expires_at' => $token->expiresAt,
                'granted_scopes' => $permissions['granted'],
                'declined_scopes' => $permissions['declined'],
                'status' => FacebookConnection::STATUS_ACTIVE,
                'last_verified_at' => now(),
            ]);

            $this->pages->syncPages($connection);

            return $connection->refresh();
        });
    }

    public function disconnect(): void
    {
        DB::transaction(function (): void {
            FacebookConnection::query()
                ->where('status', FacebookConnection::STATUS_ACTIVE)
                ->update(['status' => FacebookConnection::STATUS_DISCONNECTED]);

            FacebookPage::query()->update(['is_active' => false]);
        });
    }

    public function syncLatestPages(): int
    {
        $connection = $this->latest();

        if (! $connection) {
            return 0;
        }

        return $this->pages->syncPages($connection)->count();
    }

    private function exchangeLongTokenSafely(FacebookTokenData $shortToken): FacebookTokenData
    {
        try {
            return $this->oauth->exchangeForLongLivedToken($shortToken->accessToken);
        } catch (\Throwable) {
            return $shortToken;
        }
    }
}
