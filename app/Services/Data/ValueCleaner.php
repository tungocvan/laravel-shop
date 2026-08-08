<?php
namespace App\Services\Data;

class ValueCleaner
{
    public function clean($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return trim(
            preg_replace('/[\x00-\x1F\x7F]/u', '', $value)
        );
    }
}