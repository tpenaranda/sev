<?php

namespace App\Http\Controllers\Twitch;

use App\Http\Controllers\Controller;
use App\Http\Twitch\Client;
use Illuminate\Http\{Request, Response};
use Log;

class MainController extends Controller
{
    public function receiveWebhook(Request $request)
    {
        Log::info($request);

        return response($request->get('hub.challenge'));
    }

    public function getWebhooks(Request $request)
    {
        return response()->json(['data' => []]);
    }

    public function listen(Request $request)
    {
        $client = app(Client::class);
        $favorite_streamer = $request->streamer;

        if (empty($user = $client->getUserObjectByUsername($favorite_streamer))) {
            return response()->json(['error' => 'Username not found.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$client->submitToWebhookStreamChanged($user)) {
            return response()->json(['error' => "Sorry, can't create the event listener right now"], Response::HTTP_NOT_ACCEPTABLE);
        }

        session(compact('favorite_streamer'));

        return response()->json(['data' => []], Response::HTTP_ACCEPTED);
    }
}
