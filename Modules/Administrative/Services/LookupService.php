<?php

namespace Modules\Administrative\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Administrative\Enums\AdministrativeFileType;
use Modules\Administrative\Models\AdministrativeFile;
use Modules\Administrative\Models\AdministrativeSubmission;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LookupService
{
    private const SESSION_TTL_MINUTES = 15;

    public function verify(string $submissionCode, string $lookupToken): AdministrativeSubmission
    {
        $code = strtoupper(trim($submissionCode));
        $rateKey = 'administrative-lookup:'.sha1(request()->ip().'|'.$code);
        $ipRateKey = 'administrative-lookup-ip:'.sha1((string) request()->ip());

        if (RateLimiter::tooManyAttempts($rateKey, 10) || RateLimiter::tooManyAttempts($ipRateKey, 30)) {
            throw ValidationException::withMessages([
                'lookup' => 'Bạn đã tra cứu quá nhiều lần. Vui lòng thử lại sau.',
            ]);
        }

        RateLimiter::hit($rateKey, 300);
        RateLimiter::hit($ipRateKey, 300);
        $submission = AdministrativeSubmission::query()->where('submission_code', $code)->first();

        if (! $submission || ! Hash::check(strtoupper(trim($lookupToken)), $submission->lookup_token_hash)) {
            throw ValidationException::withMessages([
                'lookup' => 'Thông tin tra cứu không chính xác.',
            ]);
        }

        RateLimiter::clear($rateKey);

        return $submission;
    }

    public function issueAccess(AdministrativeSubmission $submission): string
    {
        $accessToken = bin2hex(random_bytes(32));
        session()->put("administrative.lookup_access.{$accessToken}", [
            'submission_id' => $submission->id,
            'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES)->timestamp,
        ]);

        return $accessToken;
    }

    public function submissionForAccess(string $accessToken): AdministrativeSubmission
    {
        $grant = session("administrative.lookup_access.{$accessToken}");

        if (! is_array($grant) || (int) ($grant['expires_at'] ?? 0) < now()->timestamp) {
            session()->forget("administrative.lookup_access.{$accessToken}");
            abort(404);
        }

        return AdministrativeSubmission::query()
            ->with([
                'procedure:id,code,name',
                'files' => fn ($query) => $query
                    ->where('file_type', AdministrativeFileType::Result->value)
                    ->select(['id', 'submission_id', 'original_name', 'size', 'created_at']),
            ])
            ->findOrFail((int) $grant['submission_id']);
    }

    public function downloadResult(string $accessToken, int $fileId): StreamedResponse
    {
        $submission = $this->submissionForAccess($accessToken);
        $file = AdministrativeFile::query()
            ->whereKey($fileId)
            ->where('submission_id', $submission->id)
            ->where('file_type', AdministrativeFileType::Result->value)
            ->firstOrFail();

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
