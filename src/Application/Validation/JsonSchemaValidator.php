<?php

declare(strict_types=1);

namespace App\Application\Validation;

class JsonSchemaValidator
{
    /**
     * Validate a data array against a very small subset of JSON Schema.
     * Supported keywords: required, properties.type (string|integer|number), properties.enum, properties.minimum
     * Returns an associative array of errors keyed by property name.
     *
     * @param array $data
     * @param array $schema
     * @return array<string,string>
     */
    public function validate(array $data, array $schema): array
    {
        $errors = [];

        // required
        $required = $schema['required'] ?? [];
        foreach ($required as $prop) {
            if (!array_key_exists($prop, $data) || $data[$prop] === null || $data[$prop] === '') {
                $errors[$prop] = 'is required';
            }
        }

        $props = $schema['properties'] ?? [];
        foreach ($props as $name => $rules) {
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];

            // type
            if (isset($rules['type'])) {
                $type = $rules['type'];
                $ok = match ($type) {
                    'string' => is_string($value),
                    'integer' => is_int($value),
                    'number' => is_int($value) || is_float($value),
                    default => true,
                };

                if (!$ok) {
                    $errors[$name] = "must be of type {$type}";
                    continue;
                }
            }

            // enum
            if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                $errors[$name] = 'has invalid value';
                continue;
            }

            // minimum (numeric)
            if (isset($rules['minimum']) && (is_int($value) || is_float($value))) {
                if ($value < $rules['minimum']) {
                    $errors[$name] = "must be >= {$rules['minimum']}";
                    continue;
                }
            }
        }

        return $errors;
    }
}
