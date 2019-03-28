<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model
{
    //
    public function user(){
    	return $this->belongsTo('App\User');
    }

    public function pageMetrics(){
    	return $this->hasOne('App\PageMetric');
    }

    public function likeSourceMetrics(){
    	return $this->hasOne('App\LikeSourceMetric');
    }

    public function contentActivityByTypeMetrics(){
    	return $this->hasOne('App\ContentActivityByTypeMetric');
    }

    public function fansCountryMetrics(){
    	return $this->hasOne('App\FansCountryMetric');
    }

    public function fansCityMetrics(){
        return $this->hasOne('App\FansCityMetric');
    }

    public function fansFemaleAgeMetrics(){
        return $this->hasOne('App\FansFemaleAgeMetrics');
    }

    public function fansMaleAgeMetrics(){
        return $this->hasOne('App\FansMaleAgeMetrics');
    }
}
