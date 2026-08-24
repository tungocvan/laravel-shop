<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Request\Application\Services\RequestOperationsQuery;
use Modules\Request\Application\Services\RetryRequestOperation;
use Modules\Request\Policies\RequestOperationPolicy;

final class RequestOperationsController extends Controller
{
    public function index(Request $request, RequestOperationsQuery $query, RequestOperationPolicy $policy): View
    {
        abort_unless($policy->view($request->user('admin')), 403);

        return view('Request::admin.operations', [
            'failures' => $query->failures(),
        ]);
    }

    public function retry(Request $request, RetryRequestOperation $retry, RequestOperationPolicy $policy): RedirectResponse
    {
        abort_unless($policy->retry($request->user('admin')), 403);

        $validated = $request->validate([
            'kind' => ['required', 'string', Rule::in((array) config('request.operations.retry_allowlist', []))],
            'public_id' => ['required', 'string', 'size:26'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:200'],
        ]);

        $retry->handle(
            $validated['kind'],
            $validated['public_id'],
            (int) $request->user('admin')->getAuthIdentifier(),
            $validated['idempotency_key'],
        );

        return redirect()->route('request.admin.operations')->with('request_success', __('Request::operations.retry_started'));
    }
}
