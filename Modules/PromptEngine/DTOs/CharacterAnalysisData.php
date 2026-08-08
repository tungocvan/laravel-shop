<?php declare(strict_types=1);
namespace Modules\PromptEngine\DTOs;
final class CharacterAnalysisData { public function __construct(private array $data) {} public function toArray(): array { return $this->data; } public function get(string $key, mixed $default=null): mixed { return $this->data[$key] ?? $default; } }
