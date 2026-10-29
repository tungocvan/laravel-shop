<?php

namespace Modules\Demo\Http\Controllers\Projects\RentalRoom;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class RentalRoomManagementController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.rental-room.index');
    }
}
