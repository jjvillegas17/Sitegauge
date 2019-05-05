<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TweetMetric extends Model
{
    //
    public $timestamps = false;
    
    protected $fillable = ['id', 'link', 'text', 'created_date', 'impressions', 'engagements', 'engagement_rate', 'retweets', 'replies', 'likes', 'follows', 'user_profile_clicks', 'media_views', 'media_engagements', 'uploader_id', 'twitter_id'];
}
