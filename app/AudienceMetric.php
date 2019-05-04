<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AudienceMetric extends Model
{
    //
    protected $fillable = ["id", "sessions", "users", "new_users", "sessions_per_user", "pageviews", "pages_per_session", "avg_session_duration", "bounce_rate", "date_retrieved", "profile_id", "created_at", "updated_at"];
}
