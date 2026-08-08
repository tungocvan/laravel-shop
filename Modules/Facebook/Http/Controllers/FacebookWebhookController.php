<?php

namespace Modules\Facebook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        if (
            $request->query('hub_mode') === 'subscribe'
            && hash_equals((string) config('facebook.webhook_verify_token'), (string) $request->query('hub_verify_token'))
        ) {
            return response((string) $request->query('hub_challenge'), 200);
        }

        return response('Invalid verify token', 403);
    }

    public function handle(Request $request): Response
    {
        Log::channel('facebook')->info('Facebook webhook received', [
            'object' => $request->input('object'),
            'entries' => count((array) $request->input('entry', [])),
        ]);

        return response('EVENT_RECEIVED', 200);
    }
}
