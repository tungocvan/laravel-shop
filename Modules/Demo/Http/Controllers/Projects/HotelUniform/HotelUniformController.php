<?php

namespace Modules\Demo\Http\Controllers\Projects\HotelUniform;

use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;

final class HotelUniformController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.hotel-uniform.index');
    }
}
