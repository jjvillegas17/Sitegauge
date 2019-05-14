<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FansCountryMetric extends Model
{
    public function facebookPage(){
    	return $this->belongsTo('App\FacebookPage');
    }

    protected $fillable = ["id", "country1", "value1", "country2", "value2", "country3", "value3", "country4", "value4", "country5", "value5", "date_retrieved", "facebook_page_id"];
}
