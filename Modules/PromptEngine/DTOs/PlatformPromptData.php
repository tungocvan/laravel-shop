<?php declare(strict_types=1);
namespace Modules\PromptEngine\DTOs;
final class PlatformPromptData { public function __construct(public string $platform, public string $prompt, public string $negativePrompt='', public array $recommendedSettings=[]) {} public function toArray(): array { return ['platform'=>$this->platform,'prompt'=>$this->prompt,'negative_prompt'=>$this->negativePrompt,'recommended_settings'=>$this->recommendedSettings]; } }
