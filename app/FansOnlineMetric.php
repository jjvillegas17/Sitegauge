<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FansOnlineMetric extends Model
{
    //
    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }

    protected $fillable = ['id', 'hour', 'fans', 'date_retrieved', 'facebook_page_id'];
}
