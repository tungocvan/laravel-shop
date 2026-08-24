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
            }
        }
        if ($fieldCount > (int) config('request.forms.max_fields', 200)) {
            $errors['form_schema_json.sections'][] = 'field_limit_exceeded';
        }

        return $errors;
    }
}
