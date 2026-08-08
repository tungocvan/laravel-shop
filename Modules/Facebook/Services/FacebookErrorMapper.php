<?php

namespace Modules\Facebook\Services;

use Modules\Facebook\DTO\FacebookApiErrorData;
use Modules\Facebook\Exceptions\FacebookApiException;
use Modules\Facebook\Exceptions\FacebookAuthenticationException;
use Modules\Facebook\Exceptions\FacebookPermissionException;
use Modules\Facebook\Exceptions\FacebookRateLimitException;
use Modules\Facebook\Exceptions\FacebookTemporaryException;
use Modules\Facebook\Exceptions\FacebookValidationException;

class FacebookErrorMapper
{
    public function fromResponse(?int $httpStatus, array $payload): FacebookApiException
    {
        $error = $payload['error'] ?? [];
        $code = isset($error['code']) ? (string) $error['code'] : null;
        $subcode = isset($error['error_subcode']) ? (string) $error['error_subcode'] : null;
        $type = $error['type'] ?? null;
        $message = $error['message'] ?? 'Meta Graph API trả về lỗi không xác định.';
        $traceId = $error['fbtrace_id'] ?? null;
        $retryable = $this->isRetryable($httpStatus, $code, $type);

        $data = new FacebookApiErrorData($httpStatus, $code, $subcode, $type, $message, $traceId, $retryable);

        if (in_array($code, ['190', '102'], true)) {
            return new FacebookAuthenticationException($data);
        }

        if (in_array($code, ['10', '200', '2500'], true)) {
            return new FacebookPermissionException($data);
        }

        if (in_array($code, ['4', '17', '32', '613'], true)) {
            return new FacebookRateLimitException($data);
        }

        if ($retryable) {
            return new FacebookTemporaryException($data);
        }

        if ($httpStatus !== null && $httpStatus >= 400 && $httpStatus < 500) {
            return new FacebookValidationException($data);
        }

        return new FacebookApiException($data);
    }

    public function isRetryable(?int $httpStatus, ?string $code = null, ?string $type = null): bool
    {
        if ($httpStatus !== null && $httpStatus >= 500) {
            return true;
        }

        if (in_array($code, ['1', '2', '4', '17', '32', '613'], true)) {
            return true;
        }

        return in_array($type, ['OAuthException: transient'], true);
    }
}
