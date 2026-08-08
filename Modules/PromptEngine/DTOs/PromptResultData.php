<?php declare(strict_types=1);
namespace Modules\PromptEngine\DTOs;
use JsonSerializable;
final class PromptResultData implements JsonSerializable { public function __construct(public array $input, public array $analysis, public array $designPlan, public array $explanationVi, public array $imagePrompt, public ?array $image=null, public array $qualityReport=[]) {} public function toArray(bool $debug=false): array { $out=['input'=>$this->input,'analysis'=>$this->analysis,'design_plan'=>$this->designPlan,'explanation_vi'=>$this->explanationVi,'image_prompt'=>$this->imagePrompt]; if($this->image!==null)$out['image']=$this->image; if($debug)$out['quality_report']=$this->qualityReport; return $out; } public function jsonSerialize(): array { return $this->toArray(); } }
