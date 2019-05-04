<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DateHelper;
use Auth;
use App\User;
use Exception;
use App\FacebookPage;
use App\LikeSourceMetric;
use App\FansOnlineMetric;

use App\Http\Controllers\API\BaseController as BaseController;

class InsightController extends BaseController{

	public function getMostEngagedPost($pageId, Request $request){
		$post = \DB::select(\DB::raw("SELECT * from post_details_metrics where facebook_page_id = :id1 and engaged_users = (select max(engaged_users) from post_details_metrics where facebook_page_id = :id2)"), ['id1' => $pageId, 'id2' => $pageId]);
		return response()->json($post);
	}

	public function getTopLikeSource($pageId){
		$maxDate = LikeSourceMetric::max('date_retrieved');
		$maxDate = strtotime($maxDate);
		$end = date('Y-m-d', strtotime('-7 days', $maxDate));
		$start = date('Y-m-d', strtotime('-14 days', $maxDate));

		$lastWeek = \DB::select(\DB::raw("SELECT sum(ads) as `ads` ,sum(news_feed) as `news feed`, sum(page_suggestions) as `page suggestions`, sum(restored_likes) as `restored likes`, sum(search) as `search`, sum(your_page) as `your page`, sum(other) as `other` FROM like_source_metrics WHERE facebook_page_id = :id and date_retrieved between :start and :end"), ['id' => $pageId, 'start' => $start, 'end' => $end]);

		$topSource = "";
		$max = 0;
		$tops = [];

		foreach (get_object_vars($lastWeek[0]) as $key => $value) {
			if($max <= $value){
				$max = $value;
				$topSource = $key;
			}
		}

		if(!is_null($max)){
			$topweek[ucwords($topSource)] = $max;
			array_push($tops, $topweek);			
		}


		$end = date('Y-m-d', strtotime('-30 days', $maxDate));
		$start = date('Y-m-d', strtotime('-60 days', $maxDate));

		$lastWeek = \DB::select(\DB::raw("SELECT sum(ads) as `ads` ,sum(news_feed) as `news feed`, sum(page_suggestions) as `page suggestions`, sum(restored_likes) as `restored likes`, sum(search) as `search`, sum(your_page) as `your page`, sum(other) as `other` FROM like_source_metrics WHERE facebook_page_id = :id and date_retrieved between :start and :end"), ['id' => $pageId, 'start' => $start, 'end' => $end]);

		$topSource = "";
		$max = 0;
		foreach (get_object_vars($lastWeek[0]) as $key => $value) {
			if($max <= $value){
				$max = $value;
				$topSource = $key;
			}
		}

		if(!is_null($max)){
			$topmonth[ucwords($topSource)] = $max;
			array_push($tops, $topmonth);
		}

		return response()->json($tops); 		
	}

	public function getLikePeakDates($pageId){  // new_like/avg*100 > 300%
		$peakDates = \DB::select(\DB::raw("SELECT  new_likes, cast(new_likes/(select avg(new_likes) from page_metrics where facebook_page_id = :id0)*100 as unsigned) as `avg`, date_retrieved from page_metrics where facebook_page_id = :id1 and new_likes/(select avg(new_likes) from page_metrics where facebook_page_id = :id2)*100 >= 300 and year(date_retrieved) = year(curdate()) order by new_likes desc limit 5"), ['id0' => $pageId,'id1' => $pageId, 'id2' => $pageId]);

		return response()->json($peakDates);
	}

	public function getBestTimeToPost($pageId){
		$maxDate = FansOnlineMetric::max('date_retrieved');
		$maxDate = strtotime($maxDate);
		$end = date('Y-m-d', strtotime('-7 days', $maxDate));
		$start = date('Y-m-d', strtotime('-14 days', $maxDate));

		$bestLastWeek = \DB::select(\DB::raw("SELECT hour, sum(fans) as `fans` from fans_online_metrics where date_retrieved between :start and :end and facebook_page_id = :id group by hour order by sum(fans) desc limit 5"), ["start" => $start, "end" => $end, "id" => $pageId]);
		
		$time = [];
		if(!empty($bestLastWeek))
			array_push($time, $bestLastWeek);
		
		$end = date('Y-m-d', strtotime('-30 days', $maxDate));
		$start = date('Y-m-d', strtotime('-60 days', $maxDate));	

		$bestLastMonth = \DB::select(\DB::raw("SELECT hour, sum(fans) as `fans` from fans_online_metrics where date_retrieved between :start and :end and facebook_page_id = :id group by hour order by sum(fans) desc limit 5"), ["start" => $start, "end" => $end, "id" => $pageId]);

		if(!empty($bestLastWeek))
			array_push($time, $bestLastMonth);

		return response()->json($time);		
	}

}