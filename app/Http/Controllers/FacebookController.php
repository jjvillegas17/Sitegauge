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
    public function addPage(Request $request, Facebook $fb, $userId){
        // return response()->json($request);
        try{
            // $user = User::find(7);
            // return response()->json($user->facebookPages()->get());
            $page = FacebookPage::find($request->pageId);
            $facebookPage = empty($page)? new FacebookPage : $page;
            $facebookPage->id = $request->pageId;
            $facebookPage->access_token = $request->pageToken;
            $facebookPage->page_name = $request->pageName;
            $facebookPage->save();
            $accs = User::find($userId)->facebookPages()->where('user_id', $userId)->get();
            // return response()->json($accs);
            if(count($accs) == 0){
                // return response()->json("mt");
                User::find($userId)->facebookPages()->attach($request->pageId);
            }
            return $this->sendResponse($facebookPage, 'Page succesfully added'); 
        }catch (\Illuminate\Database\QueryException $ex){
            return response()->json($ex->getMessage());
        }catch (Exception $e) {
            return response()->json($e->getMessage());
            // return $this->sendError(['error' => get_class($e)],'Getting metrics failed'); 
        } 
    }

    // b, c 
    public function uploadCSV(Request $request, $userId, $pageId){
        $types = ['application/csv','application/excel','application/vnd.ms-excel','application/vnd.msexcel','text/csv','text/anytext','text/comma-separated-values'];

        if ($request->hasFile('file')) {
            $rFile = $request->file('file'); 
            if($rFile && in_array($rFile->getClientMimeType(), $types)) {
                $file = $request->file("file");
                $metricsArr = $this->csvToArray($file, $pageId, $userId);

                foreach ($metricsArr as $key => $metric) {
                    $m = PageMetric::where([
                        ['date_retrieved', $metric['date_retrieved']],
                        ['facebook_page_id', $metric['facebook_page_id']]
                        ])->get();
                    
                    // return response()->json($m);

                    if(empty($m)){ // if wala dun sa 2 year range data, insert
                        PageMetric::insert($metric);
                    }
                    else{
                        $hasSameUploaderId = false;
                        foreach ($m as $key => $row) {
                            if($row->uploader_id == $userId){ // upload only rows that are from upload and not from api
                                $row->likes = $metric['likes'];
                                $row->views = $metric['views'];
                                $row->impressions = $metric['impressions'];
                                $row->engagements = $metric['engagements'];
                                $row->negative_feedback = $metric['negative_feedback'];
                                $row->new_likes = $metric['new_likes'];
                                $row->content_activity = $metric['content_activity'];
                                $row->date_retrieved = $metric['date_retrieved'];
                                $row->facebook_page_id = $metric['facebook_page_id'];
                                $row->video_views = $metric['video_views'];
                                $row->uploader_id = $metric['uploader_id'];
                                $row->save();
                                $hasSameUploaderId = true;
                                break;    
                            }
                            else if(is_null($row->uploader_id)){ // galing from api
                                $hasSameUploaderId = true;
                                break; 
                            }
                        }
                        if($hasSameUploaderId == false){
                            PageMetric::insert($metric);
                        }                          
                    }
                }

                return response()->json($metricsArr);
            }
            return response()->json('invalid file type');
        }
        return response()->json('no file');        
    }

    public function csvToArray($filename, $pageId, $userId){
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
                $d["uploader_id"] = $userId;

                array_push($metrics, $d);
            }
        }
        return $metrics;
    }

    public function getDashboardMetricsFans(Request $request, $pageId, Facebook $fb){
        $this->getPagePostsDetails($request, $pageId, $fb);
        $minDate = FansCountryMetric::where('facebook_page_id', '=', $pageId)->min('date_retrieved');
        $maxDate = FansCountryMetric::where('facebook_page_id', '=', $pageId)->max('date_retrieved');

        $last2Yrs1 = date("Y-m-d", strtotime("-2 years", time() + 3600*8));
        $last2Yrs2 = date("Y-m-d", strtotime("+1 day", strtotime($last2Yrs1)));  // last 2 yrs + 1 day
        $yesterday = date("Y-m-d", strtotime("-2 day", time() + 3600*8));

        // return response()->json([$minDate, $last2Yrs2, $maxDate, $yesterday]);

        $until = "";
        $toRepeat = true;
            
        if($minDate == null){   // if does not have record in the db
            $since = $last2Yrs1; 
        }
        else{    // if has record in the db
            if($minDate <= $last2Yrs2 && $maxDate >= $yesterday){
                return response()->json([]); // up to date
            }
            else{    // not up to date
                $since = $maxDate;
                
                $datetime1 = new \DateTime($since);
                $datetime2 = new \DateTime(date("Y-m-d", time() + 3600*8));
                $days = $datetime1->diff($datetime2)->format("%a");
                
                if($days <= 30){
                    $until = date("Y-m-d", time() + 3600*8);
                    $toRepeat = false;
                }
            }
        }

        $lastMonth = date("Y-m-d", strtotime("-70 days", time() + 3600*8)); // only get the last months analytics of page_fans_online para di masyadong marami

        // return response()->json([$minDate, $last2Yrs2, $maxDate, $yesterday, $since, $until]);
        $days = 728;

        // list the metrics to be fetched
        $batch = [
            'fans_online' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_online&since={$lastMonth}&until={$until}"),
            'fans_country' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_country&since={$since}&until={$until}"),
            // 'fans_city' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_city&since={$since}&until={$until}"),
            // 'fans_gender_age' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_gender_age&since={$since}&until={$until}"),
            // 'content_activity_by_type' => $fb->request('GET',"/{$pageId}/insights?metric=page_content_activity_by_action_type&since={$since}&until={$until}"),
            'like_source' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_by_like_source&since={$since}&until={$until}"),
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
        
        if(!empty($metrics[1][0]['values'])){
            $days = count($metrics[1][0]['values']);
        }
        else{
            $days = 0;
        }

        // return response()->json($metrics);

        for($i = 0; $i<$days; $i++ ){
            $fcountryObj = FacebookParser::parseFansPlace($metrics, $i, $pageId, 'country');
            // $fcityObj = FacebookParser::parseFansPlace($metrics, $i, $pageId, 'city');
            // $fmaleAgeObj = FacebookParser::parseFansGender($metrics, $i, $pageId, 'm');
            // $ffemaleAgeObj = FacebookParser::parseFansGender($metrics, $i, $pageId, 'f');
            // $fonline = FacebookParser::parseFansOnline($metrics, $i, $pageId,$fonline);
            // $contentObj = FacebookParser::parseContentActivityType($metrics, $i, $pageId);
            $likeObj = FacebookParser::parseLikeSource($metrics, $i, $pageId);
            // array_push can be done inside parse fxns

            array_push($fcountry, $fcountryObj);
            // array_push($fcity, $fcityObj);
            // array_push($fmaleAge, $fmaleAgeObj);
            // array_push($ffemaleAge, $ffemaleAgeObj);
            // array_push($c, $contentObj);
            array_push($l, $likeObj);
        }

        $daysOnline = count($metrics[0][0]['values']);

        for($i = 0; $i<$daysOnline; $i++){
            $fonline = FacebookParser::parseFansOnline($metrics, $i, $pageId,$fonline);
        }

        $countries = [];

        foreach ($fcountry as $key => $row) {
            // return response()->json($row);
            $a = FacebookPage::find($pageId)->fansCountryMetrics()->where('date_retrieved', $row['date_retrieved'])->first();
            if(!empty($a)){
                $a->country1 = $row['country1'];
                $a->value1 = $row['value1'];
                $a->country2 = $row['country2'];
                $a->value2 = $row['value2'];
                $a->country3 = $row['country3'];
                $a->value3 = $row['value3'];
                $a->country4 = $row['country4'];
                $a->value4 = $row['value4'];
                $a->country5 = $row['country5'];
                $a->value5 = $row['value5'];
                $a->date_retrieved = $row['date_retrieved'];
                $a->facebook_page_id = $row['facebook_page_id'];
                $a->save();
            }
            else{
                array_push($countries, $row);
            }
        }

        FansCountryMetric::insert($countries);

        $sources = [];
        foreach ($l as $key => $row) {
            $a = FacebookPage::find($pageId)->likeSourceMetrics()->where('date_retrieved', $row['date_retrieved'])->first();

            if(!empty($a)){
                $a->ads = $row['ads'];
                $a->news_feed = $row['news_feed'];
                $a->page_suggestions = $row['page_suggestions'];    
                $a->restored_likes = $row['restored_likes'];
                $a->search = $row['search'];
                $a->your_page = $row['your_page'];
                $a->other = $row['other'];
                $a->date_retrieved = $row['date_retrieved'];
                $a->facebook_page_id = $row['facebook_page_id'];
                $a->save();
            }
            else{
                array_push($sources, $row);
            }
        }

        LikeSourceMetric::insert($sources);

        $onlines = [];
        foreach (array_chunk($fonline, 1000) as $key => $chunk) {
            foreach ($chunk as $key => $row) {
                $a = FacebookPage::find($pageId)->fansOnlineMetrics()->where('date_retrieved', $row['date_retrieved'])->first();

                if(!empty($a)){
                    $a->hour = $row['hour'];
                    $a->fans = $row['fans'];
                    $a->date_retrieved = $row['date_retrieved'];
                    $a->facebook_page_id = $row['facebook_page_id'];
                    $a->save();
                }
                else{
                    array_push($onlines, $row);
                }
            }
            FansOnlineMetric::insert($onlines);
            $onlines = [];
        };

        $resp = ['fans_country_metrics' => $fcountry, 'fans_city_metrics' => $fcity, 'fans_female_age_metrics' => $ffemaleAge, 'fans_male_age_metrics' => $fmaleAge, 'fans_online_metrics' => $fonline, 'content_activity_by_type_metrics' =>$c, 'like_source_metrics' => $l];

        if($toRepeat == true){
            $resp = $this->getDashboardMetrics($request, $pageId, $fb);
        }
        
        return $this->sendResponse($resp, 'Metrics succesfully saved');
    }
            

    public function getDashboardMetrics(Request $request, $pageId, Facebook $fb){
        // get min date of page metric of page id x that is from API (null uploader_id)
        $minDate = PageMetric::where('facebook_page_id', '=', $pageId)->whereNull('uploader_id')->min('date_retrieved');
        $maxDate = PageMetric::where('facebook_page_id', '=', $pageId)->whereNull('uploader_id')->max('date_retrieved');

        $last2Yrs1 = date("Y-m-d", strtotime("-2 years", time() + 3600*8));
        $last2Yrs2 = date("Y-m-d", strtotime("+1 day", strtotime($last2Yrs1)));  // last 2 yrs + 1 day
        $yesterday = date("Y-m-d", strtotime("-1 day", time() + 3600*8));

        // return response()->json([$minDate, $last2Yrs2, $maxDate, $yesterday]);

        $until = "";
        $toRepeat = true;
            
        if($minDate == null){   // if does not have record in the db
            $since = $last2Yrs1; 
        }
        else{    // if has record in the db
            if($minDate <= $last2Yrs2 && $maxDate >= $yesterday){
                return response()->json([]); // up to date
            }
            else{    // not up to date
                $since = $maxDate;
                
                $datetime1 = new \DateTime($since);
                $datetime2 = new \DateTime(date("Y-m-d", time() + 3600*8));
                $days = $datetime1->diff($datetime2)->format("%a");
                
                if($days <= 30){
                    $until = date("Y-m-d", time() + 3600*8);
                    $toRepeat = false;
                }
            }
        }

        // return response()->json([$minDate, $last2Yrs2, $maxDate, $yesterday, $since, $until]);
        $days = 728;
  
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

        // return response()->json($metrics);

        for($i = 0; $i<$days; $i++ ){
            $metricObj = FacebookParser::parseGeneral($metrics, $i, $pageId);

            array_push($m, $metricObj);
        }
        // save to db
        $rowsToInsert = [];
        foreach ($m as $key => $row){
            $a = FacebookPage::find($pageId)->pageMetrics()->where('date_retrieved', $row['date_retrieved'])->first();

            if(!empty($a)){
                $a->likes = $row['likes'];
                $a->views = $row['views'];
                $a->impressions = $row['impressions'];
                $a->engagements = $row['engagements'];
                $a->posts_engagements = $row['posts_engagements'];
                $a->content_activity = $row['content_activity'];
                $a->negative_feedback = $row['negative_feedback'];
                $a->new_likes = $row['new_likes'];
                $a->video_views = $row['video_views'];
                $a->date_retrieved = $row['date_retrieved'];
                $a->uploader_id = $row['uploader_id'];
                $a->facebook_page_id = $row['facebook_page_id'];
                $a->save();
            }
            else{
                array_push($rowsToInsert, $row);
            }
        }

        PageMetric::insert($rowsToInsert);

        $resp = ['page_metrics' => $m,];
        
        if($toRepeat == true){
            $resp = $this->getDashboardMetrics($request, $pageId, $fb);
        } 
        
        return $this->sendResponse($resp, 'Metrics succesfully saved');
    }
    
    // /fb/{userId}/pages
    public function getPagesOfUser($userId){
        $pages = User::find($userId)->facebookPages;
        return response()->json($pages);
    }
    // params: start, end, pageId
    public function fetchMetrics(Request $request, $userId, $pageId){
        $metrics = [];
        $metrics['page_metrics']= FacebookPage::find($pageId)->pageMetrics()->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}")->where(function ($query) use ($userId) {
                $query->where("uploader_id", "{$userId}")
                    ->orWhereNull("uploader_id");
            })->orderBy("date_retrieved", "asc")->get();
        return response()->json($metrics);

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

    public function getMinDate($userId, $pageId){
        $min = FacebookPage::find($pageId)->pageMetrics()->where(function ($query) use ($userId) {
                $query->where("uploader_id", "{$userId}")
                    ->orWhereNull("uploader_id");
            })->min('date_retrieved');

        return $this->sendResponse($min, 'Minimum date succesfully fetched');
    }

    public function deletePage($userId, $pageId){
        User::find($userId)->facebookPages()->detach($pageId);
        $metric = FacebookPage::find($pageId)->pageMetrics()->where('uploader_id', $userId)->delete();
        return response()->json([]);
    }
    
}