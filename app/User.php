<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable (pwedeng ipasa nlng ay ung array sa pagssave
     * imbis na mag 
     * $user->user_type = 'admin';
     * $user->save();
     * 
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'first_name', 'last_name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function facebookPages(){
        return $this->belongsToMany('App\FacebookPage', 'user_pages');
    }

    public function twitterAccounts(){
        return $this->belongsToMany('App\TwitterAccount', 'user_twitter_accounts');
    }

    public function googleAnalyticsAccounts(){
        return $this->belongsToMany('App\GoogleAnalytics', 'user_google_analytics');   
    }
}
