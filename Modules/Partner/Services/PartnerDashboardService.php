<?php

namespace Modules\Partner\Services;

use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;

class PartnerDashboardService
{
    public function metrics(): array
    {
        $recentSince = now()->subDays(30);

        return [
            'total' => Partner::count(),
            'companies' => Partner::where('legal_type', 'company')->count(),
            'hospitals' => Partner::where('legal_type', 'hospital')->count(),
            'suppliers' => Partner::whereJsonContains('partner_types', 'supplier')->count(),
            'customers' => Partner::whereJsonContains('partner_types', 'customer')->count(),
            'active' => Partner::where('status', 'active')->count(),
            'inactive' => Partner::where('status', 'inactive')->count(),
            'with_tax_code' => Partner::whereNotNull('tax_code')->where('tax_code', '!=', '')->count(),
            'missing_tax_code' => Partner::where(fn ($query) => $query->whereNull('tax_code')->orWhere('tax_code', ''))->count(),
            'with_external_source' => Partner::whereHas('sourceReferences')->count(),
            'masothue_references' => PartnerSourceReference::where('source', 'masothue')->count(),
            'recent' => Partner::where('created_at', '>=', $recentSince)->count(),
            'missing_province' => Partner::where(fn ($query) => $query->whereNull('province_code')->orWhere('province_code', ''))->count(),
        ];
    }

    public function topProvinces(int $limit = 8)
    {
        return Partner::query()
            ->selectRaw('province_code, COUNT(*) as total')
            ->whereNotNull('province_code')
            ->where('province_code', '!=', '')
            ->groupBy('province_code')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }
}
