<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcquisitionMetric extends Model
{
    //
    protected $fillable = ["id", "date_retrieved", "direct", "organic_search", "social", "other", "referral", "profile_id", "created_at", "updated_at"];
}
