<?php

namespace Modules\Admission\Livewire\Admin;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Models\Setting;
use Modules\Admission\Services\SchoolSettingService;

class SchoolSettingsForm extends Component
{
    use WithFileUploads;

    public string $principal = '';

    public string $school_year = '';

    public string $school_name = '';

    public string $school_managing_agency = '';

    public string $school_login_description = '';

    public array $registration_classes = [];

    public ?string $site_logo = null;

    public ?string $site_favicon = null;

    public mixed $new_logo = null;

    public mixed $new_favicon = null;

    public function mount(SchoolSettingService $settings): void
    {
        $this->fill($settings->all());
        $this->site_logo = Setting::getValue('site_logo');
        $this->site_favicon = Setting::getValue('site_favicon');
    }

    public function save(SchoolSettingService $settings): void
    {
        $this->authorize('manage_admission_settings');

        $this->registration_classes = collect($this->registration_classes)
            ->map(static fn (mixed $class): string => trim((string) $class))
            ->values()
            ->all();

        $validated = $this->validate([
            'principal' => ['required', 'string', 'max:255'],
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}\s*-\s*\d{4}$/'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_managing_agency' => ['required', 'string', 'max:255'],
            'school_login_description' => ['required', 'string', 'max:500'],
            'registration_classes' => ['required', 'array', 'min:1'],
            'registration_classes.*' => ['required', 'string', 'max:255', 'distinct'],
            'new_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'new_favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:1024'],
        ], [
            'school_year.regex' => 'Năm học phải có định dạng 2026-2027.',
            'registration_classes.required' => 'Phải có ít nhất một lớp đăng ký.',
            'registration_classes.min' => 'Phải có ít nhất một lớp đăng ký.',
            'registration_classes.*.required' => 'Tên lớp đăng ký không được để trống.',
            'registration_classes.*.distinct' => 'Tên lớp đăng ký không được trùng nhau.',
        ]);

        $normalizedClasses = collect($validated['registration_classes'])
            ->map(static fn (string $class): string => mb_strtolower($class));

        if ($normalizedClasses->unique()->count() !== $normalizedClasses->count()) {
            $this->addError('registration_classes', 'Tên lớp đăng ký không được trùng nhau.');

            return;
        }

        $settings->save($validated);
        $this->saveImages();
        session()->flash('success', 'Đã cập nhật thông tin nhà trường.');
    }

    public function removeImage(string $type): void
    {
        $this->authorize('manage_admission_settings');

        if ($type === 'logo') {
            $this->deleteStoredImage($this->site_logo);
            Setting::setValue('site_logo', null);
            $this->site_logo = null;
            $this->new_logo = null;
            $this->dispatch('logo-updated', url: asset('storage/img/logo.png'));
        }

        if ($type === 'favicon') {
            $this->deleteStoredImage($this->site_favicon);
            Setting::setValue('site_favicon', null);
            $this->site_favicon = null;
            $this->new_favicon = null;
            $this->dispatch('favicon-updated', url: asset('favicon.ico'), type: 'image/x-icon');
        }

        session()->flash('success', 'Đã xóa hình ảnh.');
    }

    public function addRegistrationClass(): void
    {
        $this->registration_classes[] = '';
    }

    public function removeRegistrationClass(int $index): void
    {
        if (! array_key_exists($index, $this->registration_classes)) {
            return;
        }

        unset($this->registration_classes[$index]);
        $this->registration_classes = array_values($this->registration_classes);
    }

    public function render()
    {
        return view('Admission::livewire.admin.school-settings-form');
    }

    private function saveImages(): void
    {
        if ($this->new_logo) {
            $oldLogo = $this->site_logo;
            $path = $this->new_logo->store('settings', 'public');

            Setting::setValue('site_logo', $path);
            $this->site_logo = $path;
            $this->new_logo = null;
            $this->deleteStoredImage($oldLogo);

            $this->dispatch(
                'logo-updated',
                url: asset('storage/'.$path).'?v='.md5($path.microtime(true)),
            );
        }

        if ($this->new_favicon) {
            $oldFavicon = $this->site_favicon;
            $path = $this->new_favicon->store('settings', 'public');

            Setting::setValue('site_favicon', $path);
            $this->site_favicon = $path;
            $this->new_favicon = null;
            $this->deleteStoredImage($oldFavicon);

            $this->dispatch(
                'favicon-updated',
                url: asset('storage/'.$path).'?v='.md5($path.microtime(true)),
                type: strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'ico' ? 'image/x-icon' : 'image/png',
            );
        }
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
