<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContentActivityByTypeMetric extends Model
{
    //
    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }
}
