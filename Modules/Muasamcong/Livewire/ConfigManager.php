<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\MuasamcongConfigService;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PersonalSessionService;
use Throwable;

class ConfigManager extends Component
{
    private const MANAGE_PERMISSION = 'muasamcong.config.manage';

    public array $form = [
        'origin' => '',
        'verify_ssl' => true,
        'timeout' => 20,
        'user_agent' => '',
        'smart_token' => '',
        'session_cookie' => '',
        'pricing_endpoint' => '',
        'contractor_endpoint' => '',
        'portal_referer' => '',
        'pricing_referer' => '',
        'page_size' => 20,
    ];

    public array $environmentStatus = [];

    public array $personalSessionStatus = [];

    public bool $hasSmartToken = false;

    public bool $hasSessionCookie = false;

    public string $personalSessionCookie = '';

    public string $tokenTestStatus = '';

    public string $tokenTestMessage = '';

    public string $sessionTestStatus = '';

    public string $sessionTestMessage = '';

    public function mount(MuasamcongConfigService $configService, PersonalSessionService $personalSessions): void
    {
        $this->form = [
            'origin' => (string) config('muasamcong.origin'),
            'verify_ssl' => app()->environment('production')
                ? true
                : (bool) config('muasamcong.verify_ssl', true),
            'timeout' => (int) config('muasamcong.timeout', 20),
            'user_agent' => (string) config('muasamcong.user_agent'),
            'smart_token' => '',
            'session_cookie' => '',
            'pricing_endpoint' => (string) config('muasamcong.endpoints.pricing'),
            'contractor_endpoint' => (string) config('muasamcong.endpoints.contractor_search'),
            'portal_referer' => (string) config('muasamcong.referers.portal'),
            'pricing_referer' => (string) config('muasamcong.referers.pricing'),
            'page_size' => (int) config('muasamcong.page_size', 20),
        ];

        $this->hasSmartToken = trim((string) config('muasamcong.smart_token')) !== '';
        $this->hasSessionCookie = trim((string) config('muasamcong.session_cookie')) !== '';
        $this->personalSessionStatus = $personalSessions->status();
        $this->loadEnvironmentStatus($configService);
    }

    public function checkEnvironment(MuasamcongConfigService $configService): void
    {
        $this->authorizeManageConfig();
        $this->loadEnvironmentStatus($configService);
    }

    public function repairEnvironment(MuasamcongConfigService $configService): void
    {
        $this->authorizeManageConfig();

        if ($configService->isDockerRuntime()) {
            $this->loadEnvironmentStatus($configService);
            session()->flash('error', 'Đang chạy trong Docker. Không sửa .env bên trong container; hãy cập nhật .env ở host rồi rebuild/redeploy.');

            return;
        }

        try {
            $added = $configService->ensureDefaults();

            if ($added !== []) {
                Artisan::call('config:clear');
                session()->flash('success', 'Đã bổ sung '.count($added).' biến MUASAMCONG_* còn thiếu vào .env bằng giá trị mặc định.');
            } else {
                session()->flash('success', 'Các biến MUASAMCONG_* trong .env đã đầy đủ.');
            }
        } catch (Throwable) {
            session()->flash('error', 'Không thể kiểm tra/bổ sung .env. Vui lòng kiểm tra file và quyền đọc/ghi.');
        }

        $this->loadEnvironmentStatus($configService);
    }

    public function save(MuasamcongConfigService $configService): void
    {
        $this->authorizeManageConfig();

        if ($configService->isDockerRuntime()) {
            $this->loadEnvironmentStatus($configService);
            session()->flash('error', 'Docker runtime không lưu .env trong container. Hãy cập nhật .env ở host và rebuild/redeploy Docker.');

            return;
        }

        $validated = $this->validate([
            'form.origin' => ['required', 'url:https', 'max:500'],
            'form.verify_ssl' => ['required', 'boolean'],
            'form.timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'form.user_agent' => ['required', 'string', 'max:500', 'not_regex:/[\r\n]/'],
            'form.smart_token' => ['nullable', 'string', 'max:4000', 'not_regex:/[\r\n]/'],
            'form.session_cookie' => ['nullable', 'string', 'max:16000', 'not_regex:/[\r\n]/'],
            'form.pricing_endpoint' => ['required', 'url:https', 'max:1000'],
            'form.contractor_endpoint' => ['required', 'url:https', 'max:1000'],
            'form.portal_referer' => ['required', 'url:https', 'max:1000'],
            'form.pricing_referer' => ['required', 'url:https', 'max:1000'],
            'form.page_size' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        if (app()->environment('production') && ! $validated['form']['verify_ssl']) {
            $this->addError('form.verify_ssl', 'Không được tắt xác minh SSL trong môi trường production.');

            return;
        }

        $values = [
            'MUASAMCONG_ORIGIN' => rtrim($validated['form']['origin'], '/'),
            'MUASAMCONG_VERIFY_SSL' => $validated['form']['verify_ssl'] ? 'true' : 'false',
            'MUASAMCONG_TIMEOUT' => (string) $validated['form']['timeout'],
            'MUASAMCONG_USER_AGENT' => $validated['form']['user_agent'],
            'MUASAMCONG_PRICING_ENDPOINT' => $validated['form']['pricing_endpoint'],
            'MUASAMCONG_CONTRACTOR_ENDPOINT' => $validated['form']['contractor_endpoint'],
            'MUASAMCONG_PORTAL_REFERER' => $validated['form']['portal_referer'],
            'MUASAMCONG_PRICING_REFERER' => $validated['form']['pricing_referer'],
            'MUASAMCONG_PAGE_SIZE' => (string) $validated['form']['page_size'],
        ];

        if ($validated['form']['smart_token'] !== '') {
            $values['MUASAMCONG_SMART_TOKEN'] = $validated['form']['smart_token'];
        }

        if ($validated['form']['session_cookie'] !== '') {
            $values['MUASAMCONG_SESSION_COOKIE'] = $validated['form']['session_cookie'];
        }

        try {
            $configService->update($values);
            Artisan::call('config:clear');
        } catch (Throwable) {
            session()->flash('error', 'Không thể lưu cấu hình Mua sắm công. Vui lòng kiểm tra dữ liệu và quyền ghi file .env.');

            return;
        }

        $this->form['smart_token'] = '';
        $this->form['session_cookie'] = '';
        session()->flash('success', 'Đã cập nhật cấu hình Mua sắm công.');
        $this->redirectRoute('muasamcong.config');
    }

    public function savePersonalSession(PersonalSessionService $personalSessions): void
    {
        $this->authorizeManageConfig();

        $validated = $this->validate([
            'personalSessionCookie' => ['required', 'string', 'min:20', 'max:20000', 'not_regex:/[\r\n]/'],
        ]);

        $user = Auth::guard('admin')->user();

        try {
            $personalSessions->save($validated['personalSessionCookie'], $user ? (int) $user->getAuthIdentifier() : null);
            $this->personalSessionCookie = '';
            $this->personalSessionStatus = $personalSessions->status();
            $this->sessionTestStatus = '';
            $this->sessionTestMessage = 'Đã lưu Personal Page Session vào database ở dạng mã hóa. Hãy bấm Kiểm tra Session.';
        } catch (Throwable $e) {
            report($e);
            $this->sessionTestStatus = 'error';
            $this->sessionTestMessage = 'Không thể lưu Personal Page Session.';
        }
    }

    public function testPersonalSession(ContractorHistoryService $history, PersonalSessionService $personalSessions): void
    {
        $this->authorizeManageConfig();
        $this->sessionTestStatus = '';
        $this->sessionTestMessage = '';

        try {
            $result = $history->testSession();
            $personalSessions->markVerified();
            $this->personalSessionStatus = $personalSessions->status();
            $this->sessionTestStatus = 'success';
            $this->sessionTestMessage = 'Personal Page Session hợp lệ. API lịch sử nhà thầu phản hồi thành công; tài khoản hiện có '.((int) ($result['total'] ?? 0)).' gói.';
        } catch (Throwable $e) {
            $personalSessions->markFailed($e->getMessage());
            $this->personalSessionStatus = $personalSessions->status();
            $this->sessionTestStatus = 'error';
            $this->sessionTestMessage = 'Personal Page Session không hợp lệ hoặc đã hết hạn. Hãy lấy Cookie mới từ request get-list-notify-contractor-join trên trình duyệt.';
        }
    }

    public function testToken(MuaSamCongService $service): void
    {
        $this->authorizeManageConfig();

        $validated = $this->validate([
            'form.smart_token' => ['nullable', 'string', 'max:4000', 'not_regex:/[\r\n]/'],
            'form.session_cookie' => ['nullable', 'string', 'max:16000', 'not_regex:/[\r\n]/'],
        ]);

        $this->tokenTestStatus = '';
        $this->tokenTestMessage = '';

        $result = $service->testSmartToken(
            $validated['form']['smart_token'] !== '' ? $validated['form']['smart_token'] : null,
            $validated['form']['session_cookie'] !== '' ? $validated['form']['session_cookie'] : null
        );

        if ($result['success'] ?? false) {
            $total = (int) ($result['data']['total'] ?? 0);
            $this->tokenTestStatus = 'success';
            $this->tokenTestMessage = "Token hợp lệ. Truy vấn thử trả về {$total} kết quả.";

            return;
        }

        $this->tokenTestStatus = 'error';
        $this->tokenTestMessage = $result['message'] ?? 'Token không hợp lệ hoặc đã hết hạn.';
    }

    public function render(): View
    {
        return view('Muasamcong::livewire.config-manager-shell');
    }

    private function loadEnvironmentStatus(MuasamcongConfigService $configService): void
    {
        try {
            $this->environmentStatus = $configService->inspectEnvironment();
        } catch (Throwable) {
            $this->environmentStatus = [
                'docker' => $configService->isDockerRuntime(),
                'exists' => false,
                'readable' => false,
                'writable' => false,
                'total' => 0,
                'present' => 0,
                'missing' => [],
                'complete' => false,
                'snippet' => '',
            ];
        }
    }

    private function authorizeManageConfig(): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless(
            $user !== null && Gate::forUser($user)->allows(self::MANAGE_PERMISSION),
            403
        );
    }
}
