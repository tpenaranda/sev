<?php

namespace App\Http\Controllers\Twitch;

use App\Http\Controllers\Controller;
use App\Http\Twitch\Client;
use Illuminate\Http\{Request, Response};
use Log;

class MainController extends Controller
{
    public function setWebhook(Request $request)
    {
        $client = app(Client::class);
        $favorite_streamer = $request->streamer;

        if (empty($user = $client->getUserObjectByUsername($favorite_streamer))) {
            return response()->json(['error' => 'Username not found'], Response::HTTP_BAD_REQUEST);
        }

        if (!$client->submitToWebhookStreamChanged($user) || !$client->submitToWebhookNewFollower($user)) {
            return response()->json(['error' => "Sorry, can't subscribe to {$favorite_streamer} webhooks right now"], Response::HTTP_NOT_ACCEPTABLE);
        }

        session(compact('favorite_streamer'));

        return response()->json(['data' => []], Response::HTTP_ACCEPTED);
    }

    public function getWebhooks(Request $request)
    {
        return response()->json(['data' => []]);
    }

    public function verifyWebhook(Request $request)
    {
        Log::info('Webhook verification requested');

        Log::info($request);

        return response($request->get('hub_challenge', ''), Response::HTTP_ACCEPTED);
    }

    public function receiveWebhook(Request $request)
    {
        Log::info('Receive');
        Log::info($request);

        return response('Got it, thanks!');
    }
}
