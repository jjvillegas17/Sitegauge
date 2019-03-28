<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LikeSourceMetric extends Model
{
    //
    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }
}
