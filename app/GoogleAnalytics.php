<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoogleAnalytics extends Model
{
   protected $primaryKey = 'profile_id';
   
   public function users(){
    	return $this->belongsToMany('App\User', 'user_google_analytics');
   }

   public function acquisitionMetrics(){
    	return $this->hasMany('App\AcquisitionMetric', 'profile_id');
    }

    public function audienceMetrics(){
    	return $this->hasMany('App\AudienceMetric', 'profile_id');
    }

    public function behaviorMetrics(){
    	return $this->hasMany('App\BehaviorMetric', 'profile_id');
    }

}
