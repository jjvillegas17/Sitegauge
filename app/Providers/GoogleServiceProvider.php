<?php
 
namespace App\Providers;
 
use Google_Client;
use Illuminate\Support\ServiceProvider;
 
class GoogleServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton(Google_Client::class, function ($app) {
            $client = new Google_Client();
            $client->setAuthConfig(Storage_path('client_secret.json'));
            return $client;
        });
    }

    public function boot()
    {
 
    }
}