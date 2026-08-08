<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;

class ArtisanList extends Component
{

    public $artisanCommand;
    public $commandOutput;
    public $errorMessage = '';

    public function executeArtisanCommand()
    {
        // Kiểm tra nếu câu lệnh không được để trống
        if (empty(trim($this->artisanCommand))) {
            $this->errorMessage = 'Câu lệnh không được để trống.';
            return;
        }

        // Thực hiện câu lệnh artisan
        try {
            Artisan::call($this->artisanCommand);
            $this->commandOutput = Artisan::output(); // Lấy đầu ra của câu lệnh
            $this->errorMessage = ''; // Reset thông báo lỗi
        } catch (\Exception $e) {
            $this->errorMessage = 'Có lỗi xảy ra: ' . $e->getMessage();
            $this->commandOutput = '';
        }

        // Reset ô input
        $this->artisanCommand = '';
    }

    public function render()
    {
        return view('System::livewire.settings.artisan-list');
    }
} 