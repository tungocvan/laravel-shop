<?php

namespace Modules\Administrative\Livewire\Procedures;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Administrative\Services\ProcedureService;

class ProcedureForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $procedureId = null;

    public string $code = '';

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $instructions = '';

    public string $required_documents_text = '';

    public array $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    public int $max_file_size_kb = 10240;

    public int $max_files = 5;

    public bool $is_active = true;

    public int $sort_order = 0;

    public mixed $template = null;

    #[Locked]
    public ?string $currentTemplateName = null;

    public bool $removeTemplate = false;

    protected ProcedureService $procedureService;

    public function boot(ProcedureService $procedureService): void
    {
        $this->procedureService = $procedureService;
    }

    public function mount(?int $id = null): void
    {
        $this->authorizePermission($id ? 'administrative.procedure.update' : 'administrative.procedure.create');
        if (! $id) {
            return;
        }
        $procedure = $this->procedureService->findForEdit($id);
        $this->fill([
            'procedureId' => $procedure->id,
            'code' => $procedure->code,
            'name' => $procedure->name,
            'slug' => $procedure->slug,
            'description' => $procedure->description ?? '',
            'instructions' => $procedure->instructions ?? '',
            'required_documents_text' => implode("\n", $procedure->required_documents ?? []),
            'allowed_extensions' => $procedure->allowed_extensions ?? [],
            'max_file_size_kb' => $procedure->max_file_size_kb,
            'max_files' => $procedure->max_files,
            'is_active' => $procedure->is_active,
            'sort_order' => $procedure->sort_order,
            'currentTemplateName' => $procedure->template_original_name,
        ]);
    }

    public function updatedName(string $value): void
    {
        if ($this->procedureId === null) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(ProcedureService $service)
    {
        $permission = $this->procedureId ? 'administrative.procedure.update' : 'administrative.procedure.create';
        $this->authorizePermission($permission);
        $this->slug = $service->normalizeSlug($this->slug, $this->name);
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('administrative_procedures', 'code')->ignore($this->procedureId)],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('administrative_procedures', 'slug')->ignore($this->procedureId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'required_documents_text' => ['nullable', 'string', 'max:10000'],
            'allowed_extensions' => ['required', 'array', 'min:1'],
            'allowed_extensions.*' => ['required', Rule::in(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])],
            'max_file_size_kb' => ['required', 'integer', 'min:100', 'max:51200'],
            'max_files' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'template' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'removeTemplate' => ['boolean'],
        ]);
        $validated['updated_by'] = Auth::guard('admin')->id();

        if ($this->procedureId === null) {
            $validated['created_by'] = Auth::guard('admin')->id();
        }

        $this->procedureId
            ? $service->update($this->procedureId, $validated, $this->template, $this->removeTemplate)
            : $service->create($validated, $this->template);

        session()->flash('success', 'Đã lưu thủ tục hành chính.');

        return redirect()->route('admin.administrative.procedures.index');
    }

    public function render()
    {
        return view('Administrative::livewire.procedures.procedure-form');
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
