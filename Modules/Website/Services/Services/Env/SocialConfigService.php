<?php
namespace Modules\Admin\Services\Env;

class SocialConfigService
{
    /**
     * Kiểm tra định dạng Client ID để tránh các lỗi copy-paste phổ biến
     */
    public function validateCredentials(array $config): array
    {
        if (isset($config['GOOGLE_CLIENT_ID']) && !str_contains($config['GOOGLE_CLIENT_ID'], '.apps.googleusercontent.com')) {
            return ['success' => false, 'message' => 'Google Client ID có vẻ không đúng định dạng.'];
        }

        if (isset($config['FACEBOOK_CLIENT_ID']) && !is_numeric($config['FACEBOOK_CLIENT_ID'])) {
            return ['success' => false, 'message' => 'Facebook App ID phải là một dãy số.'];
        }

        return ['success' => true, 'message' => 'Định dạng cấu hình hợp lệ!'];
    }
}