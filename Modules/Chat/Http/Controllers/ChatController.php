<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;

class ChatController extends Controller
{
    public function internalChat()
    {
        // Trả về view layout admin, bên trong sẽ chứa Livewire component
        return view('Chat::pages.chat.index');
    }
    public function chat()
    {
        // Trả về view layout admin, bên trong sẽ chứa Livewire component
        return view('Chat::chat');
    }
}