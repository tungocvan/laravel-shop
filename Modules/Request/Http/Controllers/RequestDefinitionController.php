<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Modules\User\Contracts\UserDirectory;

final class RequestDefinitionController extends Controller
{
    public function groups(): View
    {
        Gate::authorize('viewAny', RequestGroup::class);

        return view('Request::admin.groups', ['groups' => RequestGroup::query()->withCount('types')->orderBy('sort_order')->paginate(25)]);
    }

    public function types(): View
    {
        Gate::authorize('viewAny', RequestType::class);

        return view('Request::admin.types');
    }

    public function designer(string $typePublicId): View
    {
        $type = RequestType::query()->where('public_id', $typePublicId)->with(['activeDraft.audiences', 'activeDraft.stages'])->firstOrFail();
        Gate::authorize('update', $type);

        return view('Request::admin.designer', compact('type'));
    }

    public function versions(string $typePublicId, UserDirectory $users): View
    {
        $type = RequestType::query()->where('public_id', $typePublicId)->with([
            'activeDraft:id,request_type_id,version_number',
            'currentPublishedVersion:id,request_type_id,version_number',
            'versions' => fn ($query) => $query->latest('version_number'),
            'versions.audiences',
            'versions.stages',
        ])->firstOrFail();
        Gate::authorize('view', $type);

        $actorIds = $type->versions
            ->flatMap(fn ($version) => [$version->created_by, $version->published_by])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $versionActors = collect($users->findManyActive($actorIds, 100))->keyBy('id');

        return view('Request::admin.versions', compact('type', 'versionActors'));
    }
}
