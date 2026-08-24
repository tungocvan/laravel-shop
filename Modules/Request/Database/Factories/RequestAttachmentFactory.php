<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\AttachmentClassification;
use Modules\Request\Domain\Enums\AttachmentScanStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;

class RequestAttachmentFactory extends Factory
{
    protected $model = RequestAttachment::class;

    public function definition(): array
    {
        $name = fake()->uuid().'.pdf';

        return ['request_instance_id' => InternalRequest::factory(), 'uploaded_by' => 1, 'storage_disk' => 'local', 'storage_path' => 'request/attachments/test/'.$name, 'original_filename' => 'document.pdf', 'generated_filename' => $name, 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size_bytes' => 10, 'checksum' => hash('sha256', fake()->uuid()), 'classification' => AttachmentClassification::Internal, 'scan_status' => AttachmentScanStatus::Clean, 'created_at' => now('UTC')];
    }
}
