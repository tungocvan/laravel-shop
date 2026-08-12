<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SystemMailConfigService
{
    private const PUBLIC_KEYS = ['MAIL_MAILER','MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_ENCRYPTION','MAIL_FROM_ADDRESS','MAIL_FROM_NAME'];
    private const WRITABLE_KEYS = ['MAIL_MAILER','MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_ENCRYPTION','MAIL_FROM_ADDRESS','MAIL_FROM_NAME'];

    public function __construct(
        private readonly EnvManagerService $envManager,
        private readonly MailConfigService $mailConfigService,
    ) {}

    public function publicConfig(): array
    {
        $env = $this->envManager->getValues();
        $defaults = [
            'MAIL_MAILER' => 'smtp', 'MAIL_HOST' => '', 'MAIL_PORT' => '587',
            'MAIL_USERNAME' => '', 'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => '', 'MAIL_FROM_NAME' => '',
        ];
        $result = [];
        foreach (self::PUBLIC_KEYS as $key) {
            $result[$key] = $env[$key] ?? $defaults[$key];
        }
        $result['MAIL_MAILER'] = 'smtp';
        $enc = $this->normalizeEncryption($result['MAIL_ENCRYPTION'] ?? null);
        $result['MAIL_ENCRYPTION'] = $enc === '' ? 'none' : $enc;
        return $result;
    }

    public function test(array $form, string $recipient, ?int $actorId = null): array
    {
        $candidate = $this->resolveCandidate($form);
        $actorKey = (string) ($actorId ?? 'guest');

        if (! Cache::add('system:mail-config:test-cooldown:'.$actorKey, true, 5)) {
            return ['success' => false, 'message' => 'Vui lòng chờ vài giây trước khi gửi lại email kiểm tra.'];
        }

        $lock = Cache::lock('system:mail-config:test:'.$actorKey, 10);
        if (! $lock->get()) {
            return ['success' => false, 'message' => 'Một email kiểm tra khác đang được gửi. Vui lòng thử lại sau.'];
        }

        try {
            $result = $this->mailConfigService->testSendMail($candidate, $recipient);
            Log::notice('Mail configuration test completed.', [
                'actor_id' => $actorId, 'mailer' => 'smtp', 'host' => $candidate['MAIL_HOST'],
                'port' => $candidate['MAIL_PORT'],
                'password_replaced' => trim((string) ($form['MAIL_PASSWORD'] ?? '')) !== '',
                'success' => (bool) ($result['success'] ?? false),
            ]);
            return $result;
        } finally {
            $lock->release();
        }
    }

    public function save(array $form, ?int $actorId = null): array
    {
        $candidate = $this->resolveCandidate($form);
        $lock = Cache::lock('system:mail-config:update', 15);
        if (! $lock->get()) {
            return ['success' => false, 'message' => 'Một thao tác cập nhật cấu hình Email khác đang được thực hiện.'];
        }

        try {
            if (! $this->envManager->update($candidate)) {
                throw new RuntimeException('Environment update returned false.');
            }
            Artisan::call('config:clear');
            Log::notice('Mail configuration saved.', [
                'actor_id' => $actorId, 'mailer' => 'smtp', 'host' => $candidate['MAIL_HOST'],
                'port' => $candidate['MAIL_PORT'],
                'password_replaced' => trim((string) ($form['MAIL_PASSWORD'] ?? '')) !== '',
            ]);
            return ['success' => true, 'message' => 'Cấu hình Email đã được lưu.'];
        } catch (Throwable $e) {
            Log::error('Mail configuration save failed.', ['actor_id' => $actorId, 'exception' => $e::class]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function resolveCandidate(array $form): array
    {
        if (array_diff(array_keys($form), self::WRITABLE_KEYS) !== []) {
            throw new InvalidArgumentException('Unsupported mail configuration key.');
        }
        $env = $this->envManager->getValues();
        $candidate = [];
        foreach (self::WRITABLE_KEYS as $key) {
            $candidate[$key] = $form[$key] ?? '';
        }
        $candidate['MAIL_MAILER'] = 'smtp';
        $candidate['MAIL_ENCRYPTION'] = $this->normalizeEncryption($candidate['MAIL_ENCRYPTION'] ?? null);
        if (trim((string) $candidate['MAIL_PASSWORD']) === '') {
            $candidate['MAIL_PASSWORD'] = $env['MAIL_PASSWORD'] ?? '';
        }
        if (($candidate['MAIL_HOST'] ?? '') === '' || ($candidate['MAIL_PORT'] ?? '') === '') {
            throw new InvalidArgumentException('Incomplete mail configuration.');
        }
        return $candidate;
    }

    private function normalizeEncryption(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '' || $value === 'none' || $value === 'null') {
            return '';
        }
        if (! in_array($value, ['tls', 'ssl'], true)) {
            throw new InvalidArgumentException('Unsupported mail encryption.');
        }
        return $value;
    }
}
