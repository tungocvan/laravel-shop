<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

class SocialConfigService
{
    private const PUBLIC_KEYS = [
        'GOOGLE_CLIENT_ID',
        'GOOGLE_REDIRECT',
        'FACEBOOK_CLIENT_ID',
        'FACEBOOK_REDIRECT_URI',
        'GOOGLE_ANALYTICS_ID',
    ];

    private const SECRET_KEYS = [
        'GOOGLE_CLIENT_SECRET',
        'FACEBOOK_CLIENT_SECRET',
        'TINYMCE_API_KEY',
    ];

    public function __construct(private readonly EnvManagerService $envManager)
    {
    }

    public function publicValues(): array
    {
        $env = $this->envManager->getValues();
        $result = [];
        foreach (self::PUBLIC_KEYS as $key) {
            $result[$key] = $env[$key] ?? '';
        }
        foreach (self::SECRET_KEYS as $key) {
            $result[$key] = '';
        }

        return $result;
    }

    public function configuredSecrets(): array
    {
        $env = $this->envManager->getValues();
        return [
            'google' => ($env['GOOGLE_CLIENT_SECRET'] ?? '') !== '',
            'facebook' => ($env['FACEBOOK_CLIENT_SECRET'] ?? '') !== '',
            'tinymce' => ($env['TINYMCE_API_KEY'] ?? '') !== '',
        ];
    }

    public function save(array $form): bool
    {
        $googleId = (string) ($form['GOOGLE_CLIENT_ID'] ?? '');
        $facebookId = (string) ($form['FACEBOOK_CLIENT_ID'] ?? '');

        if ($googleId !== '' && !str_contains($googleId, '.apps.googleusercontent.com')) {
            throw new InvalidArgumentException('Google Client ID format is invalid.');
        }
        if ($facebookId !== '' && !ctype_digit($facebookId)) {
            throw new InvalidArgumentException('Facebook App ID format is invalid.');
        }

        $current = $this->envManager->getValues();
        $data = [];
        foreach (self::PUBLIC_KEYS as $key) {
            $data[$key] = (string) ($form[$key] ?? '');
        }
        foreach (self::SECRET_KEYS as $key) {
            $data[$key] = ($form[$key] ?? '') !== '' ? (string) $form[$key] : (string) ($current[$key] ?? '');
        }

        $lock = Cache::lock('system:social-config:update', 10);
        if (!$lock->get()) {
            throw new RuntimeException('Social configuration update is already in progress.');
        }

        try {
            return $this->envManager->update($data);
        } finally {
            $lock->release();
        }
    }
}
