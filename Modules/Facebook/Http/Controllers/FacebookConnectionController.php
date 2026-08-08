<?php

namespace Modules\Facebook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Facebook\Exceptions\FacebookApiException;
use Modules\Facebook\Services\FacebookConnectionService;
use Modules\Facebook\Services\FacebookOAuthService;

class FacebookConnectionController extends Controller
{
    public function index(): View
    {
        return view('Facebook::pages.dashboard.index');
    }

    public function connect(FacebookOAuthService $oauth): RedirectResponse
    {
        return redirect()->away($oauth->buildAuthorizationUrl());
    }

    public function callback(Request $request, FacebookOAuthService $oauth, FacebookConnectionService $connections): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('admin.facebook.index')->with('error', 'Người dùng đã từ chối hoặc Meta trả về lỗi: '.$request->string('error_description'));
        }

        if (! $oauth->validateState($request->query('state'))) {
            return redirect()->route('admin.facebook.index')->with('error', 'OAuth state không hợp lệ hoặc đã hết hạn.');
        }

        if (! $request->filled('code')) {
            return redirect()->route('admin.facebook.index')->with('error', 'Callback Facebook thiếu authorization code.');
        }

        try {
            $connections->completeOAuth((string) $request->query('code'));
        } catch (FacebookApiException $exception) {
            return redirect()->route('admin.facebook.index')->with('error', $exception->error->message);
        }

        return redirect()->route('admin.facebook.index')->with('success', 'Đã kết nối Facebook và đồng bộ Fanpage.');
    }

    public function disconnect(FacebookConnectionService $connections): RedirectResponse
    {
        $connections->disconnect();

        return redirect()->route('admin.facebook.index')->with('success', 'Đã ngắt kết nối Facebook.');
    }

    public function syncPages(FacebookConnectionService $connections): RedirectResponse
    {
        $count = $connections->syncLatestPages();

        return redirect()->route('admin.facebook.pages.index')->with('success', "Đã đồng bộ {$count} Fanpage.");
    }
}
