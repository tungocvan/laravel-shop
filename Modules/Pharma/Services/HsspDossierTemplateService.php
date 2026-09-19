<?php

namespace Modules\Pharma\Services;

use App\Dossiers\Models\DossierTemplate;
use Illuminate\Support\Facades\DB;

class HsspDossierTemplateService
{
    public const CODE = 'pharma-product-dossier';

    public function get(): DossierTemplate
    {
        return DB::transaction(function (): DossierTemplate {
            $template = DossierTemplate::query()->firstOrCreate(
                ['code' => self::CODE],
                ['name' => 'MỤC LỤC BỘ HỒ SƠ SẢN PHẨM', 'version' => 1, 'is_active' => true]
            );

            $definitions = [
                ['code' => 'gmp', 'name' => 'Giấy chứng nhận Thực hành tốt sản xuất thuốc (GMP)', 'sort_order' => 1, 'is_required' => true, 'metadata_schema' => [
                    ['key' => 'document_number', 'label' => 'Số GMP', 'type' => 'text', 'required' => false],
                    ['key' => 'effective_from', 'label' => 'Hiệu lực từ', 'type' => 'date', 'required' => false],
                    ['key' => 'effective_to', 'label' => 'Hiệu lực GMP đến', 'type' => 'date', 'required' => true],
                ]],
                ['code' => 'registration', 'name' => 'Quyết định cấp Giấy đăng ký lưu hành thuốc', 'sort_order' => 2, 'is_required' => true, 'metadata_schema' => [
                    ['key' => 'document_number', 'label' => 'Số đăng ký', 'type' => 'text', 'required' => false],
                    ['key' => 'effective_from', 'label' => 'Hiệu lực từ', 'type' => 'date', 'required' => false],
                    ['key' => 'effective_to', 'label' => 'Hiệu lực số đăng ký đến', 'type' => 'date', 'required' => true],
                ]],
                ['code' => 'instructions', 'name' => 'Tờ hướng dẫn sử dụng thuốc (HDSD)', 'sort_order' => 3, 'is_required' => true, 'metadata_schema' => []],
                ['code' => 'label', 'name' => 'Mẫu nhãn sản phẩm', 'sort_order' => 4, 'is_required' => true, 'metadata_schema' => []],
                ['code' => 'changes', 'name' => 'Các thông tin hồ sơ thay đổi sản phẩm (nếu có)', 'sort_order' => 5, 'is_required' => false, 'metadata_schema' => [
                    ['key' => 'note', 'label' => 'Nội dung thay đổi / gia hạn', 'type' => 'textarea', 'required' => false],
                    ['key' => 'effective_to', 'label' => 'Hiệu lực đến', 'type' => 'date', 'required' => false],
                ]],
            ];

            foreach ($definitions as $definition) {
                $template->items()->updateOrCreate(
                    ['code' => $definition['code']],
                    $definition + ['allows_upload' => true, 'allows_multiple_files' => true]
                );
            }

            return $template->load('items');
        });
    }
}
