<?php

namespace App\Http\Twitch;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Response;
use Redis, stdClass;

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
        $response = $this->request('GET', "{$this->helixBaseUrl}/users?login={$username}", ['headers' => ['Client-ID' => $this->clientId]])->getBody()->getContents();

        return json_decode($response)->data[0] ?? null;
    }

    public function submitToWebhookStreamChanged(stdClass $user, int $leaseSeconds = 86400): bool
    {
        $form_params = [
            'hub.callback' => route('twitch.webhook'),
            'hub.mode' => 'subscribe',
            'hub.topic' => "{$this->helixBaseUrl}/streams?user_id={$user->id}",
            'hub.lease_seconds' => "{$leaseSeconds}",
            'hub.secret' => config('app.webhooks_signature_secret'),
        ];

        $headers = ['Client-ID' => $this->clientId];

        $response = $this->request('POST', "{$this->helixBaseUrl}/webhooks/hub", compact('form_params', 'headers'));

        return $response->getStatusCode() == Response::HTTP_ACCEPTED;
    }
}
