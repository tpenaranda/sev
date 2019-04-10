<?php

namespace App\Http\Controllers\Twitch;

use App\Http\Controllers\Controller;
use App\Http\Twitch\Client;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function redirectToProvider(Request $request)
    {
        $params = [
            'client_id' => config('twitch.client_id'),
            'redirect_uri' => route('twitch_oauth2_callback_url'),
            'response_type' => 'code',
            'scope' => 'user:read:email chat:read chat:edit',
            'state' => sha1(session()->getId()),
        ];

        return redirect(config('twitch.oauth2_url') . '?' . http_build_query($params));
    }

    public function handleProviderCallback(Request $request)
    {
        if (!empty($request->error)) {
            if ($request->error === 'access_denied') {
                session(['twitch_auth' => null]);
                return view('home');
            }
            return $request->error_description;
        }

        session(['twitch_auth' => app(Client::class)->getAuthForCode($request->code)]);

        return redirect(route('home'));
    }
}
