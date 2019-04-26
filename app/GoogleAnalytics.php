<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoogleAnalytics extends Model
{
   protected $primaryKey = 'profile_id';
   
   public function user(){
    	return $this->belongsTo('App\User');
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
