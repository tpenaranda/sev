<?php

namespace App\Http\Controllers\Twitch;

use App\Http\Controllers\Controller;
use App\Http\Twitch\Client;
use Illuminate\Http\{Request, Response};
use Log, Redis;

class MainController extends Controller
{
    protected function handleWebhook(Request $request)
    {
        $userId = $request->get('user_id');
        $type = $request->get('type');

        if (empty($userId) || empty($type)) {
            Log::info('Missing data when handling webhook.');
            return false;
        }

        switch ($type) {
            case 'stream_changed':
                $this->logStreamChangedEvent($userId, $request->get('data'));
                break;
            case 'new_follower':
                $this->logNewFollowerEvent($userId, $request->get('data'));
                break;
            default:
                break;
        }

        return true;
    }

    protected function logStreamChangedEvent(int $userId, array $data = [])
    {
        if (!empty($data[0]['type']) && $data[0]['type'] === 'live') {
            $description = 'Stream went online';
            $done_at = $data[0]['started_at'];
        } else {
            $description = 'Stream went offline';
            $done_at = now()->format('c');
        }

        return $this->logEvent($userId, compact('description', 'done_at'));
    }

    protected function logNewFollowerEvent(int $userId, array $data = [])
    {
        $description = "Streamer is now followed by username {$data[0]['from_name']}";
        $done_at = $data[0]['followed_at'];

        return $this->logEvent($userId, compact('description', 'done_at'));
    }

    protected function logEvent(int $userId, array $data = [])
    {
        return Redis::rpush("webhook_events_user_id:{$userId}", json_encode($data));
    }

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
        if (empty($streamer = session('favorite_streamer'))) {
            return response()->json(['data' => []]);
        }

        $userId = app(Client::class)->getUserObjectByUsername($streamer)->id;

        $events = Redis::lrange("webhook_events_user_id:{$userId}", 0, 9);

        return response()->json(['data' => $events]);
    }

    public function verifyWebhook(Request $request)
    {
        return response($request->get('hub_challenge', ''), Response::HTTP_ACCEPTED);
    }

    public function receiveWebhook(Request $request)
    {
        Log::info('receiveWebhook');
        Log::info($request);

        $this->handleWebhook($request);

        return response('Got it, thanks!');
    }
}
