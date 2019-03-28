<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FansMaleAgeMetric extends Model
{
    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }
}
