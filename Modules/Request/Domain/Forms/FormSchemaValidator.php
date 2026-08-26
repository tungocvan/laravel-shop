<?php

namespace Modules\Request\Domain\Forms;

final class FormSchemaValidator
{
    public function __construct(private readonly FormFieldRegistry $fields) {}

    public function errors(array $schema): array
    {
        $errors = [];
        if (($schema['schema_version'] ?? null) !== 1) {
            $errors['form_schema_json.schema_version'][] = 'unsupported_schema_version';
        }
        $sections = $schema['sections'] ?? null;
        if (! is_array($sections) || count($sections) > (int) config('request.forms.max_sections', 30)) {
            return $errors + ['form_schema_json.sections' => ['invalid_sections']];
        }
        $keys = [];
        $fieldCount = 0;
        foreach ($sections as $sectionIndex => $section) {
            if (! is_array($section) || ! preg_match('/^[a-z][a-z0-9_]{0,79}$/', (string) ($section['key'] ?? ''))) {
                $errors["form_schema_json.sections.$sectionIndex.key"][] = 'invalid_key';
            }
            foreach ((array) ($section['fields'] ?? []) as $fieldIndex => $field) {
                $fieldCount++;
                $key = (string) ($field['key'] ?? '');
                $path = "form_schema_json.sections.$sectionIndex.fields.$fieldIndex";
                if (! preg_match('/^[a-z][a-z0-9_]{0,79}$/', $key) || isset($keys[$key])) {
                    $errors["$path.key"][] = 'invalid_or_duplicate_key';
                }
                $keys[$key] = true;
                if (! $this->fields->supports((string) ($field['type'] ?? ''))) {
                    $errors["$path.type"][] = 'unsupported_field_type';
                }
                if (array_key_exists('required', $field) && ! is_bool($field['required'])) {
                    $errors["$path.required"][] = 'invalid_required_flag';
                }
                if (isset($field['width']) && ! in_array($field['width'], ['auto', 'full', 'half', 'third'], true)) {
                    $errors["$path.width"][] = 'invalid_field_width';
                }
                if (($field['default'] ?? null) === 'today' && ($field['type'] ?? null) !== 'date') {
                    $errors["$path.default"][] = 'invalid_field_default';
                }
            }
        }
        if ($fieldCount > (int) config('request.forms.max_fields', 200)) {
            $errors['form_schema_json.sections'][] = 'field_limit_exceeded';
        }

        return $errors;
    }
}
