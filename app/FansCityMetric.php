<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FansCityMetric extends Model
{
    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }
}
