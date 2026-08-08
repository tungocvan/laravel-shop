<?php

namespace Modules\Administrative\Services;

use Modules\Admin\Models\Setting;

class PublicBrandingService
{
    public function get(): array
    {
        $logoPath = Setting::getValue('site_logo');
        $branding = [
            'logo' => $logoPath
                ? asset('storage/'.$logoPath).'?v='.md5($logoPath)
                : asset('storage/img/logo.png'),
            'name_line_1' => Setting::getValue('site_name_line_1') ?? config('app.school_managing_agency', ''),
            'name_line_2' => Setting::getValue('site_name_line_2') ?? 'CÔNG TY TNHH INAFO VIỆT NAM',
            'description' => Setting::getValue('login_description') ?? config('app.school_login_description', 'Hệ thống quản trị'),
        ];

        if (! config('modules.registry.Admission.enabled', false)) {
            return $branding;
        }

        $serviceClass = 'Modules\\Admission\\Services\\SchoolSettingService';
        if (! class_exists($serviceClass)) {
            return $branding;
        }

        $settings = app($serviceClass)->all();
        $branding['name_line_1'] = $settings['school_managing_agency'] ?? $branding['name_line_1'];
        $branding['name_line_2'] = $settings['school_name'] ?? $branding['name_line_2'];
        $branding['description'] = $settings['school_login_description'] ?? $branding['description'];

        return $branding;
    }
}
