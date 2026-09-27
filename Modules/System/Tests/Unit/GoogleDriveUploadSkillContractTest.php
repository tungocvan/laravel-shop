<?php

namespace Modules\System\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleDriveUploadSkillContractTest extends TestCase
{
    #[Test]
    public function workflow_and_prompt_guide_require_the_canonical_google_drive_upload_skill(): void
    {
        $workflow = file_get_contents(base_path('docs/GITHUB_COLLABORATION_WORKFLOW.md'));
        $promptGuide = file_get_contents(base_path('docs/GOOGLE_DRIVE_SCHEDULER_PROMPT_GUIDE.md'));
        $skill = file_get_contents(base_path('.codex/skills/google-drive-upload/SKILL.md'));

        $this->assertStringContainsString('.codex/skills/google-drive-upload/SKILL.md', $workflow);
        $this->assertStringContainsString('.codex/skills/google-drive-upload/SKILL.md', $promptGuide);
        $this->assertStringContainsString('GoogleDriveConnectionService', $skill);
        $this->assertStringContainsString('Local → Drive', $skill);
        $this->assertStringContainsString('Drive → Local', $skill);
        $this->assertStringContainsString('không bắt người dùng bấm Save metadata', $skill);
        $this->assertStringContainsString('Storage::disk(...)->exists(...)', $skill);
        $this->assertStringContainsString('Hủy / Quay lại record trước', $skill);
    }

    #[Test]
    public function skill_keeps_system_as_google_drive_infrastructure_owner(): void
    {
        $skill = file_get_contents(base_path('.codex/skills/google-drive-upload/SKILL.md'));

        $this->assertStringContainsString('Modules/System', $skill);
        $this->assertStringContainsString('Không tạo OAuth, access token, refresh token', $skill);
        $this->assertStringContainsString('uploadApplicationFile', $skill);
        $this->assertStringContainsString('downloadApplicationFile', $skill);
        $this->assertStringContainsString('deleteApplicationFile', $skill);
        $this->assertStringContainsString('Laravel-Backup/<Module>/<Domain>/<BusinessKey>/', $skill);
    }
}
