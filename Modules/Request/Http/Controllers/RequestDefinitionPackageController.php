<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Request\Application\Services\DryRunRequestDefinitionPackage;
use Modules\Request\Application\Services\ImportRequestDefinitionPackage;
use Modules\Request\Contracts\RequestDefinitionPackage;
use Modules\Request\Models\RequestType;
use Modules\Request\Support\RequestDefinitionPackageStorage;
use Symfony\Component\HttpFoundation\Response;

final class RequestDefinitionPackageController extends Controller
{
    public function show(string $typePublicId): View
    {
        $type = $this->type($typePublicId);
        Gate::authorize('view', $type);

        return view('Request::admin.definition-package', [
            'type' => $type,
            'preview' => null,
            'mappingsJson' => '{}',
            'previewChecksum' => null,
        ]);
    }

    public function download(string $typePublicId, RequestDefinitionPackage $packages): Response
    {
        $type = $this->type($typePublicId);
        Gate::authorize('exportDefinition', $type);
        $version = $type->currentPublishedVersion()->with(['type.group', 'audiences', 'stages'])->firstOrFail();
        $package = $packages->export($version);

        return response($packages->encode($package), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="request-definition-'.$type->code.'-v'.$version->version_number.'.json"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function preview(
        Request $request,
        string $typePublicId,
        RequestDefinitionPackageStorage $storage,
        RequestDefinitionPackage $packages,
        DryRunRequestDefinitionPackage $dryRun,
    ): View {
        $type = $this->type($typePublicId);
        Gate::authorize('importDefinition', $type);
        $request->validate([
            'package' => ['required', 'file', 'max:256'],
            'mappings_json' => ['nullable', 'string', 'max:20000'],
        ]);

        [$package, $mappings] = $this->readUpload($request, $storage, $packages);
        $preview = $dryRun->handle($type, $package, $mappings);
        $checksum = (string) ($package['checksum'] ?? '');
        $request->session()->put('request.definition_package.preview_checksum.'.$type->public_id, $checksum);

        return view('Request::admin.definition-package', [
            'type' => $type,
            'preview' => $preview,
            'mappingsJson' => $request->string('mappings_json', '{}')->toString() ?: '{}',
            'previewChecksum' => $checksum,
        ]);
    }

    public function import(
        Request $request,
        string $typePublicId,
        RequestDefinitionPackageStorage $storage,
        RequestDefinitionPackage $packages,
        ImportRequestDefinitionPackage $importer,
    ): RedirectResponse {
        $type = $this->type($typePublicId);
        Gate::authorize('importDefinition', $type);
        $request->validate([
            'package' => ['required', 'file', 'max:256'],
            'mappings_json' => ['nullable', 'string', 'max:20000'],
            'preview_checksum' => ['required', 'string', 'size:64'],
        ]);

        [$package, $mappings] = $this->readUpload($request, $storage, $packages);
        $checksum = (string) ($package['checksum'] ?? '');
        $expected = (string) $request->session()->get('request.definition_package.preview_checksum.'.$type->public_id, '');
        if ($expected === '' || ! hash_equals($expected, $checksum) || ! hash_equals($request->string('preview_checksum')->toString(), $checksum)) {
            throw ValidationException::withMessages(['package' => __('Request::definition_package.dry_run_required')]);
        }

        $draft = $importer->handle($type, $package, $mappings, (int) auth('admin')->id());
        $request->session()->forget('request.definition_package.preview_checksum.'.$type->public_id);

        return redirect()
            ->route('request.admin.types.designer', $type->public_id)
            ->with('request_success', __('Request::definition_package.imported', ['version' => $draft->version_number]));
    }

    private function type(string $publicId): RequestType
    {
        return RequestType::query()
            ->where('public_id', $publicId)
            ->with(['currentPublishedVersion', 'activeDraft'])
            ->firstOrFail();
    }

    private function readUpload(Request $request, RequestDefinitionPackageStorage $storage, RequestDefinitionPackage $packages): array
    {
        $stored = $storage->store($request->file('package'));
        try {
            $package = $packages->decode($storage->read($stored));
        } finally {
            $storage->delete($stored);
        }

        $mappingsJson = $request->string('mappings_json', '{}')->toString() ?: '{}';
        try {
            $mappings = json_decode($mappingsJson, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['mappings_json' => __('Request::definition_package.invalid_mappings')]);
        }
        if (! is_array($mappings) || array_is_list($mappings)) {
            throw ValidationException::withMessages(['mappings_json' => __('Request::definition_package.invalid_mappings')]);
        }

        return [$package, $mappings];
    }
}
