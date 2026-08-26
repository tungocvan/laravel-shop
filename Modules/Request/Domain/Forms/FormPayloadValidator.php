<?php

namespace Modules\Request\Domain\Forms;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Request\Domain\Enums\AttachmentScanStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;
use Modules\Role\Contracts\RoleDirectory;
use Modules\User\Contracts\UserDirectory;

final class FormPayloadValidator
{
    public function __construct(
        private readonly FormPayloadNormalizer $normalizer,
        private readonly FormDefaultValueResolver $defaults,
        private readonly VisibilityRuleEvaluator $visibility,
        private readonly UserDirectory $users,
        private readonly RoleDirectory $roles,
    ) {}

    public function validate(array $schema, array $input, bool $forSubmit = false, ?InternalRequest $request = null): array
    {
        if (strlen(json_encode($input, JSON_THROW_ON_ERROR)) > (int) config('request.forms.max_payload_bytes', 524288)) {
            return ['payload' => [], 'display' => [], 'errors' => ['payload' => ['payload_too_large']]];
        }

        $fields = collect((array) ($schema['sections'] ?? []))
            ->flatMap(fn (mixed $section): array => is_array($section) ? (array) ($section['fields'] ?? []) : [])
            ->filter(fn (mixed $field): bool => is_array($field) && is_string($field['key'] ?? null))
            ->keyBy('key');
        $errors = [];
        $normalized = [];

        foreach (array_keys($input) as $key) {
            if (! $fields->has($key)) {
                $errors["payload.$key"][] = 'unknown_field';
            }
        }

        foreach ($fields as $key => $field) {
            $type = (string) ($field['type'] ?? '');
            if (! $this->visibility->valid($field['visible_when'] ?? null)) {
                $errors["payload.$key"][] = 'visibility_rule_invalid';

                continue;
            }
            if ($type === 'computed_display' || ! $this->visibility->visible($field['visible_when'] ?? null, $input)) {
                continue;
            }

            $present = array_key_exists($key, $input);
            $value = $present ? $this->normalizer->normalizeValue($type, $input[$key], $field) : $this->defaults->value($field);
            $blank = $value === null || $value === '' || $value === [];
            if ($forSubmit && ($field['required'] ?? false) === true && $blank) {
                $errors["payload.$key"][] = 'required';

                continue;
            }
            if ($blank) {
                continue;
            }

            $fieldErrors = $this->valueErrors($type, $value, $field, $request);
            if ($fieldErrors !== []) {
                $errors["payload.$key"] = $fieldErrors;

                continue;
            }
            $normalized[$key] = $value;
        }

        ksort($normalized);

        return ['payload' => $normalized, 'display' => $normalized, 'errors' => $errors];
    }

    private function valueErrors(string $type, mixed $value, array $field, ?InternalRequest $request): array
    {
        $validation = is_array($field['validation'] ?? null) ? $field['validation'] : [];
        $errors = [];

        if (in_array($type, ['text', 'textarea', 'select'], true)) {
            if (! is_string($value)) {
                return ['string_required'];
            }
            $max = min((int) ($validation['max_length'] ?? config('request.forms.string_max', 10000)), (int) config('request.forms.string_max', 10000));
            if (mb_strlen($value) > $max || mb_strlen($value) < (int) ($validation['min_length'] ?? 0)) {
                $errors[] = 'invalid_length';
            }
            if ($type === 'select' && ! $this->optionKeys($field)->contains($value)) {
                $errors[] = 'invalid_option';
            }
        } elseif ($type === 'integer') {
            if (! is_int($value) || (isset($validation['min']) && $value < $validation['min']) || (isset($validation['max']) && $value > $validation['max'])) {
                $errors[] = 'invalid_integer';
            }
        } elseif ($type === 'decimal') {
            if (! is_string($value) || ! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
                $errors[] = 'invalid_decimal';
            }
        } elseif ($type === 'currency') {
            if (! is_array($value) || array_keys($value) !== ['amount', 'currency'] || ! preg_match('/^-?\d+(?:\.\d+)?$/', (string) ($value['amount'] ?? '')) || ! preg_match('/^[A-Z]{3}$/', (string) ($value['currency'] ?? ''))) {
                $errors[] = 'invalid_currency';
            }
        } elseif ($type === 'date') {
            $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
            if (! $date || $date->format('Y-m-d') !== $value) {
                $errors[] = 'invalid_date';
            }
        } elseif ($type === 'datetime') {
            if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value)) {
                $errors[] = 'invalid_datetime';
            }
        } elseif ($type === 'boolean' && ! is_bool($value)) {
            $errors[] = 'invalid_boolean';
        } elseif ($type === 'multiselect') {
            if (! is_array($value) || collect($value)->contains(fn (mixed $item): bool => ! is_string($item) || ! $this->optionKeys($field)->contains($item))) {
                $errors[] = 'invalid_options';
            }
        } elseif ($type === 'user' && (! is_int($value) || $this->users->findActive($value) === null)) {
            $errors[] = 'user_unavailable';
        } elseif ($type === 'role' && (! is_int($value) || $this->roles->findAdminRole($value) === null)) {
            $errors[] = 'role_unavailable';
        } elseif ($type === 'attachment') {
            if (! is_array($value) || collect($value)->contains(fn (mixed $id): bool => ! is_string($id) || ! Str::isUlid($id))) {
                $errors[] = 'invalid_attachment_reference';
            } elseif ($value !== [] && ($request === null || RequestAttachment::query()->where('request_instance_id', $request->id)->where('payload_field_key', $field['key'])->where('uploaded_by', $request->requester_id)->where('scan_status', AttachmentScanStatus::Clean)->whereNull('removed_at')->whereIn('public_id', $value)->count() !== count($value))) {
                $errors[] = 'attachment_not_owned_or_clean';
            }
        }

        return $errors;
    }

    private function optionKeys(array $field)
    {
        return collect((array) ($field['options'] ?? []))->map(fn (mixed $option): mixed => is_array($option) ? ($option['key'] ?? null) : $option)->filter(fn (mixed $key): bool => is_string($key));
    }
}
