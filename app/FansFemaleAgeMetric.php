<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FansFemaleAgeMetric extends Model
{
    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }
}
