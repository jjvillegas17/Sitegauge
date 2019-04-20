<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterAccount extends Model
{
    //
    public $timestamps = false;

    protected $fillable = ['id', 'username', 'name', 'followers', 'following', 'tweets', 'user_id'];

    public function user(){
    	return $this->belongsTo('App\User');
    }

    public function tweetMetrics(){
    	return $this->hasMany('App\TweetMetric', 'twitter_id');
    }
}
