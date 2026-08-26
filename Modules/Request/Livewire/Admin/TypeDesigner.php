<?php

namespace Modules\Request\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Request\Application\Services\CreateTypeDraft;
use Modules\Request\Application\Services\PublishTypeVersion;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Domain\Enums\AudienceActorType;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;
use Modules\User\Contracts\UserDirectory;

class TypeDesigner extends Component
{
    public string $typePublicId;

    public string $title = '';

    public string $description = '';

    public string $requesterGuidance = '';

    public int $schemaVersion = 1;

    public array $schemaExtras = [];

    public array $sections = [];

    /** @var list<int> */
    public array $audienceUserIds = [];

    public string $audienceSearch = '';

    public array $stages = [];

    public int $lockVersion = 1;

    public bool $showValidationModal = false;

    public string $validationModalTitle = '';

    /** @var list<string> */
    public array $validationModalMessages = [];

    public function mount(string $typePublicId): void
    {
        $this->typePublicId = $typePublicId;
        $type = $this->type();
        Gate::authorize('update', $type);

        if ($type->active_draft_version_id === null) {
            app(CreateTypeDraft::class)->handle($type, (int) auth('admin')->id());
            $type = $type->refresh();
        }

        $draft = $type->activeDraft()->with(['audiences', 'stages'])->firstOrFail();
        $schema = (array) $draft->form_schema_json;
        $this->title = $draft->title;
        $this->description = (string) ($draft->description ?? '');
        $this->requesterGuidance = (string) ($draft->requester_guidance ?? '');
        $this->schemaVersion = max(1, (int) ($schema['schema_version'] ?? 1));
        $this->sections = array_values((array) ($schema['sections'] ?? []));
        $this->schemaExtras = array_diff_key($schema, array_flip(['schema_version', 'sections']));
        $this->audienceUserIds = $this->userCreateAudienceIds($draft);
        $this->stages = $draft->stages->values()->map(function ($stage): array {
            [$slaValue, $slaUnit] = $this->minutesForEditor($stage->sla_minutes);
            [$warningValue, $warningUnit] = $this->minutesForEditor($stage->warning_minutes_before);
            [$graceValue, $graceUnit] = $this->minutesForEditor($stage->grace_minutes ?? 0);
            $resolverConfig = (array) $stage->resolver_config_json;

            return [
                'stage_key' => $stage->stage_key,
                'name' => $stage->name,
                'mode' => $stage->mode->value,
                'resolver_key' => $stage->resolver_key === 'fixed_role' ? 'role_members' : $stage->resolver_key,
                'resolver_user_ids' => array_values(array_map('intval', (array) ($resolverConfig['user_ids'] ?? []))),
                'resolver_config_json' => json_encode($resolverConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'instructions' => $stage->instructions ?? '',
                'allow_reassignment' => (bool) $stage->allow_reassignment,
                'sla_value' => $slaValue,
                'sla_unit' => $slaUnit,
                'warning_value' => $warningValue,
                'warning_unit' => $warningUnit,
                'grace_value' => $graceValue,
                'grace_unit' => $graceUnit,
                'timeout_action' => $stage->timeout_action ?? 'notify_only',
                'email_on_assignment' => (bool) ($stage->email_on_assignment ?? true),
                'email_on_decision' => (bool) ($stage->email_on_decision ?? true),
                'email_on_sla_warning' => (bool) ($stage->email_on_sla_warning ?? true),
            ];
        })->all();
        $this->lockVersion = $type->lock_version;
    }

    public function addSection(): void
    {
        $number = count($this->sections) + 1;
        $this->sections[] = ['key' => 'section_'.$number, 'label' => 'Phần '.$number, 'fields' => []];
    }

    public function removeSection(int $section): void
    {
        if (isset($this->sections[$section])) {
            array_splice($this->sections, $section, 1);
        }
    }

    public function moveSection(int $section, int $direction): void
    {
        $this->moveItem($this->sections, $section, $direction);
    }

    public function addField(int $section): void
    {
        if (! isset($this->sections[$section])) {
            return;
        }

        $fields = array_values((array) ($this->sections[$section]['fields'] ?? []));
        $number = count($fields) + 1;
        $fields[] = ['key' => 'field_'.$number, 'type' => 'text', 'label' => 'Trường '.$number, 'required' => false, 'classification' => 'internal', 'offline_draft' => true];
        $this->sections[$section]['fields'] = $fields;
    }

    public function removeField(int $section, int $field): void
    {
        if (! isset($this->sections[$section]['fields'][$field])) {
            return;
        }

        $fields = array_values((array) $this->sections[$section]['fields']);
        array_splice($fields, $field, 1);
        $this->sections[$section]['fields'] = $fields;
    }

    public function moveField(int $section, int $field, int $direction): void
    {
        if (! isset($this->sections[$section])) {
            return;
        }

        $fields = array_values((array) ($this->sections[$section]['fields'] ?? []));
        $this->moveItem($fields, $field, $direction);
        $this->sections[$section]['fields'] = $fields;
    }

    public function addStage(): void
    {
        $number = count($this->stages) + 1;
        $this->stages[] = [
            'stage_key' => 'stage_'.$number,
            'name' => 'Cấp duyệt '.$number,
            'mode' => 'single',
            'resolver_key' => 'fixed_users',
            'resolver_user_ids' => [],
            'resolver_config_json' => '{"user_ids":[]}',
            'instructions' => '',
            'allow_reassignment' => false,
            'sla_value' => 24,
            'sla_unit' => 'hours',
            'warning_value' => 4,
            'warning_unit' => 'hours',
            'grace_value' => 12,
            'grace_unit' => 'hours',
            'timeout_action' => 'suspend',
            'email_on_assignment' => true,
            'email_on_decision' => true,
            'email_on_sla_warning' => true,
        ];
    }

    public function removeStage(int $stage): void
    {
        if (isset($this->stages[$stage])) {
            array_splice($this->stages, $stage, 1);
        }
    }

    public function moveStage(int $stage, int $direction): void
    {
        $this->moveItem($this->stages, $stage, $direction);
    }

    public function save(SaveTypeDraft $service): void
    {
        $this->resetValidationFeedback();

        try {
            $this->persistDraft($service);
        } catch (ValidationException $exception) {
            $this->presentValidationFailure($exception, 'Chưa thể lưu bản nháp');

            return;
        }

        session()->flash('request_success', __('Request::request.saved'));
        $this->js("window.alert('Đã lưu bản nháp thành công.');");
    }

    public function publish(PublishTypeVersion $service): void
    {
        $this->resetValidationFeedback();
        $type = $this->type();
        Gate::authorize('publish', $type);

        try {
            $type = $this->persistDraft(app(SaveTypeDraft::class));
            $service->handle($type, (int) auth('admin')->id(), $this->lockVersion);
        } catch (ValidationException $exception) {
            $this->presentValidationFailure($exception, 'Chưa thể phát hành phiên bản');

            return;
        }

        session()->flash('request_success', __('Request::request.published'));
        $this->redirectRoute('request.admin.types.versions', $type->public_id);
    }

    public function closeValidationModal(): void
    {
        $this->showValidationModal = false;
    }

    public function render()
    {
        $type = $this->type();
        $directory = app(UserDirectory::class);
        $approverUsers = collect($directory->searchActive('@', 100))
            ->map(fn ($identity): object => (object) [
                'id' => $identity->id,
                'name' => $identity->displayName,
                'email' => $identity->maskedEmail,
            ]);

        $selectedAudienceIds = $this->normalizedAudienceUserIds();
        $selectedAudienceUsers = $selectedAudienceIds === []
            ? collect()
            : collect($directory->findManyActive($selectedAudienceIds, min(count($selectedAudienceIds), 100)));
        $activeSelectedAudienceIds = $selectedAudienceUsers
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $unavailableAudienceUsers = collect(array_values(array_diff($selectedAudienceIds, $activeSelectedAudienceIds)))
            ->map(fn (int $id): object => (object) [
                'id' => $id,
                'displayName' => 'Tài khoản không còn hoạt động',
                'maskedEmail' => null,
                'unavailable' => true,
            ]);
        $canManageAudience = Gate::allows('manageAudience', $type);
        $matchedAudienceUsers = $canManageAudience
            ? collect($directory->searchActive(trim($this->audienceSearch) !== '' ? $this->audienceSearch : '@', 100))
            : collect();
        $audienceUsers = $selectedAudienceUsers
            ->merge($unavailableAudienceUsers)
            ->merge($matchedAudienceUsers)
            ->unique(fn ($identity): int => $identity->id)
            ->sortBy(fn ($identity): string => mb_strtolower($identity->displayName))
            ->map(fn ($identity): object => (object) [
                'id' => $identity->id,
                'name' => $identity->displayName,
                'email' => $identity->maskedEmail,
                'selected' => in_array($identity->id, $selectedAudienceIds, true),
                'unavailable' => (bool) ($identity->unavailable ?? false),
            ])
            ->values();
        $draft = $type->activeDraft()->with('audiences')->firstOrFail();
        $preservedAudienceCount = $draft->audiences
            ->reject(fn ($audience): bool => $this->isUserCreateAudience($audience))
            ->count();

        return view('Request::livewire.admin.type-designer', [
            'type' => $type,
            'approverUsers' => $approverUsers,
            'approvalReady' => $this->approvalReady(),
            'audienceUsers' => $audienceUsers,
            'audienceReady' => $this->audienceReady($draft, $activeSelectedAudienceIds),
            'canManageAudience' => $canManageAudience,
            'preservedAudienceCount' => $preservedAudienceCount,
        ]);
    }

    private function type(): RequestType
    {
        return RequestType::query()->where('public_id', $this->typePublicId)->firstOrFail();
    }

    private function persistDraft(SaveTypeDraft $service): RequestType
    {
        $type = $this->type();
        Gate::authorize('update', $type);
        $schema = $this->schemaExtras;
        $schema['schema_version'] = $this->schemaVersion;
        $schema['sections'] = array_values($this->sections);
        $audiences = $this->audiencesForSave($type);

        $service->handle($type, [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'requester_guidance' => $this->requesterGuidance ?: null,
            'form_schema_json' => $schema,
            'audiences' => $audiences,
            'stages' => $this->normalizedStages(),
        ], (int) auth('admin')->id(), $this->lockVersion);

        $type->refresh();
        $this->lockVersion = $type->lock_version;

        return $type;
    }

    private function resetValidationFeedback(): void
    {
        $this->resetErrorBag();
        $this->showValidationModal = false;
        $this->validationModalTitle = '';
        $this->validationModalMessages = [];
    }

    private function presentValidationFailure(ValidationException $exception, string $title): void
    {
        $this->setErrorBag($exception->validator->errors());
        $this->validationModalTitle = $title;
        $this->validationModalMessages = $this->validationMessages($exception->errors());
        $this->showValidationModal = true;
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     * @return list<string>
     */
    private function validationMessages(array $errors): array
    {
        $messages = [];
        foreach ($errors as $field => $codes) {
            foreach ((array) $codes as $code) {
                $messages[] = $this->validationMessage((string) $field, (string) $code);
            }
        }

        return array_values(array_unique($messages ?: ['Một hoặc nhiều phần cấu hình chưa hợp lệ. Hãy kiểm tra lại bản nháp.']));
    }

    private function validationMessage(string $field, string $code): string
    {
        $stage = '';
        if (preg_match('/^stages\.(\d+)/', $field, $matches) === 1) {
            $stage = 'Cấp duyệt '.((int) $matches[1] + 1).': ';
        }

        return match ($code) {
            'duration_required' => $stage.'Hãy nhập thời hạn xử lý SLA.',
            'duration_invalid', 'duration_unit_invalid', 'sla_duration_invalid' => $stage.'Thời lượng SLA chưa hợp lệ.',
            'duration_exceeds_maximum' => $stage.'Thời lượng SLA không được vượt quá 365 ngày.',
            'warning_exceeds_sla' => $stage.'Cảnh báo trước hạn không được lớn hơn thời hạn xử lý.',
            'grace_requires_suspension' => $stage.'Chỉ cấu hình thời gian gia hạn khi chọn tạm dừng quá hạn.',
            'sla_required_for_timeout_configuration' => $stage.'Hãy nhập thời hạn xử lý trước khi cấu hình cảnh báo hoặc tạm dừng.',
            'single_approver_required' => $stage.'Chế độ một người duyệt phải có đúng một người phê duyệt.',
            'approver_required', 'fixed_users_invalid' => $stage.'Hãy chọn người phê duyệt đang hoạt động.',
            'role_required', 'role_unavailable' => $stage.'Hãy chọn một vai trò quản trị hợp lệ.',
            'user_field_required', 'user_field_invalid' => $stage.'Hãy chọn một trường người dùng hợp lệ từ biểu mẫu.',
            'resolver_not_registered' => $stage.'Bộ phân giải người duyệt không được hỗ trợ.',
            'stage_identity_required' => $stage.'Mã và tên cấp duyệt là bắt buộc.',
            'stage_mode_invalid', 'invalid_mode' => $stage.'Chế độ phê duyệt chưa hợp lệ.',
            'timeout_action_invalid' => $stage.'Hành vi sau khi quá hạn chưa hợp lệ.',
            'at_least_one_stage_required' => 'Hãy thêm ít nhất một cấp phê duyệt và hoàn thiện cấu hình SLA.',
            'stage_limit_exceeded' => 'Số cấp phê duyệt vượt quá giới hạn cho phép.',
            'invalid_json', 'array_required' => $this->validationSection($field).' phải là JSON hợp lệ.',
            'unsupported_schema_version', 'invalid_sections', 'invalid_key', 'invalid_or_duplicate_key', 'unsupported_field_type', 'field_limit_exceeded' => 'Biểu mẫu còn trường hoặc cấu trúc chưa hợp lệ.',
            'actor_unavailable' => 'Một hoặc nhiều người được phép tạo đề nghị không còn hoạt động.',
            'audience_limit_exceeded' => 'Mỗi loại đề nghị chỉ được phân trực tiếp cho tối đa 100 người dùng.',
            'stale_version' => 'Bản nháp đã thay đổi trên máy chủ. Hãy tải lại trang trước khi tiếp tục.',
            default => $this->validationSection($field).' còn dữ liệu chưa hợp lệ.',
        };
    }

    private function validationSection(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'stages') => 'Phê duyệt & SLA',
            str_starts_with($field, 'form_schema_json') => 'Biểu mẫu',
            str_starts_with($field, 'audiences'), $field === 'audienceUserIds' => 'Đối tượng tạo đề nghị',
            default => 'Bản nháp',
        };
    }

    private function decode(string $json, string $field): array
    {
        try {
            $value = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([$field => 'invalid_json']);
        }

        if (! is_array($value)) {
            throw ValidationException::withMessages([$field => 'array_required']);
        }

        return $value;
    }

    /** @return array{0:int|string,1:string} */
    private function minutesForEditor(?int $minutes): array
    {
        if ($minutes === null) {
            return ['', 'hours'];
        }

        if ($minutes > 0 && $minutes % 1440 === 0) {
            return [$minutes / 1440, 'days'];
        }

        if ($minutes > 0 && $minutes % 60 === 0) {
            return [$minutes / 60, 'hours'];
        }

        return [$minutes, 'minutes'];
    }

    private function editorDurationToMinutes(mixed $value, string $unit, bool $required, string $field): ?int
    {
        if ($value === '' || $value === null) {
            if ($required) {
                throw ValidationException::withMessages([$field => 'duration_required']);
            }

            return null;
        }

        if (! is_numeric($value) || (float) $value < 0 || ($required && (float) $value <= 0)) {
            throw ValidationException::withMessages([$field => 'duration_invalid']);
        }

        $factor = match ($unit) {
            'minutes' => 1,
            'hours' => 60,
            'days' => 1440,
            default => throw ValidationException::withMessages([$field => 'duration_unit_invalid']),
        };

        $minutes = (int) round((float) $value * $factor);
        if ($minutes > (int) config('request.settings.max_sla_duration_minutes', 525600)) {
            throw ValidationException::withMessages([$field => 'duration_exceeds_maximum']);
        }

        return $minutes;
    }

    /** @return list<array<string, mixed>> */
    private function normalizedStages(): array
    {
        if (count($this->stages) > (int) config('request.settings.max_stage_count', 20)) {
            throw ValidationException::withMessages(['stages' => 'stage_limit_exceeded']);
        }

        $stages = [];
        foreach (array_values($this->stages) as $index => $stage) {
            $path = 'stages.'.$index;
            $stage['position'] = $index + 1;
            $stage['stage_key'] = trim((string) ($stage['stage_key'] ?? ''));
            $stage['name'] = trim((string) ($stage['name'] ?? ''));
            if ($stage['stage_key'] === '' || $stage['name'] === '') {
                throw ValidationException::withMessages([$path => 'stage_identity_required']);
            }

            $mode = (string) ($stage['mode'] ?? 'single');
            if (! in_array($mode, ['single', 'parallel_all', 'parallel_any'], true)) {
                throw ValidationException::withMessages([$path.'.mode' => 'stage_mode_invalid']);
            }

            $resolverKey = (string) ($stage['resolver_key'] ?? 'fixed_users');
            $resolverKey = $resolverKey === 'fixed_role' ? 'role_members' : $resolverKey;
            if (! in_array($resolverKey, ['fixed_users', 'role_members', 'form_user_field'], true)) {
                throw ValidationException::withMessages([$path.'.resolver_key' => 'resolver_not_registered']);
            }
            $stage['resolver_key'] = $resolverKey;

            if ($resolverKey === 'fixed_users') {
                $userIds = collect((array) ($stage['resolver_user_ids'] ?? []))
                    ->map(fn (mixed $id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
                if ($userIds === [] || ($mode === 'single' && count($userIds) !== 1)) {
                    throw ValidationException::withMessages([$path.'.resolver_user_ids' => $mode === 'single' ? 'single_approver_required' : 'approver_required']);
                }
                $stage['resolver_config_json'] = ['user_ids' => $userIds];
            } else {
                $resolverConfig = $this->decode((string) ($stage['resolver_config_json'] ?? '{}'), $path.'.resolver_config_json');
                if ($resolverKey === 'role_members' && (int) ($resolverConfig['role_id'] ?? 0) <= 0) {
                    throw ValidationException::withMessages([$path.'.resolver_config_json' => 'role_required']);
                }
                if ($resolverKey === 'form_user_field' && trim((string) ($resolverConfig['field_key'] ?? '')) === '') {
                    throw ValidationException::withMessages([$path.'.resolver_config_json' => 'user_field_required']);
                }
                $stage['resolver_config_json'] = $resolverConfig;
            }

            $stage['sla_minutes'] = $this->editorDurationToMinutes($stage['sla_value'] ?? null, (string) ($stage['sla_unit'] ?? 'hours'), true, $path.'.sla_value');
            $stage['warning_minutes_before'] = $this->editorDurationToMinutes($stage['warning_value'] ?? null, (string) ($stage['warning_unit'] ?? 'hours'), false, $path.'.warning_value');
            $stage['warning_minutes_before'] = $stage['warning_minutes_before'] === 0 ? null : $stage['warning_minutes_before'];
            if ($stage['warning_minutes_before'] !== null && $stage['warning_minutes_before'] > $stage['sla_minutes']) {
                throw ValidationException::withMessages([$path.'.warning_value' => 'warning_exceeds_sla']);
            }

            $timeoutAction = (string) ($stage['timeout_action'] ?? 'notify_only');
            if (! in_array($timeoutAction, ['notify_only', 'suspend'], true)) {
                throw ValidationException::withMessages([$path.'.timeout_action' => 'timeout_action_invalid']);
            }
            $stage['timeout_action'] = $timeoutAction;
            $stage['grace_minutes'] = $timeoutAction === 'suspend'
                ? ($this->editorDurationToMinutes($stage['grace_value'] ?? 0, (string) ($stage['grace_unit'] ?? 'hours'), false, $path.'.grace_value') ?? 0)
                : 0;

            $stage['email_on_assignment'] = (bool) ($stage['email_on_assignment'] ?? false);
            $stage['email_on_decision'] = (bool) ($stage['email_on_decision'] ?? false);
            $stage['email_on_sla_warning'] = $stage['warning_minutes_before'] !== null
                && (bool) ($stage['email_on_sla_warning'] ?? false);

            unset(
                $stage['resolver_user_ids'],
                $stage['sla_value'],
                $stage['sla_unit'],
                $stage['warning_value'],
                $stage['warning_unit'],
                $stage['grace_value'],
                $stage['grace_unit'],
                $stage['suspend_on_overdue'],
                $stage['email_notification_enabled'],
            );
            $stages[] = $stage;
        }

        return $stages;
    }

    private function approvalReady(): bool
    {
        if ($this->stages === []) {
            return false;
        }

        try {
            $this->normalizedStages();

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /** @return list<int> */
    private function normalizedAudienceUserIds(): array
    {
        return collect($this->audienceUserIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @return list<int> */
    private function userCreateAudienceIds(RequestTypeVersion $draft): array
    {
        return $draft->audiences
            ->filter(fn ($audience): bool => $this->isUserCreateAudience($audience))
            ->pluck('actor_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function isUserCreateAudience(mixed $audience): bool
    {
        return $audience->actor_type === AudienceActorType::User
            && $audience->capability === AudienceCapability::Create;
    }

    /** @return list<array{actor_type:string,actor_id:int,capability:string}> */
    private function audiencesForSave(RequestType $type): array
    {
        $draft = $type->activeDraft()->with('audiences')->firstOrFail();
        $currentUserIds = $this->userCreateAudienceIds($draft);
        $selectedUserIds = $this->normalizedAudienceUserIds();
        $audienceChanged = $selectedUserIds !== $currentUserIds;

        if ($audienceChanged) {
            Gate::authorize('manageAudience', $type);
        }

        if ($audienceChanged && count($selectedUserIds) > 100) {
            throw ValidationException::withMessages(['audienceUserIds' => 'audience_limit_exceeded']);
        }

        if ($audienceChanged && $selectedUserIds !== []) {
            $activeUserIds = collect(app(UserDirectory::class)->findManyActive($selectedUserIds, count($selectedUserIds)))
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->sort()
                ->values()
                ->all();
            if ($activeUserIds !== $selectedUserIds) {
                throw ValidationException::withMessages(['audienceUserIds' => 'actor_unavailable']);
            }
        }

        $preserved = $draft->audiences
            ->reject(fn ($audience): bool => $this->isUserCreateAudience($audience))
            ->map(fn ($audience): array => [
                'actor_type' => $audience->actor_type->value,
                'actor_id' => (int) $audience->actor_id,
                'capability' => $audience->capability->value,
            ])
            ->values()
            ->all();
        $selectedUsers = collect($selectedUserIds)
            ->map(fn (int $userId): array => [
                'actor_type' => AudienceActorType::User->value,
                'actor_id' => $userId,
                'capability' => AudienceCapability::Create->value,
            ])
            ->all();

        return array_values(array_merge($preserved, $selectedUsers));
    }

    /** @param list<int> $activeSelectedUserIds */
    private function audienceReady(RequestTypeVersion $draft, array $activeSelectedUserIds): bool
    {
        if ($this->normalizedAudienceUserIds() !== []
            && $this->normalizedAudienceUserIds() === collect($activeSelectedUserIds)->sort()->values()->all()) {
            return true;
        }

        return $draft->audiences->contains(
            fn ($audience): bool => $audience->capability === AudienceCapability::Create
                && ! $this->isUserCreateAudience($audience),
        );
    }

    private function moveItem(array &$items, int $index, int $direction): void
    {
        $items = array_values($items);
        $target = $index + ($direction < 0 ? -1 : 1);
        if (! isset($items[$index], $items[$target])) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
    }
}
