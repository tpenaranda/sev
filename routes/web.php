<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () { return view('home'); })->name('home');

// Twitch
Route::group(['namespace' => 'Twitch'], function () {

    // Login
    Route::group(['prefix' => 'login/twitch'], function () {
        Route::get('', 'LoginController@redirectToProvider');
        Route::get('callback', 'LoginController@handleProviderCallback')->name('twitch_oauth2_callback_url');
    });

    // Webhooks
    Route::group(['prefix' => 'twitch'], function () {
        Route::post('listen', 'MainController@setWebhook');
        Route::get('listen', 'MainController@getWebhooks');

        Route::group(['prefix' => 'webhooks'], function () {
            Route::get('', 'MainController@verifyWebhook');
            Route::post('', 'MainController@receiveWebhook')->name('twitch.webhook');
        });
    });
});
