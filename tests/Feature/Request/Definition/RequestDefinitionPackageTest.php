<?php

namespace Tests\Feature\Request\Definition;

use Illuminate\Support\Facades\DB;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\DryRunRequestDefinitionPackage;
use Modules\Request\Application\Services\ImportRequestDefinitionPackage;
use Modules\Request\Application\Services\PublishTypeVersion;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Contracts\RequestDefinitionPackage;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;

class RequestDefinitionPackageTest extends RequestDefinitionTestCase
{
    public function test_export_replaces_identity_ids_with_mapping_placeholders_and_valid_checksum(): void
    {
        [$sourceUser, , $published] = $this->publishedType('PACKAGE_EXPORT');
        $packages = app(RequestDefinitionPackage::class);
        $package = $packages->export($published);

        $this->assertSame([], $packages->validate($package));
        $this->assertSame(1, $package['package_version']);
        $this->assertSame('user:'.$sourceUser, $package['definition']['audiences'][0]['actor_ref']);
        $this->assertArrayNotHasKey('actor_id', $package['definition']['audiences'][0]);
        $this->assertSame(['user:'.$sourceUser], $package['definition']['stages'][0]['resolver_config']['user_refs']);
        $this->assertArrayNotHasKey('user_ids', $package['definition']['stages'][0]['resolver_config']);
        $this->assertSame(64, strlen($package['checksum']));

        $encoded = $packages->encode($package);
        $this->assertStringNotContainsString('request_instances', $encoded);
        $this->assertStringNotContainsString('request_payload_revisions', $encoded);
    }

    public function test_tampering_and_runtime_data_are_rejected(): void
    {
        [, , $published] = $this->publishedType('PACKAGE_TAMPER');
        $packages = app(RequestDefinitionPackage::class);
        $package = $packages->export($published);
        $package['definition']['title'] = 'Tampered';

        $errors = $packages->validate($package);
        $this->assertArrayHasKey('checksum', $errors);

        $package = $packages->export($published);
        $package['request_instances'] = [['id' => 1]];
        $errors = $packages->validate($package);
        $this->assertContains('runtime_data_forbidden', $errors['package']);
    }

    public function test_dry_run_requires_explicit_active_local_mapping_and_reports_diff_without_writing(): void
    {
        [$sourceUser, $type, $published] = $this->publishedType('PACKAGE_PREVIEW');
        $localUser = $this->user('Mapped Local User');
        $package = app(RequestDefinitionPackage::class)->export($published);
        $package['definition']['title'] = 'Imported Vietnamese Definition';
        $this->resign($package);

        $beforeVersions = $type->versions()->count();
        $preview = app(DryRunRequestDefinitionPackage::class)->handle($type->refresh(), $package, ['user:'.$sourceUser => $localUser]);

        $this->assertTrue($preview['valid']);
        $this->assertContains('title', $preview['changed_sections']);
        $this->assertSame($localUser, $preview['resolved_definition']['audiences'][0]['actor_id']);
        $this->assertSame([$localUser], $preview['resolved_definition']['stages'][0]['resolver_config_json']['user_ids']);
        $this->assertSame($beforeVersions, $type->versions()->count(), 'Dry-run must not write a draft.');
    }

    public function test_import_creates_draft_only_through_definition_services(): void
    {
        [$sourceUser, $type, $published] = $this->publishedType('PACKAGE_IMPORT');
        $localUser = $this->user('Import Local User');
        $package = app(RequestDefinitionPackage::class)->export($published);
        $package['definition']['title'] = 'Định nghĩa nhập thử';
        $this->resign($package);

        $runtimeBefore = DB::table('request_instances')->count();
        $draft = app(ImportRequestDefinitionPackage::class)->handle(
            $type->refresh(),
            $package,
            ['user:'.$sourceUser => $localUser],
            $localUser,
        );

        $this->assertSame(RequestTypeVersionStatus::Draft, $draft->status);
        $this->assertSame('Định nghĩa nhập thử', $draft->title);
        $this->assertSame($draft->id, $type->refresh()->active_draft_version_id);
        $this->assertSame($published->id, $type->current_published_version_id);
        $this->assertSame($runtimeBefore, DB::table('request_instances')->count());
        $this->assertDatabaseMissing('request_type_versions', ['id' => $draft->id, 'status' => 'published']);
    }

    private function publishedType(string $code): array
    {
        $actorId = $this->user('Source '.$code);
        $group = app(CreateRequestGroup::class)->handle(['code' => 'G_'.$code, 'name' => 'Group'], $actorId);
        $type = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => $code, 'name' => 'Type'], $actorId);
        app(SaveTypeDraft::class)->handle($type, [
            'title' => 'Đề nghị nguồn',
            'form_schema_json' => ['schema_version' => 1, 'sections' => [['key' => 'details', 'fields' => [['key' => 'reason', 'type' => 'textarea']]]]],
            'policy_json' => [],
            'presentation_json' => [],
            'audiences' => [['actor_type' => 'user', 'actor_id' => $actorId, 'capability' => 'create']],
            'stages' => [[
                'stage_key' => 'approval',
                'name' => 'Phê duyệt',
                'position' => 1,
                'mode' => 'single',
                'resolver_key' => 'fixed_users',
                'resolver_config_json' => ['user_ids' => [$actorId]],
                'allow_reassignment' => false,
            ]],
        ], $actorId, 1);
        $published = app(PublishTypeVersion::class)->handle($type->refresh(), $actorId, 2);

        return [$actorId, $type->refresh(), $published];
    }

    private function user(string $name): int
    {
        return (int) DB::table('users')->insertGetId([
            'name' => $name,
            'email' => uniqid().'@example.test',
            'is_active' => true,
            'password' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resign(array &$package): void
    {
        unset($package['checksum']);
        $package['checksum'] = app(DefinitionCanonicalizer::class)->checksum($package);
    }
}
