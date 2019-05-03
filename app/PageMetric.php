<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PageMetric extends Model
{
	protected $fillable = [
        'id', 'likes', 'views', 'impressions', 'engagements', 'posts_engagements', 'content_activity', 'negative_feedback', 'new_likes', 'video_views', 'date_retrieved', 'facebook_page_id',   
        ];

    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }
}
