<?php
namespace Modules\Admin\Http\Controllers;
use App\Http\Controllers\Controller;

class ChatController extends Controller
{
    public function index()
    {
        // Trả về view layout admin, bên trong sẽ chứa Livewire component
        return view('Admin::pages.chat.index');
    }
}
