<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostDetailsMetric extends Model
{
	public $timestamps = false;
	
    protected $fillable = [
        'id', 'link', 'comments', 'type', 'message', 'targeting', 'impressions', 'likes', 'love', 'haha', 'wow', 'sad', 'angry', 'engaged_users', 'created_time', 'facebook_page_id'   
        ];
}
