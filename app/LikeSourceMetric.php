<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LikeSourceMetric extends Model
{
    //
	protected $fillable = ["id", "ads", "news_feed", "page_suggestions", "restored_likes", "search", "your_page", "other", "date_retrived", "facebook_page_id"];
}
