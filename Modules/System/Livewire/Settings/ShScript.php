<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\File;

class ShScript extends Component
{
    public array $scripts = []; // Khai báo kiểu dữ liệu là array
    public ?string $selectedScript = null;
    public string $scriptContent = "#!/bin/bash\n";
    public string $errorMessage = '';
    public string $newScriptName = '';
    public string $executionOutput = ''; // Biến lưu trữ kết quả thực thi

    public function mount()
    {
        $this->loadScripts();
    }

    public function loadScripts()
    {
        $shDirectory = app_path('sh');

        // Kiểm tra xem thư mục có tồn tại không, nếu không thì tạo
        if (!File::exists($shDirectory)) {
            File::makeDirectory($shDirectory, 0755, true);
        }

        // Chỉ lấy tên file dưới dạng chuỗi
        $this->scripts = array_map(function ($file) {
            return $file->getFilename(); // Lấy tên file
        }, File::files($shDirectory));
    }

    public function selectScript(string $script)
    {
        $this->selectedScript = $script;
        $scriptPath = app_path("sh/{$script}");
        $this->newScriptName = $script;
        try {
            // Đọc nội dung file
            $this->scriptContent = File::get($scriptPath);
            $this->errorMessage = ''; // Reset thông báo lỗi
        } catch (\Exception $e) {
            // Nếu có lỗi xảy ra, lưu thông báo lỗi
            //$this->errorMessage = 'Không thể đọc nội dung của file: ' . $e->getMessage();
            $this->scriptContent = ''; // Reset nội dung
        }
    }

    public function saveScript()
    {
        if (empty(trim($this->newScriptName))) {
            $this->errorMessage = 'Tên file không được để trống.';
            return;
        }

        // Kiểm tra tên script có tồn tại hay không
        $shDirectory = app_path('sh');
        $filePath = "$shDirectory/$this->newScriptName";

        // Nếu script đã tồn tại, thông báo lỗi
        if (File::exists($filePath) && $this->selectedScript !== "$this->newScriptName") {
            $this->errorMessage = 'Script đã tồn tại. Vui lòng chọn tên khác.';
            return;
        }

           

        // Lưu nội dung file
        File::put($filePath, $this->scriptContent);

        // Cấp quyền thực thi cho file
        chmod($filePath, 0755);

        $this->loadScripts(); // Tải lại danh sách scripts
        $this->errorMessage = ''; // Reset thông báo lỗi
        // $this->newScriptName = ''; // Reset tên file
        // $this->scriptContent = ''; // Reset nội dung file
}

public function executeScript()
{
    // Kiểm tra xem đã chọn script hay chưa
    if (!$this->selectedScript) {
        $this->errorMessage = 'Vui lòng chọn một script để thực hiện.';
        return;
    }

    // Xác định đường dẫn đến file script
    $scriptPath = app_path("sh/{$this->selectedScript}");

    // Kiểm tra sự tồn tại của file script
    if (!File::exists($scriptPath)) {
        $this->errorMessage = 'File script không tồn tại.';
        return;
    }

    // Kiểm tra quyền thực thi của file
    if (!is_executable($scriptPath)) {
        $this->errorMessage = 'File script không có quyền thực thi.';
        return;
    }

    // Thực hiện file .sh và lưu kết quả vào biến
    $output = shell_exec("bash {$scriptPath}");

    // Kiểm tra kết quả và gán giá trị cho executionOutput
    if ($output !== null) {
        $this->executionOutput = $output; // Gán kết quả nếu không phải null
    } else {
        $this->executionOutput = ''; // Gán chuỗi rỗng nếu output là null
        $this->errorMessage = 'Có lỗi xảy ra khi thực hiện script.';
    }

    // Reset thông báo lỗi nếu không có lỗi
    if (empty($this->errorMessage)) {
        $this->errorMessage = ''; // Reset thông báo lỗi
    }
}

    public function deleteScript()
    {
        if (!$this->selectedScript) {
            $this->errorMessage = 'Vui lòng chọn một script để xóa.';
            return;
        } 

        $scriptPath = app_path("sh/{$this->selectedScript}");

        // Kiểm tra xem file có tồn tại không
        if (File::exists($scriptPath)) {
            File::delete($scriptPath); // Xóa file
            $this->loadScripts(); // Tải lại danh sách scripts
            $this->errorMessage = ''; // Reset thông báo lỗi
            $this->scriptContent = ''; // Reset nội dung
            $this->selectedScript = null; // Reset script đã chọn
        } else {
            $this->errorMessage = 'File script không tồn tại.';
        }
    }

    public function render()
    {
        return view('System::livewire.settings.sh-script');
    }
}