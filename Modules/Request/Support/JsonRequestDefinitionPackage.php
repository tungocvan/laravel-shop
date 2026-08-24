<?php

namespace Modules\Request\Support;

use JsonException;
use Modules\Request\Contracts\RequestDefinitionPackage;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Domain\Forms\FormSchemaValidator;
use Modules\Request\Models\RequestTypeVersion;
use RuntimeException;

final readonly class JsonRequestDefinitionPackage implements RequestDefinitionPackage
{
    private const PACKAGE_VERSION = 1;

    private const MAX_BYTES = 262144;

    private const TOP_LEVEL_KEYS = ['package_version', 'source', 'definition', 'required_mappings', 'signature', 'checksum'];

    private const FORBIDDEN_KEYS = [
        'request_instances',
        'request_payload_revisions',
        'request_runs',
        'request_tasks',
        'request_decisions',
        'request_comments',
        'request_attachments',
        'request_audit_events',
        'request_outbox_messages',
        'request_export_jobs',
        'request_notification_deliveries',
    ];

    public function __construct(
        private DefinitionCanonicalizer $canonicalizer,
        private FormSchemaValidator $schemas,
    ) {}

    public function export(RequestTypeVersion $version): array
    {
        $version->loadMissing(['type.group', 'audiences', 'stages']);
        $mappings = [];

        $audiences = $version->audiences->values()->map(function ($audience) use (&$mappings): array {
            $kind = $audience->actor_type->value;
            $ref = $this->mappingRef($kind, (int) $audience->actor_id);
            $mappings[$ref] = ['ref' => $ref, 'kind' => $kind, 'source_id' => (int) $audience->actor_id];

            return [
                'actor_type' => $kind,
                'actor_ref' => $ref,
                'capability' => $audience->capability->value,
            ];
        })->all();

        $stages = $version->stages->values()->map(function ($stage) use (&$mappings): array {
            $config = (array) $stage->resolver_config_json;

            if ($stage->resolver_key === 'fixed_users') {
                $refs = [];
                foreach ((array) ($config['user_ids'] ?? []) as $sourceId) {
                    $sourceId = (int) $sourceId;
                    if ($sourceId < 1) {
                        continue;
                    }
                    $ref = $this->mappingRef('user', $sourceId);
                    $mappings[$ref] = ['ref' => $ref, 'kind' => 'user', 'source_id' => $sourceId];
                    $refs[] = $ref;
                }
                unset($config['user_ids']);
                $config['user_refs'] = array_values(array_unique($refs));
            }

            if ($stage->resolver_key === 'role_members' && (int) ($config['role_id'] ?? 0) > 0) {
                $sourceId = (int) $config['role_id'];
                $ref = $this->mappingRef('role', $sourceId);
                $mappings[$ref] = ['ref' => $ref, 'kind' => 'role', 'source_id' => $sourceId];
                unset($config['role_id']);
                $config['role_ref'] = $ref;
            }

            return [
                'stage_key' => $stage->stage_key,
                'name' => $stage->name,
                'position' => (int) $stage->position,
                'mode' => $stage->mode->value,
                'resolver_key' => $stage->resolver_key,
                'resolver_config' => $config,
                'instructions' => $stage->instructions,
                'allow_reassignment' => (bool) $stage->allow_reassignment,
            ];
        })->all();

        $payload = [
            'package_version' => self::PACKAGE_VERSION,
            'source' => [
                'type_code' => $version->type->code,
                'type_name' => $version->type->name,
                'group_code' => $version->type->group?->code,
                'version_number' => (int) $version->version_number,
                'schema_version' => (int) $version->schema_version,
                'exported_at' => now()->toIso8601String(),
            ],
            'definition' => [
                'title' => $version->title,
                'description' => $version->description,
                'requester_guidance' => $version->requester_guidance,
                'form_schema' => (array) $version->form_schema_json,
                'policy' => (array) $version->policy_json,
                'presentation' => (array) $version->presentation_json,
                'audiences' => $audiences,
                'stages' => $stages,
            ],
            'required_mappings' => array_values($mappings),
            'signature' => null,
        ];

        $payload['checksum'] = $this->canonicalizer->checksum($payload);

        return $payload;
    }

    public function encode(array $package): string
    {
        return json_encode($package, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function decode(string $json): array
    {
        if (strlen($json) > self::MAX_BYTES) {
            throw new RuntimeException('REQUEST_DEFINITION_PACKAGE_TOO_LARGE');
        }

        try {
            $package = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('REQUEST_DEFINITION_PACKAGE_INVALID_JSON', previous: $exception);
        }

        if (is_array($package) === false || array_is_list($package)) {
            throw new RuntimeException('REQUEST_DEFINITION_PACKAGE_OBJECT_REQUIRED');
        }

        return $package;
    }

    public function validate(array $package): array
    {
        $errors = [];
        $unknown = array_values(array_diff(array_keys($package), self::TOP_LEVEL_KEYS));
        if ($unknown !== []) {
            $errors['package'][] = 'unknown_keys:'.implode(',', $unknown);
        }

        if (($package['package_version'] ?? null) !== self::PACKAGE_VERSION) {
            $errors['package_version'][] = 'unsupported_package_version';
        }

        $checksum = (string) ($package['checksum'] ?? '');
        $unsigned = $package;
        unset($unsigned['checksum']);
        if (preg_match('/^[a-f0-9]{64}$/', $checksum) !== 1 || hash_equals($this->canonicalizer->checksum($unsigned), $checksum) === false) {
            $errors['checksum'][] = 'checksum_mismatch';
        }

        if ($this->containsForbiddenKey($package)) {
            $errors['package'][] = 'runtime_data_forbidden';
        }

        $definition = $package['definition'] ?? null;
        if (is_array($definition) === false || array_is_list($definition)) {
            return $errors + ['definition' => ['definition_required']];
        }

        if (is_string($definition['title'] ?? null) === false || trim((string) $definition['title']) === '') {
            $errors['definition.title'][] = 'title_required';
        }

        $schema = $definition['form_schema'] ?? null;
        if (is_array($schema) === false) {
            $errors['definition.form_schema'][] = 'form_schema_required';
        } else {
            foreach ($this->schemas->errors($schema) as $path => $messages) {
                $errors['definition.'.$path] = $messages;
            }
        }

        $required = [];
        foreach ((array) ($package['required_mappings'] ?? []) as $index => $mapping) {
            $ref = is_array($mapping) ? (string) ($mapping['ref'] ?? '') : '';
            $kind = is_array($mapping) ? (string) ($mapping['kind'] ?? '') : '';
            $sourceId = is_array($mapping) ? (int) ($mapping['source_id'] ?? 0) : 0;
            if (preg_match('/^(user|role):[1-9][0-9]*$/', $ref) !== 1 || in_array($kind, ['user', 'role'], true) === false || $sourceId < 1 || $ref !== $this->mappingRef($kind, $sourceId)) {
                $errors['required_mappings.'.$index][] = 'invalid_mapping_placeholder';
                continue;
            }
            $required[$ref] = $kind;
        }

        foreach ((array) ($definition['audiences'] ?? []) as $index => $audience) {
            $ref = is_array($audience) ? (string) ($audience['actor_ref'] ?? '') : '';
            $kind = is_array($audience) ? (string) ($audience['actor_type'] ?? '') : '';
            if (($required[$ref] ?? null) !== $kind) {
                $errors['definition.audiences.'.$index][] = 'mapping_required';
            }
        }

        foreach ((array) ($definition['stages'] ?? []) as $index => $stage) {
            if (is_array($stage) === false) {
                $errors['definition.stages.'.$index][] = 'invalid_stage';
                continue;
            }
            $config = (array) ($stage['resolver_config'] ?? []);
            foreach ((array) ($config['user_refs'] ?? []) as $ref) {
                if (($required[(string) $ref] ?? null) !== 'user') {
                    $errors['definition.stages.'.$index][] = 'user_mapping_required';
                }
            }
            if (isset($config['role_ref']) && ($required[(string) $config['role_ref']] ?? null) !== 'role') {
                $errors['definition.stages.'.$index][] = 'role_mapping_required';
            }
        }

        return $errors;
    }

    private function mappingRef(string $kind, int $sourceId): string
    {
        return $kind.':'.$sourceId;
    }

    private function containsForbiddenKey(mixed $value): bool
    {
        if (is_array($value) === false) {
            return false;
        }

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array($key, self::FORBIDDEN_KEYS, true)) {
                return true;
            }
            if ($this->containsForbiddenKey($item)) {
                return true;
            }
        }

        return false;
    }
}
