<?php
namespace Modules\Admin\Services\Env;

use Illuminate\Support\Facades\File;

class EnvManagerService
{
    protected string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env'); //
    }
    public function exportToEnvironment(string $suffix): bool
    {
        $targetPath = base_path(".env.{$suffix}");
      
        // Copy file .env hiện tại sang file đích
        if (File::exists($this->envPath)) {
            return File::copy($this->envPath, $targetPath);
        }
        
        return false;
    }
    public function getValues(): array
    {
        if (!File::exists($this->envPath)) {
            return [];
        }

        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $settings = [];

        foreach ($lines as $line) {
            // Bỏ qua các dòng comment
            if (str_starts_with(trim($line), '#')) continue;

            // Tách Key và Value
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $settings[trim($key)] = $this->unquoteValue(trim($value));
            }
        }

        return $settings;
    }
    /**
     * Cập nhật các biến môi trường một cách an toàn
     */
    public function update(array $data): bool
    {
        if (!File::exists($this->envPath)) return false;

        $content = File::get($this->envPath);

        foreach ($data as $key => $value) {
            $key = strtoupper($key);
            $formattedValue = $this->formatValue($value);

            // Regex tìm chính xác Key ở đầu dòng
            $pattern = "/^{$key}=.*/m";
            $newLine = "{$key}={$formattedValue}";

            if (preg_match($pattern, $content)) {
                // Nếu tồn tại -> Thay thế
                $content = preg_replace($pattern, $newLine, $content);
            } else {
                // Nếu không -> Thêm mới vào cuối file
                $content .= "\n{$newLine}";
            }
        }

        return (bool) File::put($this->envPath, $content);
    }

    /**
     * Format giá trị: Thêm ngoặc kép nếu có khoảng trắng hoặc ký tự đặc biệt
     */

    protected function unquoteValue(string $value): string
    {
        return trim($value, '"\' ');
    }

    /**
     * Xử lý dấu ngoặc khi ghi file (Helper)
     */
    protected function formatValue(string $value): string
    {
        // Nếu chứa khoảng trắng hoặc ký tự đặc biệt, bọc trong dấu ngoặc kép
        if (preg_match('/\s/', $value) || str_contains($value, '#')) {
            return '"' . addslashes($value) . '"';
        }
        return $value;
    }
}
