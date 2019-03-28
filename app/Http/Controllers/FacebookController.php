<?php

namespace App\Http\Controllers;

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

use App\Http\Controllers\API\BaseController as BaseController;

class FacebookController extends BaseController
{
    private $fb;

// The Insights API only offers two years retention. Insights data older than two years is subject to removal.
// Only 90 days of insights can be viewed at one time when using since and until parameters.

    public function __construct(Facebook $fb, Request $request) {
        $this->fb = $fb;
        $this->fb->setDefaultAccessToken($request->pageToken);
    }

    public function addPage(Request $request, Facebook $fb){
        $facebookPage = new FacebookPage;
        $facebookPage->id = $request->pageId;
        $facebookPage->access_token = $request->pageToken;
        $facebookPage->page_name = 'ACSS';
        $facebookPage->user_id = Auth::user()->id;
        $facebookPage->save();
        return response()->json($facebookPage);
    }

    public function getDashboardMetrics(Request $request, $pageId, Facebook $fb){
        // check if there is existing analytics in the page_metrics table
        $lastDate = PageMetric::where('facebook_pages_id', '=', $pageId)->max('date_retrieved');
        
        // set since params to date 2yrs ago
        $time = strtotime("-2 years", time());
        $since = date("Y-m-d", $time);
        $days = 728;

        // if there is existing analytics in the table
        if(!is_null($lastDate)){
            $since = $lastDate;
            // set since params to the 2 days from last date retrieved from the db 
            $now = new \DateTime('now');
            $lastDate = new \DateTime($lastDate);
            $days = $lastDate->diff($now)->days - 2;
        }

        // list the metrics to be fetched
        $batch = [
            'likes' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans&since={$since}"),
            'views' => $fb->request('GET',"/{$pageId}/insights?metric=page_views_total&since={$since}"),
            'impressions' => $fb->request('GET',"/{$pageId}/insights?metric=page_impressions&since={$since}"),
            'engangements' => $fb->request('GET',"/{$pageId}/insights?metric=page_engaged_users&since={$since}"),
            'posts_engagements' => $fb->request('GET',"/{$pageId}/insights?metric=page_post_engagements&since={$since}"),
            'negative_feedback' => $fb->request('GET',"/{$pageId}/insights?metric=page_negative_feedback&since={$since}"),
            
            'video_views' => $fb->request('GET',"/{$pageId}/insights?metric=post_video_views&since={$since}"),
            'new_likes' => $fb->request('GET',"/{$pageId}/insights?metric=page_fan_adds&since={$since}"),
            'content_activity' => $fb->request('GET',"/{$pageId}/insights?metric=page_content_activity&since={$since}&period=day"),
            // 'fans_online' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_online&since={$since}"),
            'content_activity_by_type' => $fb->request('GET',"/{$pageId}/insights?metric=page_content_activity_by_action_type&since={$since}"),
            'like_source' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_by_like_source&since={$since}"),
            'fans_country' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_country&since={$since}"),
            'fans_city' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_city&since={$since}"),
            'fans_gender_age' => $fb->request('GET',"/{$pageId}/insights?metric=page_fans_gender_age&since={$since}")
        ];

        $facebookInsight = new FacebookInsight($fb, $pageId);
        $responses = [];

        // fetch only if analytics date is not up to date
        if($days > 0){
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
        $c = []; // content act by type array
        $l = []; // like source array
        $fcountry = []; // fans country array
        $fcity = []; // fans city array
        $ffemaleAge = []; // fans female age arr
        $fmaleAge = []; // fans male age arr

        for($i = 0; $i<$days; $i++ ){
            $metricObj = FacebookParser::parseGeneral($metrics, $i, $pageId);
            $contentObj = FacebookParser::parseContentActivityType($metrics, $i, $pageId);
            $likeObj = FacebookParser::parseLikeSource($metrics, $i, $pageId);
            $fcountryObj = FacebookParser::parseFansPlace($metrics, $i, $pageId, 'country');
            $fcityObj = FacebookParser::parseFansPlace($metrics, $i, $pageId, 'city');
            $fmaleAgeObj = FacebookParser::parseFansGender($metrics, $i, $pageId, 'm');
            $ffemaleAgeObj = FacebookParser::parseFansGender($metrics, $i, $pageId, 'f');
            // array_push can be done inside parse fxns
            array_push($m, $metricObj);
            array_push($c, $contentObj);
            array_push($l, $likeObj);
            array_push($fcountry, $fcountryObj);
            array_push($fcity, $fcityObj);
            array_push($fmaleAge, $fmaleAgeObj);
            array_push($ffemaleAge, $ffemaleAgeObj);
        }

        // save to db
        PageMetric::insert($m);
        LikeSourceMetric::insert($l);
        ContentActivityByTypeMetric::insert($c);
        FansCountryMetric::insert($fcountry);
        FansCityMetric::insert($fcity);
        FansFemaleAgeMetric::insert($ffemaleAge);
        FansMaleAgeMetric::insert($fmaleAge);


        return $this->sendResponse(['page_metrics' => $m, 'content_activity_by_type_metrics' =>$c, 'like_source_metrics' => $l], 'Metrics succesfully saved');
    }

    // public function getAllMetrics(){
    //     $this->getDashboardMetrics();
    //     $this->getPagePostsDetails();
    // }

    public function getPageLikes(Request $request, $pageId, Facebook $fb){
        // $since = $request->since;
        // $until = $request->until;

        $time = strtotime("-2 years", time());
        $since = date("Y-m-d", $time);

        $facebookInsight = new FacebookInsight($fb, $pageId);

        $response = $facebookInsight->metric('page_fans')
                                    ->since($since)
                                    ->get();

        return response()->json($response);
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
                $graphEdge = $fb->get("/{$postId}?fields=reactions.type(LIKE).summary(1).as(like), reactions.type(LOVE).summary(1).as(love), reactions.type(WOW).summary(1).as(wow), reactions.type(HAHA).summary(1).as(haha), reactions.type(SAD).summary(1).as(sad), reactions.type(ANGRY).summary(1).as(angry), insights.metric(post_impressions,post_engaged_users),created_time,message,type,targeting,comments.summary(1),link")->getGraphNode();

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
            PostDetailsMetric::firstOrCreate($postDetail);
        }

        return response()->json(['post_detail_metrics' => $postsDetails]);
    }

}
