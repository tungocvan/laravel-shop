<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class RentalRoomManagementController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.quan-ly-phong-tro.index');
    }
}
