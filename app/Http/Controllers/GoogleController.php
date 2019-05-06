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

        $acct = GoogleAnalytics::find($request->profileId);
        if(empty($acct)){
            $this->client->setAccessToken($request->token);
            return;
        }

        $accessToken = ["access_token" => $acct->getAttributes()["token"], "refresh_token" => $acct->getAttributes()["refresh_token"],"created" => (int) $acct->getAttributes()["created"], "expires_in" => $acct->getAttributes()["expires_in"]];
        $this->client->setAccessToken($accessToken);
        $this->client->setAccessType("offline");

        if($this->client->isAccessTokenExpired()){
            $refreshToken = $this->client->getRefreshToken();
            $this->client->refreshToken($refreshToken);
            $newAccessToken = $this->client->getAccessToken();
            
            //update model
            $acct->token = $newAccessToken["access_token"];
            $acct->save();
        }

        $this->analytics = new Google_Service_AnalyticsReporting($this->client);
    }

    public function uploadCSV(Request $request, $userId, $profileId, $metric){
        $types = ['application/csv','application/excel','application/vnd.ms-excel','application/vnd.msexcel','text/csv','text/anytext','text/comma-separated-values'];

        if ($request->hasFile('file')) {
            $rFile = $request->file('file'); 
            if($rFile && in_array($rFile->getClientMimeType(), $types)) {
                $file = $request->file("file");
                $metricsArr = $this->csvToArrayAudience($file, $profileId, $metric, $userId);
                    // return response()->json($metricsArr);

                foreach ($metricsArr as $key => $metric) {
                    // return response()->json($metric);
                    $m = AudienceMetric::where([
                    ['date_retrieved', $metric['date_retrieved']],
                    ['profile_id', $metric['profile_id']]
                    ])->get();

                    if(empty($m)){
                        AudienceMetric::insert($metric);
                    }
                    else{
                        $hasSameUploaderId = false;
                        foreach ($m as $key => $row) {
                            if($row->uploader_id == $userId){
                                $row->{strtolower(preg_replace('/\s+/', '_', $request->metric))} = $metric[strtolower(preg_replace('/\s+/', '_', $request->metric))];
                                $row->uploader_id = $metric["uploader_id"];
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
                            AudienceMetric::insert($metric);
                        } 
                    }
                }

                return response()->json($metricsArr);
            }
            return response()->json('invalid file type');
        }
        return response()->json('no file');        
    }

    public function csvToArrayAudience($filename, $profileId, $metric, $userId){
        if (!file_exists($filename))
            return false;

        $metrics = [];
        if(($handle = fopen($filename, 'r')) !== false){
            $c = 0;
            while ($row = fgetcsv($handle)) {
                if($c <=5 ){
                    $c++;
                    continue;
                }
                if($c == 6){
                    $header = $row;
                    // validate if header[1] == metric for csv file checking
                    $header[1] = $metric;
                    $c++;
                    continue;
                }

                $d = [];
                $d["users"] = 0;
                $d["sessions"] = 0;
                $d["new_users"] = 0;
                $d["sessions_per_user"] = 0;
                $d["pageviews"] = 0;
                $d["pages_per_session"] = 0;   
                $d["avg_session_duration"] = 0; 
                $d["bounce_rate"] = 0;

                $data = array_combine($header, $row);
                $metricName = strtolower(preg_replace('/\s+/', '_', $metric)); // snakecase the metricName
                $d[$metricName] = $data[$metric];
                if($metricName == "bounce_rate"){
                    $d[$metricName] = str_replace("%", "", $data[$metric]);    
                }
                if($metricName == "avg_session_duration"){
                    $time = strtotime($data[$metric]);
                    $time = date("H:i:s", $time);
                    $time = explode(':', $time);
                    $mins = ((int) $time[1]*60 + (int) $time[2])/60;
                    $mins = round($mins, 2);
                    $d[$metricName] = $mins;    
                }
                $d["date_retrieved"] = date("Y-m-d", strtotime($data["Day Index"]));
                $d["profile_id"] = $profileId;
                $d["uploader_id"] = $userId;
                array_push($metrics, $d);
            }
        }
        array_pop($metrics);
        return $metrics;
    }

    public function csvToArrayAcquisition($filename, $profileId){
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

                array_push($metrics, $d);
            }
        }
        return $metrics;
    }
    public function getAudienceMetrics($userId, $profileId){
        $minDate = AudienceMetric::where('profile_id', '=', $profileId)->whereNull('uploader_id')->min('date_retrieved');
        $maxDate = AudienceMetric::where('profile_id', '=', $profileId)->whereNull('uploader_id')->max('date_retrieved');

        $yesterday = date("Y-m-d", strtotime("-1 day", time() + 3600*8));

        $dateCreated = GoogleAnalytics::where("profile_id", "=", $profileId)->value('date_created');

        $start = '';

        if($minDate != $dateCreated){
            $start = $dateCreated;
        }
        else{
            if($minDate == $dateCreated && $maxDate >= $yesterday){
                return response()->json([]); // up to date
            }
            else{
                $start = date("Y-m-d", strtotime("+1 day", strtotime($maxDate)));
            }
        }

        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($start);
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
        $pageviewsPerSession->setAlias("pages_per_session");

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
        
        $toInsert = [];
        foreach ($arr as $key => $row){
            $m = AudienceMetric::where(['date_retrieved' => $row['date_retrieved']])->first();
            if(empty($m)){
                array_push($toInsert, $row);
            }
            else{ // update only the accurate fields if there's existing page metric
                AudienceMetric::updateOrCreate(['date_retrieved' => $row['date_retrieved']], $row);
            }
        }

        AudienceMetric::insert($toInsert);

        return response()->json($arr);

    }

    public function getAcquisitionMetrics($profileId){
        $minDate = AcquisitionMetric::where('profile_id', '=', $profileId)->min('date_retrieved');
        $maxDate = AcquisitionMetric::where('profile_id', '=', $profileId)->max('date_retrieved');

        $yesterday = date("Y-m-d", strtotime("-1 day", time() + 3600*8));

        $dateCreated = GoogleAnalytics::where("profile_id", "=", $profileId)->value('date_created');
        $dateCreated = date("Y-m-d", strtotime("+1 day", strtotime($dateCreated)));

        $start = '';
        if($minDate != $dateCreated){
            $start = $dateCreated;
        }
        else{
            if($minDate == $dateCreated && $maxDate >= $yesterday){
                return response()->json([]); // up to date
            }
            else{
                $start = date("Y-m-d", strtotime("+1 day", strtotime($maxDate)));
            }
        }

        // dd($start, $minDate, $dateCreated, $maxDate, $yesterday);

        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($start);
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

        $toInsert = [];
        foreach ($arr as $key => $row){
            $m = AcquisitionMetric::where(['date_retrieved' => $row['date_retrieved']])->first();
            if(empty($m)){
                array_push($toInsert, $row);
            }
            else{ // update only the accurate fields if there's existing page metric
                AcquisitionMetric::updateOrCreate(['date_retrieved' => $row['date_retrieved']], $row);
            }
        }

        AcquisitionMetric::insert($toInsert);

        return response()->json($arr);
    }

    public function fetchMetrics(Request $request, $userId, $profileId){
        $metrics = [];

        $metrics['acquisition']= GoogleAnalytics::find($profileId)->acquisitionMetrics()->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}")->get();

        $metrics['audience']= GoogleAnalytics::find($profileId)->audienceMetrics()->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}")->where(function ($query) use ($userId) {
                $query->where("uploader_id", "{$userId}")
                    ->orWhereNull("uploader_id");
            })->get();

        $metrics['behavior']= GoogleAnalytics::find($profileId)->behaviorMetrics()->select('page_path', \DB::raw('sum(pageviews) as pageviews'))->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}")->groupBy('page_path')->orderByRaw('SUM(pageviews) DESC')->limit(10)->get();

        $metrics['pageviews_total'] = GoogleAnalytics::find($profileId)->behaviorMetrics()->select(\DB::raw('sum(pageviews) as pageviews'))->where("date_retrieved", ">=", "{$request->start}")->where("date_retrieved", "<=", "{$request->end}")->get()[0];
        
        return response()->json($metrics);
    }

    public function getBehaviorMetrics($profileId){
        $minDate = BehaviorMetric::where('profile_id', '=', $profileId)->min('date_retrieved');
        $maxDate = BehaviorMetric::where('profile_id', '=', $profileId)->max('date_retrieved');

        $yesterday = date("Y-m-d", strtotime("-1 day", time() + 3600*8));

        $dateCreated = GoogleAnalytics::where("profile_id", "=", $profileId)->value('date_created');
        $dateCreated = date("Y-m-d", strtotime("+1 day", strtotime($dateCreated)));

        $start = '';
        if($minDate != $dateCreated){
            // dd(3);
            $start = $dateCreated;
        }
        else{
            if($minDate == $dateCreated && $maxDate >= $yesterday){
                // dd(1);
                return response()->json([]); // up to date
            }
            else{
                // dd(2);
                $start = date("Y-m-d", strtotime("+1 day", strtotime($maxDate)));
            }
        }

        $dateCreated = GoogleAnalytics::where("profile_id", "=", $profileId)->value('date_created');
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($start);
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
        
        $toInsert = [];
        foreach ($behaviors as $key => $row){
            $m = BehaviorMetric::where(['date_retrieved' => $row['date_retrieved'], 'page_path' => $row['page_path']])->first();
            if(empty($m)){
                array_push($toInsert, $row);
            }
            else{ // update only the accurate fields if there's existing page metric
                BehaviorMetric::updateOrCreate(['date_retrieved' => $row['date_retrieved'], 'page_path' => $row['page_path']], $row);
            }
        }
        BehaviorMetric::insert($toInsert);
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

    public function addAccount(Request $request, $userId){
        try{
            $account = GoogleAnalytics::find($request->profileId);
            $googleAnalytics = empty($account) ? new GoogleAnalytics : $account;
            $googleAnalytics->date_created = $request->dateCreated;
            $googleAnalytics->token = $request->token;
            $googleAnalytics->refresh_token = $request->refreshToken;
            $googleAnalytics->email = $request->email;
            $googleAnalytics->property_name = $request->propertyName;
            $googleAnalytics->property_id = $request->propertyId;
            $googleAnalytics->profile_name = $request->profileName;
            $googleAnalytics->profile_id = $request->profileId;
            $googleAnalytics->created = $request->created;
            $googleAnalytics->expires_in = $request->expiresIn;
            $googleAnalytics->save();
            $accs = User::find($userId)->googleAnalyticsAccounts()->where('user_id', $userId)->get();
            if(count($accs) == 0){
                // return response()->json("mt");
                User::find($userId)->googleAnalyticsAccounts()->attach($request->profileId);
            }
            return $this->sendResponse($googleAnalytics, 'Account succesfully added'); 
        }catch (\Illuminate\Database\QueryException $ex){
            return response()->json($ex->getMessage());
        }catch (Exception $e) {
            return response()->json($e->getMessage());
            // return $this->sendError(['error' => get_class($e)],'Getting metrics failed'); 
        } 
    }

    public function deleteAccount($userId, $profileId){
        User::find($userId)->googleAnalyticsAccounts()->detach($profileId);
        $metric = GoogleAnalytics::find($profileId)->audienceMetrics()->where('uploader_id', $userId)->delete();
        
        return response()->json([]);
    }

}