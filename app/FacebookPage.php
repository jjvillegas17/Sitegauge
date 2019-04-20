<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model
{
    public function user(){
    	return $this->belongsTo('App\User');
    }

    public function pageMetrics(){
    	return $this->hasMany('App\PageMetric');
    }

    public function likeSourceMetrics(){
    	return $this->hasMany('App\LikeSourceMetric');
    }

    public function contentActivityByTypeMetrics(){
    	return $this->hasMany('App\ContentActivityByTypeMetric');
    }

    public function fansCountryMetrics(){
    	return $this->hasMany('App\FansCountryMetric');
    }

    public function fansCityMetrics(){
        return $this->hasMany('App\FansCityMetric');
    }

    public function fansFemaleAgeMetrics(){
        return $this->hasMany('App\FansFemaleAgeMetric');
    }

    public function fansMaleAgeMetrics(){
        return $this->hasMany('App\FansMaleAgeMetric');
    }
}
