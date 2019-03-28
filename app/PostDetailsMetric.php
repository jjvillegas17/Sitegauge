<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostDetailsMetric extends Model
{
    protected $fillable = [
        'id', 'link', 'comments', 'impressions', 'likes', 'love', 'haha', 'wow', 'sad', 'angry', 'engaged_users', 'created_time', 'facebook_pages_id'   
        ];
}
