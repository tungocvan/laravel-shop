<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Services\WebsiteDesignService;
use Modules\Website\Services\WebsiteDesignThemeService;

class WebsiteDesignThemeSeeder extends Seeder
{
    public function run(): void
    {
        $design = app(WebsiteDesignService::class);
        $themes = app(WebsiteDesignThemeService::class);
        $defaults = $design->resolve();

        $classic = $defaults;
        $classic['colors'] = array_replace($classic['colors'], [
            'primary' => '#2563eb',
            'secondary' => '#4f46e5',
            'background' => '#f8fafc',
            'surface' => '#ffffff',
            'text' => '#0f172a',
            'muted' => '#64748b',
            'border' => '#e2e8f0',
        ]);
        $classic['layout']['default_container'] = 'standard';

        $commerce = $defaults;
        $commerce['colors'] = array_replace($commerce['colors'], [
            'primary' => '#059669',
            'secondary' => '#0f766e',
            'background' => '#f8fafc',
            'surface' => '#ffffff',
            'text' => '#111827',
            'muted' => '#6b7280',
            'border' => '#d1d5db',
            'success' => '#059669',
        ]);
        $commerce['typography']['base_font_size'] = '16px';
        $commerce['layout']['default_container'] = 'wide';
        $commerce['layout']['radius'] = ['sm' => '0.5rem', 'md' => '0.75rem', 'lg' => '1rem', 'xl' => '1.25rem'];

        $premium = $defaults;
        $premium['colors'] = array_replace($premium['colors'], [
            'primary' => '#7c3aed',
            'secondary' => '#a16207',
            'background' => '#fafaf9',
            'surface' => '#ffffff',
            'text' => '#1c1917',
            'muted' => '#78716c',
            'border' => '#e7e5e4',
            'warning' => '#a16207',
        ]);
        $premium['typography']['base_font_size'] = '17px';
        $premium['layout']['default_container'] = 'standard';
        $premium['layout']['container_width']['standard'] = '1240px';
        $premium['layout']['radius'] = ['sm' => '0.25rem', 'md' => '0.5rem', 'lg' => '0.75rem', 'xl' => '1rem'];

        $themes->save('Classic Blue', $classic, 'demo-classic-blue');
        $themes->save('Commerce Emerald', $commerce, 'demo-commerce-emerald');
        $themes->save('Premium Violet', $premium, 'demo-premium-violet');

        $this->command?->info('✅ WebsiteDesignThemeSeeder: Đã tạo 03 Website design themes demo. Custom themes hiện có được giữ nguyên.');
    }
}
