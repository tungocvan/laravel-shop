<?php declare(strict_types=1);
namespace Modules\PromptEngine\DTOs;
final class DesignPlanData { public function __construct(private array $data) {} public function toArray(): array { return $this->data; } }
