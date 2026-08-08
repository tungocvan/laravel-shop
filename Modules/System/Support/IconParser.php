<?php

namespace Modules\System\Support;

class IconParser
{
    public static function parse(?string $icon): ?string
    {
        if (!$icon) return null;

        // 👉 nếu là raw path (không chứa <svg)
        if (!str_contains($icon, '<svg')) {
            return $icon;
        }

        // 👉 extract d=""
        preg_match('/d="([^"]+)"/', $icon, $matches);

        return $matches[1] ?? null;
    }
}