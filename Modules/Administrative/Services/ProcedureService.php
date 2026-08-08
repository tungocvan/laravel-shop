<?php

namespace Modules\Administrative\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Administrative\Models\AdministrativeProcedure;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProcedureService
{
    public function listActiveForPublic(): Collection
    {
        return AdministrativeProcedure::query()->active()->ordered()->get();
    }

    public function findActiveForPublic(int $id): AdministrativeProcedure
    {
        return AdministrativeProcedure::query()->active()->findOrFail($id);
    }

    public function listForAdmin(array $filters, string|int $perPage = 10): LengthAwarePaginator|Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');

        $query = AdministrativeProcedure::query()
            ->withCount('submissions')
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->ordered();

        return $perPage === 'All' ? $query->get() : $query->paginate((int) $perPage);
    }

    public function findForEdit(int $id): AdministrativeProcedure
    {
        return AdministrativeProcedure::query()->findOrFail($id);
    }

    public function downloadTemplate(int $id): StreamedResponse
    {
        $procedure = $this->findForEdit($id);

        abort_unless($procedure->template_disk && $procedure->template_path, 404);
        abort_unless(Storage::disk($procedure->template_disk)->exists($procedure->template_path), 404);

        return Storage::disk($procedure->template_disk)->download(
            $procedure->template_path,
            $procedure->template_original_name ?: basename($procedure->template_path)
        );
    }

    public function downloadPublicTemplate(AdministrativeProcedure $procedure): StreamedResponse
    {
        abort_unless($procedure->is_active && ! $procedure->trashed(), 404);
        abort_unless($procedure->template_disk && $procedure->template_path, 404);
        abort_unless(Storage::disk($procedure->template_disk)->exists($procedure->template_path), 404);

        return Storage::disk($procedure->template_disk)->download(
            $procedure->template_path,
            $procedure->template_original_name ?: basename($procedure->template_path)
        );
    }

    public function create(array $data, mixed $template = null): AdministrativeProcedure
    {
        $stored = $this->storeTemplate($template);

        try {
            return DB::transaction(function () use ($data, $stored): AdministrativeProcedure {
                return AdministrativeProcedure::query()->create($this->normalize($data, $stored));
            });
        } catch (Throwable $exception) {
            $this->deleteTemplate($stored['disk'] ?? null, $stored['path'] ?? null);
            throw $exception;
        }
    }

    public function update(int $id, array $data, mixed $template = null, bool $removeTemplate = false): AdministrativeProcedure
    {
        $stored = $this->storeTemplate($template);
        $old = $this->findForEdit($id);
        $oldDisk = $old->template_disk;
        $oldPath = $old->template_path;

        try {
            $procedure = DB::transaction(function () use ($id, $data, $stored, $removeTemplate): AdministrativeProcedure {
                $procedure = AdministrativeProcedure::query()->lockForUpdate()->findOrFail($id);
                $normalized = $this->normalize($data, $stored);

                if ($stored === null && ! $removeTemplate) {
                    unset($normalized['template_disk'], $normalized['template_path'], $normalized['template_original_name']);
                }

                $procedure->update($normalized);

                return $procedure->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteTemplate($stored['disk'] ?? null, $stored['path'] ?? null);
            throw $exception;
        }

        if ($stored !== null || $removeTemplate) {
            $this->deleteTemplate($oldDisk, $oldPath);
        }

        return $procedure;
    }

    public function setActive(int $id, bool $active): void
    {
        DB::transaction(function () use ($id, $active): void {
            AdministrativeProcedure::query()->lockForUpdate()->findOrFail($id)->update([
                'is_active' => $active,
                'updated_by' => auth('admin')->id(),
            ]);
        });
    }

    public function archive(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $procedure = AdministrativeProcedure::query()->lockForUpdate()->findOrFail($id);

            if ($procedure->submissions()->exists()) {
                throw ValidationException::withMessages(['archive' => 'Không thể lưu trữ thủ tục đã có hồ sơ. Hãy chuyển sang trạng thái ngừng hoạt động.']);
            }

            $procedure->delete();
        });
    }

    public function normalizeSlug(?string $slug, string $name): string
    {
        return Str::slug(trim((string) $slug) ?: $name);
    }

    private function normalize(array $data, ?array $stored): array
    {
        $normalized = [
            'code' => Str::upper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'slug' => $this->normalizeSlug($data['slug'] ?? null, (string) $data['name']),
            'description' => $this->nullable($data['description'] ?? null),
            'instructions' => $this->nullable($data['instructions'] ?? null),
            'required_documents' => $this->lines($data['required_documents_text'] ?? ''),
            'allowed_extensions' => array_values(array_unique(array_map('strtolower', $data['allowed_extensions'] ?? []))),
            'max_file_size_kb' => (int) $data['max_file_size_kb'],
            'max_files' => (int) $data['max_files'],
            'is_active' => (bool) $data['is_active'],
            'sort_order' => (int) $data['sort_order'],
            'updated_by' => $data['updated_by'] ?? null,
            'template_disk' => $stored['disk'] ?? null,
            'template_path' => $stored['path'] ?? null,
            'template_original_name' => $stored['original_name'] ?? null,
        ];

        if (array_key_exists('created_by', $data)) {
            $normalized['created_by'] = $data['created_by'];
        }

        return $normalized;
    }

    private function storeTemplate(mixed $template): ?array
    {
        if ($template === null) {
            return null;
        }

        $disk = (string) config('administrative.administrative.storage_disk', 'local');
        $name = Str::uuid().'.'.strtolower($template->getClientOriginalExtension());

        return [
            'disk' => $disk,
            'path' => $template->storeAs('administrative/templates', $name, $disk),
            'original_name' => $template->getClientOriginalName(),
        ];
    }

    private function deleteTemplate(?string $disk, ?string $path): void
    {
        if ($disk && $path && Str::startsWith($path, 'administrative/templates/') && ! Str::contains($path, ['..', '\\'])) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function lines(mixed $value): array
    {
        return collect(preg_split('/\R/u', (string) $value))->map(fn ($line) => trim($line))->filter()->values()->all();
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
