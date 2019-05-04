<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model
{
    public function users(){
    	return $this->belongsToMany('App\User', 'user_pages');
    }

    public function pageMetrics(){
    	return $this->hasMany('App\PageMetric');
    }

    public function postDetailsMetrics(){
        return $this->hasMany('App\PostDetailsMetric', 'facebook_page_id');
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

    public function fansOnlineMetrics(){
        return $this->hasMany('App\FansOnlineMetric');
    }
}
