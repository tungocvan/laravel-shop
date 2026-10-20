<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\MuasamcongConfigService;
use Modules\Muasamcong\Services\MuasamcongSettingsRepository;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PersonalSessionService;
use Modules\Muasamcong\Services\SessionImportTokenService;
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

    public string $pricingTestStatus = '';

    public string $pricingTestMessage = '';

    public string $tokenTestStatus = '';

    public string $tokenTestMessage = '';

    public string $sessionTestStatus = '';

    public string $sessionTestMessage = '';

    public string $sessionImportLink = '';

    public string $sessionImportExpiresAt = '';

    public function mount(
        MuasamcongConfigService $configService,
        PersonalSessionService $personalSessions,
        ContractorHistoryService $history
    ): void {
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
        $this->verifyPersonalSessionOnLoad($history, $personalSessions);
    }

    public function checkEnvironment(MuasamcongConfigService $configService): void
    {
        $this->authorizeManageConfig();
        $this->loadEnvironmentStatus($configService);
    }

    public function save(MuasamcongSettingsRepository $settings): void
    {
        $this->authorizeManageConfig();

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
            'origin' => rtrim($validated['form']['origin'], '/'),
            'verify_ssl' => $validated['form']['verify_ssl'] ? 'true' : 'false',
            'timeout' => (string) $validated['form']['timeout'],
            'user_agent' => $validated['form']['user_agent'],
            'pricing_endpoint' => $validated['form']['pricing_endpoint'],
            'contractor_endpoint' => $validated['form']['contractor_endpoint'],
            'portal_referer' => $validated['form']['portal_referer'],
            'pricing_referer' => $validated['form']['pricing_referer'],
            'page_size' => (string) $validated['form']['page_size'],
        ];

        $validator = app(\\Modules\\Muasamcong\\Services\\MuasamcongUrlValidator::class);
        foreach (['origin', 'pricing_endpoint', 'contractor_endpoint', 'portal_referer', 'pricing_referer'] as $key) {
            if (! $validator->isApproved($values[$key])) {
                $this->addError('form.'.$key, 'Chỉ chấp nhận HTTPS và host Mua sắm công được phê duyệt.');
                return;
            }
        }

        foreach (['smart_token', 'session_cookie'] as $secret) {
            if ($validated['form'][$secret] !== '') {
                $values[$secret] = $validated['form'][$secret];
            }
        }

        try {
            $settings->save($values);
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Không thể lưu cấu hình vào database. Kiểm tra migration và kết nối database.');
            return;
        }

        $this->form['smart_token'] = '';
        $this->form['session_cookie'] = '';
        session()->flash('success', 'Đã lưu cấu hình Mua sắm công vào database.');
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

    public function createSessionImportLink(SessionImportTokenService $tokens): void
    {
        $this->authorizeManageConfig();
        $user = Auth::guard('admin')->user();

        try {
            $created = $tokens->create(
                $user ? (int) $user->getAuthIdentifier() : null,
                5,
                request()->getSchemeAndHttpHost()
            );

            $this->sessionImportLink = (string) $created['link'];
            $this->sessionImportExpiresAt = $created['expires_at']->format('d/m/Y H:i:s');
        } catch (Throwable $e) {
            report($e);
            $this->sessionImportLink = '';
            $this->sessionImportExpiresAt = '';
            $this->sessionTestStatus = 'error';
            $this->sessionTestMessage = 'Không thể tạo link cập nhật Session. Hãy kiểm tra migration/database.';
        }
    }

    public function testPersonalSession(ContractorHistoryService $history, PersonalSessionService $personalSessions): void
    {
        $this->authorizeManageConfig();
        $this->runPersonalSessionVerification($history, $personalSessions, true);
    }

    public function restorePricingDefaults(): void
    {
        $this->authorizeManageConfig();

        $this->form['pricing_endpoint'] = 'https://muasamcong.mof.gov.vn/o/egp-portal-personal-page/services/smart/search_prc';
        $this->form['pricing_referer'] = 'https://muasamcong.mof.gov.vn/web/guest/profile-info?menu=bid-pricing';
        $this->pricingTestStatus = '';
        $this->pricingTestMessage = 'Đã điền giá trị MOF chuẩn vào biểu mẫu. Chưa lưu cấu hình; hãy kiểm tra và bấm Lưu cấu hình khi sẵn sàng.';
    }

    public function testPricing(MuaSamCongService $service): void
    {
        $this->authorizeManageConfig();
        $this->pricingTestStatus = '';
        $this->pricingTestMessage = '';

        $result = $service->searchPricing('paracetamol');

        if ($result['success'] ?? false) {
            $this->pricingTestStatus = 'success';
            $this->pricingTestMessage = 'Smart Pricing phản hồi hợp lệ từ cấu hình đang hoạt động trên server.';

            return;
        }

        $this->pricingTestStatus = 'error';
        $this->pricingTestMessage = 'Kiểm tra Smart Pricing thất bại: '.($result['message'] ?? 'Không có phản hồi hợp lệ.');
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
        $this->tokenTestMessage = 'Kiểm tra token qua API tìm kiếm nhà thầu ('.(string) parse_url((string) config('muasamcong.endpoints.contractor_search'), PHP_URL_HOST).') thất bại: '.($result['message'] ?? 'Không có phản hồi hợp lệ.').' Đây không phải kiểm tra Smart Pricing.';
        if ((int) ($result['status'] ?? 0) === 302) {
            $this->tokenTestMessage .= ' API nhà thầu đang chuyển hướng. Cần xác minh endpoint đích trước khi cập nhật; không bật tự động theo redirect.';
        }
    }

    public function render(): View
    {
        return view('Muasamcong::livewire.config-manager-shell');
    }

    private function verifyPersonalSessionOnLoad(
        ContractorHistoryService $history,
        PersonalSessionService $personalSessions
    ): void {
        if (! ($this->personalSessionStatus['has_session'] ?? false)) {
            $this->sessionTestStatus = 'error';
            $this->sessionTestMessage = 'Chưa có Personal Page Session để kiểm tra.';

            return;
        }

        $this->runPersonalSessionVerification($history, $personalSessions, false);
    }

    private function runPersonalSessionVerification(
        ContractorHistoryService $history,
        PersonalSessionService $personalSessions,
        bool $manual
    ): void {
        $this->sessionTestStatus = '';
        $this->sessionTestMessage = '';

        try {
            $result = $history->testSession();
            $personalSessions->markVerified();
            $this->personalSessionStatus = $personalSessions->status();
            $this->sessionTestStatus = 'success';
            $prefix = $manual ? 'Personal Page Session hợp lệ.' : 'Đã tự động kiểm tra: Personal Page Session hợp lệ.';
            $this->sessionTestMessage = $prefix.' API lịch sử nhà thầu phản hồi thành công; tài khoản hiện có '.((int) ($result['total'] ?? 0)).' gói.';
        } catch (Throwable $e) {
            $personalSessions->markFailed(($manual ? 'Manual' : 'Automatic').' session verification failed: '.class_basename($e));
            $this->personalSessionStatus = $personalSessions->status();
            $this->sessionTestStatus = 'error';
            $this->sessionTestMessage = $manual
                ? 'Personal Page Session không hợp lệ hoặc đã hết hạn. Hãy tạo link cập nhật Windows mới hoặc dán Cookie mới.'
                : 'Tự động kiểm tra khi mở Config: Personal Page Session không hợp lệ hoặc đã hết hạn. Hãy dùng Windows Tool để cập nhật.';
        }
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
