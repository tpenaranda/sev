<?php

namespace App\Http\Twitch;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Response;
use Cache, Redis, stdClass;

class Client extends GuzzleClient
{
    protected $clientId;
    protected $clientSecret;
    protected $helixBaseUrl;
    protected $redirectUri;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->clientId = config('twitch.client_id');
        $this->clientSecret = config('twitch.client_secret');
        $this->helixBaseUrl = 'https://api.twitch.tv/helix';
        $this->redirectUri = route('twitch_oauth2_callback_url');
    }

    public function getAuthForCode(string $code): string
    {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ];

        return $this->request('POST', 'https://id.twitch.tv/oauth2/token', ['form_params' => $params])->getBody()->getContents();
    }

    public function getUserObjectByUsername(string $username): ? stdClass
    {
        return Cache::remember("user_object_for_username:{$username}", $minutes = 30, function () use ($username) {
            $response = $this->request('GET', "{$this->helixBaseUrl}/users?login={$username}", ['headers' => ['Client-ID' => $this->clientId]])->getBody()->getContents();

            return json_decode($response)->data[0] ?? null;
        });
    }

    protected function requestSubmitToWebhook(array $params = [], int $leaseSeconds = 864000): bool
    {
        $headers = ['Client-ID' => $this->clientId];

        $defaultParams = [
            'hub.mode' => 'subscribe',
            'hub.lease_seconds' => "{$leaseSeconds}",
            'hub.secret' => config('app.webhooks_signature_secret'),
        ];

        $form_params  = array_merge($defaultParams, $params);

        $response = $this->request('POST', "{$this->helixBaseUrl}/webhooks/hub", compact('form_params', 'headers'));

        return $response->getStatusCode() == Response::HTTP_ACCEPTED;
    }

    public function submitToWebhookNewFollower(stdClass $user, int $leaseSeconds = 259200): bool
    {
        $params = [
            'hub.topic' => "{$this->helixBaseUrl}/users/follows?first=1&to_id={$user->id}",
            'hub.callback' => route('twitch.webhook') . "?user_id={$user->id}&type=new_follower",
        ];

        return $this->requestSubmitToWebhook($params, $leaseSeconds);
    }

    public function submitToWebhookStreamChanged(stdClass $user, int $leaseSeconds = 259200): bool
    {
        $params = [
            'hub.topic' => "{$this->helixBaseUrl}/streams?user_id={$user->id}",
            'hub.callback' => route('twitch.webhook') . "?user_id={$user->id}&type=stream_changed",
        ];

        return $this->requestSubmitToWebhook($params, $leaseSeconds);
    }
}
