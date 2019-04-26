<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DateHelper;
use Auth;
use App\User;
use Exception;
use Google_Client;
use Google_Service_Analytics;
use App\GoogleAnalytics;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Dimension;
use App\Services\Google\GoogleParser;
use App\AudienceMetric;
use App\AcquisitionMetric;
use App\BehaviorMetric;

use App\Http\Controllers\API\BaseController as BaseController;

class GoogleController extends BaseController{
	private $client;
    private $analytics;

    public function __construct(Google_Client $client, Request $request){
        // check if the accessToken is expired, if expired refresh here
        if(!$request->token){
            return;
        }
        $this->client = $client;
        $this->client->setAccessToken($request->token);
        $this->analytics = new Google_Service_AnalyticsReporting($this->client);
    }

    public function getAudienceMetrics($profileId){
        $dateCreated = GoogleAnalytics::where("profile_id", "=", $profileId)->value('date_created');
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($dateCreated);
        $dateRange->setEndDate("today");

        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:sessions");
        $sessions->setAlias("sessions");

        $users = new Google_Service_AnalyticsReporting_Metric();
        $users->setExpression("ga:users");
        $users->setAlias("users");

        $newUsers = new Google_Service_AnalyticsReporting_Metric();
        $newUsers->setExpression("ga:newUsers");
        $newUsers->setAlias("new_users");

        $sessionsPerUser = new Google_Service_AnalyticsReporting_Metric();
        $sessionsPerUser->setExpression("ga:sessionsPerUser");
        $sessionsPerUser->setAlias("sessions_per_user");

        $pageviews = new Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");

        $pageviewsPerSession = new Google_Service_AnalyticsReporting_Metric();
        $pageviewsPerSession->setExpression("ga:pageviewsPerSession");
        $pageviewsPerSession->setAlias("pageviews_per_session");

        $avgSessionDuration = new Google_Service_AnalyticsReporting_Metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avg_session_duration");

        $bounceRate = new Google_Service_AnalyticsReporting_Metric();
        $bounceRate->setExpression("ga:bounceRate");
        $bounceRate->setAlias("bounce_rate");


        $date = new Google_Service_AnalyticsReporting_Dimension();
        $date->setName("ga:date");

        // $userType = new Google_Service_AnalyticsReporting_Dimension();
        // $userType->setName("ga:userType");

        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($profileId);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$sessions, $users, $newUsers, $sessionsPerUser, $pageviews, $pageviewsPerSession, $avgSessionDuration, $bounceRate]);
        $request->setDimensions([$date]);
        $request->setIncludeEmptyRows(true);
        $request->setPageSize(100000);
        
        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);
        $arr = GoogleParser::parseAudience($this->analytics->reports->batchGet($body), $profileId);
        AudienceMetric::insert($arr);
        return response()->json($arr);

    }

    public function getAcquisitionMetrics($profileId){
        $dateCreated = GoogleAnalytics::where("profile_id", "=", $profileId)->value('date_created');
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($dateCreated);
        $dateRange->setEndDate("today");

        $users = new Google_Service_AnalyticsReporting_Metric();
        $users->setExpression("ga:users");
        $users->setAlias("users");

        $date = new Google_Service_AnalyticsReporting_Dimension();
        $date->setName("ga:date");

        $channelGrouping = new Google_Service_AnalyticsReporting_Dimension();
        $channelGrouping->setName("ga:channelGrouping");

        // $sourceMedium = new Google_Service_AnalyticsReporting_Dimension();
        // $sourceMedium->setName("ga:sourceMedium");

        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($profileId);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$users]);
        $request->setDimensions([$date, $channelGrouping]);
        $request->setIncludeEmptyRows(true);
        $request->setPageSize(100000);

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);
        $arr = GoogleParser::parseAcquisition($this->analytics->reports->batchGet($body), $profileId);
        AcquisitionMetric::insert($arr);
        return response()->json($arr);
    }

    public function fetchMetrics(Request $request, $profileId){
        $metrics = [];
        $metrics['acquisition']= GoogleAnalytics::find($profileId)->acquisitionMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        $metrics['audience']= GoogleAnalytics::find($profileId)->audienceMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");
        $metrics['behavior']= GoogleAnalytics::find($profileId)->behaviorMetrics->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}");

        return response()->json($metrics);
    }

    public function getBehaviorMetrics($profileId){
        $dateCreated = GoogleAnalytics::where("profile_id", "=", $profileId)->value('date_created');
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($dateCreated);
        $dateRange->setEndDate("today");

        $pageviews = new Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");

        $date = new Google_Service_AnalyticsReporting_Dimension();
        $date->setName("ga:date");

        $pagePath = new Google_Service_AnalyticsReporting_Dimension();
        $pagePath->setName("ga:pagePath");
        // $sourceMedium = new Google_Service_AnalyticsReporting_Dimension();
        // $sourceMedium->setName("ga:sourceMedium");

        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($profileId);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$pageviews]);
        $request->setDimensions([$date, $pagePath]);
        $request->setIncludeEmptyRows(true);
        $request->setPageSize(100000);

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);
        $behaviors = GoogleParser::parseBehavior($this->analytics->reports->batchGet($body), $profileId);
        BehaviorMetric::insert($behaviors);
        return response()->json($behaviors);
    }

    public function getAccounts(Request $request){
    	$analytics = new Google_Service_Analytics($this->client);

        // get all accounts
        $accounts = $analytics->management_accounts->listManagementAccounts()->getItems();
        $all = [];
        foreach ($accounts as $key => $account) {
            $myAccount = [];
            $myAccount['accountId'] = $account->getId();
            $myAccount['accountName'] = $account->getName();
            $myAccount['properties'] = [];
            // get all properties of each account
            $properties = $analytics->management_webproperties
            ->listManagementWebproperties($myAccount['accountId'])->getItems();

            foreach ($properties as $key => $property) {
                $myProperty = [];
                // return response()->json(p$roperty->getCreated());
                $date = date('Y-m-d', strtotime($property->getCreated()));
                $myProperty['dateCreated'] = $date;
                $myProperty['propertyId'] = $property->getId();
                $myProperty['propertyName'] = $property->getName();
                $myProperty['views'] = [];

                // get all views
                $profiles = $analytics->management_profiles
                ->listManagementProfiles($myAccount['accountId'], $myProperty['propertyId'])->getItems();

                foreach ($profiles as $key => $profile) {
                    $myProfile = [];
                    $myProfile['profileId'] = $profile->getId();
                    $myProfile['profileName'] = $profile->getName();
                    array_push($myProperty['views'], $myProfile);
                }
                array_push($myAccount['properties'], $myProperty);
            }
            array_push($all, $myAccount);
        }

        return response()->json($all);
    }

    public function getAccountsofUser($userId){
        $accts = User::find($userId)->googleAnalyticsAccounts;
        return response()->json($accts);
    }

    public function addAccount(Request $request){
        try{
            $googleAnalytics = new GoogleAnalytics();
            $googleAnalytics->date_created = $request->dateCreated;
            $googleAnalytics->token = $request->token;
            $googleAnalytics->refresh_token = $request->refreshToken;
            $googleAnalytics->email = $request->email;
            $googleAnalytics->property_name = $request->propertyName;
            $googleAnalytics->property_id = $request->propertyId;
            $googleAnalytics->profile_name = $request->profileName;
            $googleAnalytics->profile_id = $request->profileId;
            $googleAnalytics->user_id = $request->userId;
            $googleAnalytics->save();
            return $this->sendResponse($googleAnalytics, 'Account succesfully added'); 
        }catch (\Illuminate\Database\QueryException $ex){
            return response()->json($ex->getMessage());
        }catch (Exception $e) {
            return response()->json($e->getMessage());
            // return $this->sendError(['error' => get_class($e)],'Getting metrics failed'); 
        } 
    }

}