<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Socialite;
use Facebook\Facebook;

class FacebookController extends Controller
{
    private $fb;

    public function __construct(Facebook $fb, Request $request) {
        $this->fb = $fb;
        $this->fb->setDefaultAccessToken($request->pageToken);
    }
   
    public function getPageLikes(Request $request, $pageId, Facebook $fb) {
        $since = $request->since;
        $until = $request->until;

        $until = date('Y-m-d', strtotime($until . ' + 1 days'));
        $since = date('Y-m-d', strtotime($since . ' - 1 days'));
        
        if(is_null($since) && is_null($until)) {
            $until = date('Y-m-d');
            $since = date('Y-m-d', strtotime('-7 days'));
        }
        else if(is_null($since) && !is_null($until)) {
            $date = \DateTime::createFromFormat('Y-m-d', $since);
            $date->modify('-7 day');
            $since = $date->format('Y-m-d');
        }

        try {
            $graphEdge = $fb->
            get("/{$pageId}/insights?metric=page_fans&since={$since}&until={$until}")->
            getGraphEdge();
            dd($since, $until, $graphEdge->asArray());
        } catch (FacebookSDKException $e) {
            echo $e->getMessage();
        }
    }

    public function getPageViews(Request $request, $pageId, Facebook $fb) {
        $since = $request->since;
        $until = $request->until;

        $until = date('Y-m-d', strtotime($until . ' + 1 days'));
        $since = date('Y-m-d', strtotime($since . ' - 1 days'));
        
        if(is_null($since) && is_null($until)) {
            $until = date('Y-m-d');
            $since = date('Y-m-d', strtotime('-7 days'));
        }
        else if(is_null($since) && !is_null($until)) {
            $date = \DateTime::createFromFormat('Y-m-d', $since);
            $date->modify('-7 day');
            $since = $date->format('Y-m-d');
        }

        try {
            $graphEdge = $fb->
            get("/{$pageId}/insights?metric=page_views_total&since={$since}&until={$until}&period=day")->
            getGraphEdge();
            dd($since, $until, $graphEdge->asArray());
        } catch (FacebookSDKException $e) {
            echo $e->getMessage();
        }
    }

    public function getPagePostEngagements(Request $request, $pageId, Facebook $fb) {
        $since = $request->since;
        $until = $request->until;

        $until = date('Y-m-d', strtotime($until . ' + 1 days'));
        $since = date('Y-m-d', strtotime($since . ' - 1 days'));
        
        if(is_null($since) && is_null($until)) {
            $until = date('Y-m-d');
            $since = date('Y-m-d', strtotime('-7 days'));
        }
        else if(is_null($since) && !is_null($until)) {
            $date = \DateTime::createFromFormat('Y-m-d', $since);
            $date->modify('-7 day');
            $since = $date->format('Y-m-d');
        }

        try {
            $graphEdge = $fb->
            get("/{$pageId}/insights?metric=page_post_engagements&since={$since}&until={$until}&period=day")->
            getGraphEdge();
            dd($since, $until, $graphEdge->asArray());
        } catch (FacebookSDKException $e) {
            echo $e->getMessage();
        }
    }

    public function getPagePostImpressions(Request $request, $pageId, Facebook $fb) {
        $since = $request->since;
        $until = $request->until;

        $until = date('Y-m-d', strtotime($until . ' + 1 days'));
        $since = date('Y-m-d', strtotime($since . ' - 1 days'));
        
        if(is_null($since) && is_null($until)) {
            $until = date('Y-m-d');
            $since = date('Y-m-d', strtotime('-7 days'));
        }
        else if(is_null($since) && !is_null($until)) {
            $date = \DateTime::createFromFormat('Y-m-d', $since);
            $date->modify('-7 day');
            $since = $date->format('Y-m-d');
        }

        try {
            $graphEdge = $fb->
            get("/{$pageId}/insights?metric=page_posts_impressions&since={$since}&until={$until}&period=day")->
            getGraphEdge();
            dd($since, $until, $graphEdge->asArray());
        } catch (FacebookSDKException $e) {
            echo $e->getMessage();
        }
    }

}
