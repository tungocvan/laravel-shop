<?php

namespace Modules\Request\Domain\Enums;

enum StageMode: string
{
    case Single = 'single';
    case ParallelAll = 'parallel_all';
    case ParallelAny = 'parallel_any';
}
