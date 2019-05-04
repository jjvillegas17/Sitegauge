<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BehaviorMetric extends Model
{
    //
    protected $fillable = ["id", "pageviews", "page_path", "date_retrieved", "profile_id", "created_at", "updated_at"];
}
