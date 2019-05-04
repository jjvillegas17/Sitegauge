<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterAccount extends Model
{
    //
    public $timestamps = false;

    protected $fillable = ['id', 'username', 'name', 'followers', 'following', 'tweets', 'user_id'];

    public function users(){
    	return $this->belongsToMany('App\User', 'user_twitter_accounts');
    }

    public function tweetMetrics(){
    	return $this->hasMany('App\TweetMetric', 'twitter_id');
    }
}
