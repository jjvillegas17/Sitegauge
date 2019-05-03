<?php

namespace App\Http\Controllers;
set_time_limit(60);
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Socialite;
use Facebook\Facebook;
use App\Services\Facebook\FacebookInsight;
use App\Services\Facebook\FacebookParser;
use App\Services\DateHelper;
use App\FacebookPage;
use Auth;
use App\User;
use App\PageMetric;
use App\ContentActivityByTypeMetric;
use App\LikeSourceMetric;
use App\FansCountryMetric;
use App\FansCityMetric;
use App\PostDetailsMetric;
use App\FansFemaleAgeMetric;
use App\FansMaleAgeMetric;
use App\FansOnlineMetric;
use Exception;
use App\Http\Controllers\API\BaseController as BaseController;
class FacebookController extends BaseController
{
    private $fb;
    public function __construct(Facebook $fb, Request $request) {
        $this->fb = $fb;
        $token = explode('=', $request->pageToken);
        $this->fb->setDefaultAccessToken($token[0]);
    }
    public function addPage(Request $request, Facebook $fb){
        // return response()->json($request);
        try{
            $facebookPage = new FacebookPage;
            $facebookPage->id = $request->pageId;
            $facebookPage->access_token = $request->pageToken;
            $facebookPage->page_name = $request->pageName;
            $facebookPage->user_id = $request->userId;
            $facebookPage->save();
            return $this->sendResponse($facebookPage, 'Page succesfully added'); 
        }catch (\Illuminate\Database\QueryException $ex){
            return response()->json($ex->getMessage());
        }catch (Exception $e) {
            return response()->json($e->getMessage());
            // return $this->sendError(['error' => get_class($e)],'Getting metrics failed'); 
        } 
    }

    // b, c 
    public function uploadCSV(Request $request, $pageId){
        $types = ['application/csv','application/excel','application/vnd.ms-excel','application/vnd.msexcel','text/csv','text/anytext','text/comma-separated-values'];

        if ($request->hasFile('file')) {
            $rFile = $request->file('file'); 
            if($rFile && in_array($rFile->getClientMimeType(), $types)) {
                $file = $request->file("file");
                $metricsArr = $this->csvToArray($file, $pageId);

                foreach ($metricsArr as $key => $metric) {
                    $m = PageMetric::where([
                        ['date_retrieved', $metric['date_retrieved']],
                        ['facebook_page_id', $metric['facebook_page_id']]
                        ])->first();
                    if(empty($m)){
                        PageMetric::insert($metric);
                    }
                    else{ // update only the accurate fields if there's existing page metric
                        $m->likes = $metric['likes'];
                        $m->views = $metric['views'];
                        $m->impressions = $metric['impressions'];
                        $m->engagements = $metric['engagements'];
                        $m->negative_feedback = $metric['negative_feedback'];
                        $m->new_likes = $metric['new_likes'];
                        $m->content_activity = $metric['content_activity'];
                        $m->date_retrieved = $metric['date_retrieved'];
                        $m->facebook_page_id = $metric['facebook_page_id'];
                        $m->video_views = $metric['video_views'];
                        $m->save();
                    }
                }

                return response()->json($metricsArr);
            }
            return response()->json('invalid file type');
        }
        return response()->json('no file');        
    }

    public function csvToArray($filename, $pageId){
        if (!file_exists($filename))
            return false;

        $metrics = [];
        if(($handle = fopen($filename, 'r')) !== false){
            $header = fgetcsv($handle);
            $header[0] = "Date";
            // return $header;
            $c = 0;
            while ($row = fgetcsv($handle)) {
                if($c == 0){
                    $c++;
                    continue;
                }

                $d = [];
                $data = array_combine($header, $row);
                $d["likes"] = (int) $data["Lifetime Total Likes"];
                $d["views"] = (int) $data["Daily Logged-in Page Views"]; // not accurate
                $d["impressions"] = (int) $data["Daily Total Impressions"];
                $d["engagements"] = (int) $data["Daily Page Engaged Users"];
                $d["posts_engagements"] = 0;  // not included in csv so 0
                $d["content_activity"] = (int) $data["Daily Page Stories By Story Type - checkin"] + (int) $data["Daily Page Stories By Story Type - coupon"] + (int) $data["Daily Page Stories By Story Type - event"] + (int) $data["Daily Page Stories By Story Type - fan"] + (int) $data["Daily Page Stories By Story Type - mention"] + (int) $data["Daily Page Stories By Story Type - other"] + (int) $data["Daily Page Stories By Story Type - page post"] + (int) $data["Daily Page Stories By Story Type - question"] + (int) $data["Daily Page Stories By Story Type - user post"];   // not included in csv so 0
                $d["negative_feedback"] = (int) $data["Daily Negative Feedback"];
                $d["new_likes"] =(int)  $data["Daily New Likes"];
                $d["date_retrieved"] = date('Y-m-d', strtotime($data["Date"] . ' +1 day'));
                $d["video_views"] = (int) $data["Daily Total Video Views"];
                $d["facebook_page_id"] = (int) $pageId;

                array_push($metrics, $d);
            }
        }
        return $metrics;
    }

    public function getDashboardMetricsFans(Request $request, $pageId, Facebook $fb){
        $this->getPagePostsDetails($request, $pageId, $fb);
        $lastDate = FansCountryMetric::where('facebook_page_id', '=', $pageId)->max('date_retrieved');

         if($lastDate == date("Y-m-d", strtotime("-3 days",time()))){
            return response()->json([]);
        }

        $time = strtotime("-2 years", time());
        $since = date("Y-m-d", $time);
        $days = 728;
        // if there is existing analytics in the table
        if(!is_null($lastDate)){
            // add 2 days from the last date retrieved from the db
            if($since == DateHelper::addDays($lastDate, 2)){
                $since = DateHelper::addDays($lastDate, 2); 
            }
            else{
                $since = $lastDate;
            }
        }

        // list the metrics to be fetched
        $batch = [
            'fans_online' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_online&since={$since}"),
            'fans_country' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_country&since={$since}"),
            'fans_city' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_city&since={$since}"),
            'fans_gender_age' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_gender_age&since={$since}"),
            'content_activity_by_type' => $fb->request('GET',"/{$pageId}/insights?metric=page_content_activity_by_action_type&since={$since}"),
            'like_source' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_by_like_source&since={$since}"),
        ];

        $facebookInsight = new FacebookInsight($fb, $pageId);
        $responses = [];
        // fetch only if analytics date is not up to date
        if($days > 1){
            $responses = $facebookInsight->getBatchRequest($batch);
        }

        if($responses == 'error'){
            dd('error');
            return $this->sendError(['error' => 'api_something_went_wrong'],'Getting metrics failed');
        }

        $i = 0;
        $metrics = [];
        foreach($responses as $key => $response){   // put all graph edge response to metrics arr
          if ($response->isError()){
            $e = $response->getThrownException();
            return $this->sendError(['error' => 'api_something_went_wrong'],'Getting metrics failed');
            dd($e->getResponse());
          } 
          else {
            $decodedResp = json_decode($response->getGraphEdge(),true);
            array_push($metrics, $decodedResp);
          }
        }

        $fonline = []; // fans online array
        $fcountry = []; // fans country array
        $fcity = []; // fans city array
        $ffemaleAge = []; // fans female age arr
        $fmaleAge = []; // fans male age arr
        $c = [];
        $l = [];
        $days = count($metrics[0][0]['values']);
        for($i = 0; $i<$days; $i++ ){
            $fcountryObj = FacebookParser::parseFansPlace($metrics, $i, $pageId, 'country');
            $fcityObj = FacebookParser::parseFansPlace($metrics, $i, $pageId, 'city');
            $fmaleAgeObj = FacebookParser::parseFansGender($metrics, $i, $pageId, 'm');
            $ffemaleAgeObj = FacebookParser::parseFansGender($metrics, $i, $pageId, 'f');
            $fonline = FacebookParser::parseFansOnline($metrics, $i, $pageId,$fonline);
            $contentObj = FacebookParser::parseContentActivityType($metrics, $i, $pageId);
            $likeObj = FacebookParser::parseLikeSource($metrics, $i, $pageId);
            // array_push can be done inside parse fxns

            array_push($fcountry, $fcountryObj);
            array_push($fcity, $fcityObj);
            array_push($fmaleAge, $fmaleAgeObj);
            array_push($ffemaleAge, $ffemaleAgeObj);
            array_push($c, $contentObj);
            array_push($l, $likeObj);
        }

        // save to db
        FansCountryMetric::insert($fcountry);
        FansCityMetric::insert($fcity);
        FansFemaleAgeMetric::insert($ffemaleAge);
        FansMaleAgeMetric::insert($fmaleAge);
        LikeSourceMetric::insert($l);
        ContentActivityByTypeMetric::insert($c);
        foreach (array_chunk($fonline, 1000) as $key => $arr) {
            FansOnlineMetric::insert($arr);
        }

        $resp = ['fans_country_metrics' => $fcountry, 'fans_city_metrics' => $fcity, 'fans_female_age_metrics' => $ffemaleAge, 'fans_male_age_metrics' => $fmaleAge, 'fans_online_metrics' => $fonline, 'content_activity_by_type_metrics' =>$c, 'like_source_metrics' => $l];
         
        if(!is_null($lastDate))
            return $this->sendResponse($resp, 'Metrics succesfully updated');
        return $this->sendResponse($resp, 'Metrics succesfully saved');
    }

    public function getDashboardMetrics(Request $request, $pageId, Facebook $fb){
        // check if there is existing analytics in the page_metrics table
        $minDate = PageMetric::where('facebook_page_id', '=', $pageId)->min('date_retrieved');
        $maxDate = PageMetric::where('facebook_page_id', '=', $pageId)->max('date_retrieved');

        $last2Yrs1 = date("Y-m-d", strtotime("-2 years", time() + 3600*8));
        $last2Yrs2 = date("Y-m-d", strtotime("+1 day", strtotime($last2Yrs1)));  // last 2 yrs + 1 day
        $yesterday = date("Y-m-d", strtotime("-1 day", time() + 3600*8));

        // return response()->json([$last2Yrs, $yesterday]);

        $until = "";
        if($minDate != $last2Yrs2){
            $since = $last2Yrs1;
        }
        else{
            if($minDate == $last2Yrs2 && $maxDate == $yesterday){
                return response()->json([]); // up to date
            }
            else{
                $since = $maxDate;
                $until = date("Y-m-d", time() + 3600*8);
            }
        }

        // // set since params to date 2yrs ago
        // $time = strtotime("-2 years", time());
        // $since = date("Y-m-d", $time);
        $days = 728;
        // if there is existing analytics in the table
        // if(!is_null($lastDate)){
        //     // add 2 days from the last date retrieved from the db
        //     if($since == DateHelper::addDays($lastDate, 2)){
        //         $since = DateHelper::addDays($lastDate, 2); 
        //     }
        //     else{
        //         $since = $lastDate;
        //     }
        // }

        // list the metrics to be fetched
        
        $batch = [
            'likes' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans&since={$since}&until={$until}"),
            'views' => $fb->request('GET',"/{$pageId}/insights?metric=page_views_total&since={$since}&until={$until}"),
            'impressions' => $fb->request('GET',"/{$pageId}/insights?metric=page_impressions&since={$since}&until={$until}"),
            'engangements' => $fb->request('GET',"/{$pageId}/insights?metric=page_engaged_users&since={$since}&until={$until}"),
            'posts_engagements' => $fb->request('GET',"/{$pageId}/insights?metric=page_post_engagements&since={$since}&until={$until}"),
            'negative_feedback' => $fb->request('GET',"/{$pageId}/insights?metric=page_negative_feedback&since={$since}&until={$until}"),
            'video_views' => $fb->request('GET',"/{$pageId}/insights?metric=page_video_views&since={$since}&until={$until}"),
            'new_likes' => $fb->request('GET',"/{$pageId}/insights?metric=page_fan_adds&since={$since}&until={$until}"),
            'content_activity' => $fb->request('GET',"/{$pageId}/insights?metric=page_content_activity&period=day&since={$since}&until={$until}"),
        ];
        $facebookInsight = new FacebookInsight($fb, $pageId);
        $responses = [];
        // fetch only if analytics date is not up to date

        if($days > 1){
            $responses = $facebookInsight->getBatchRequest($batch);
        }
        
        if($responses == 'error'){
            dd('error');
            return $this->sendError(['error' => 'api_something_went_wrong'],'Getting metrics failed');
        }
        $i = 0;
        $metrics = [];
        foreach($responses as $key => $response){   // put all graph edge response to metrics arr
          if ($response->isError()){
            $e = $response->getThrownException();
            return $this->sendError(['error' => 'api_something_went_wrong'],'Getting metrics failed');
            dd($e->getResponse());
          } 
          else {
            $decodedResp = json_decode($response->getGraphEdge(),true);
            array_push($metrics, $decodedResp);
          }
        }

        $m = []; // gen metrics array
        $days = count($metrics[0][0]['values']);

        for($i = 0; $i<$days; $i++ ){
            $metricObj = FacebookParser::parseGeneral($metrics, $i, $pageId);

            array_push($m, $metricObj);
        }
        // save to db
        $rowsToInsert = [];
        foreach ($m as $key => $row){
            PageMetric::updateOrCreate(['date_retrieved' => $row['date_retrieved']], $row);
            // $n = PageMetric::firstOrNew(['date_retrieved', $row['date_retrieved']], $row);
            // if(empty($n)){
            //     array_push($rowsToInsert, $n);
            // }
            // else{
            //     PageMetric::updateOrCreate();
            // }
        }
        
        $resp = ['page_metrics' => $m];
         
        return $this->sendResponse($resp, 'Metrics succesfully saved');
    }
    
    // /fb/{userId}/pages
    public function getPagesOfUser($userId){
        $pages = User::find($userId)->facebookPages;
        return response()->json($pages);
    }
    // params: start, end, pageId
    public function fetchMetrics(Request $request, $pageId){
        $metrics = [];
        $metrics['page_metrics']= FacebookPage::find($pageId)->pageMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        // $metrics['like_source_metrics'] = FacebookPage::find($pageId)->likeSourceMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        // $metrics['content_activity_by_type_metrics'] = FacebookPage::find($pageId)->contentActivityByTypeMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        // $metrics['fans_country_metrics'] = FacebookPage::find($pageId)->fansCountryMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        // $metrics['fans_city_metrics'] = FacebookPage::find($pageId)->fansCityMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        // $metrics['fans_female_age_metrics'] = FacebookPage::find($pageId)->fansFemaleAgeMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        // $metrics['fans_male_age_metrics'] = FacebookPage::find($pageId)->fansMaleAgeMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        return $this->sendResponse($metrics, 'Metrics succesfully fetched');
    }

    // get the 1st 5 page posts
    public function getPagePostsId(Request $request, $pageId, Facebook $fb){
        try{
            $graphEdge = $fb->get("/{$pageId}/posts?limit=5")->getGraphEdge();
            $response = $graphEdge->asArray();
            $posts = [];
            foreach ($response as $post) {
                array_push($posts, $post['id']);
            }
            return $posts;
        } catch (FacebookSDKException $e) {
            echo $e->getMessage();
        }
    }
    // get the 1st 5 page posts and its info
    // 341693319367148_944388825764258?fields=insights.metric(post_reactions_wow_total).period(lifetime)
    public function getPagePostsDetails(Request $request, $pageId, Facebook $fb){
        $postsId = $this->getPagePostsId($request, $pageId, $fb);
        $postsDetails = [];
        foreach($postsId as $postId){
            try{
                $graphEdge = $fb->get("/{$postId}?fields=reactions.type(LIKE).summary(1).as(like), reactions.type(LOVE).summary(1).as(love), reactions.type(WOW).summary(1).as(wow), reactions.type(HAHA).summary(1).as(haha), reactions.type(SAD).summary(1).as(sad), reactions.type(ANGRY).summary(1).as(angry), created_time,message,type,targeting,comments.summary(1),link,insights.metric(post_impressions,post_engaged_users)")->getGraphNode();
                $arr = $graphEdge->asArray();
                $arr = FacebookParser::parseReactions($arr, $graphEdge);
                array_push($postsDetails, $arr);
            } catch (\FacebookSDKException $e) {
                return $this->sendError(['error' => 'api_something_went_wrong'],'Getting metrics failed');
                echo $e->getMessage();
            }
        }
        $postsDetails = FacebookParser::parsePostsDetails($postsDetails, $pageId);
        foreach ($postsDetails as $key => $postDetail) {
            PostDetailsMetric::updateOrCreate(["id" => $postDetail["id"]],$postDetail);
        }
        return response()->json(['post_detail_metrics' => $postsDetails]);
    }
    public function getMinDate($pageId){
        $min = FacebookPage::find($pageId)->pageMetrics->min('date_retrieved');
        return $this->sendResponse($min, 'Minimum date succesfully fetched');
    }
    
}