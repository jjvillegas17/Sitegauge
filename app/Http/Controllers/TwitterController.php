<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\TweetMetric;
use App\TwitterAccount;
use App\Services\TwitterAPIExchange;
use App\User;

class TwitterController extends BaseController
{
	private $settings = [
        'oauth_access_token' => "1051570758-PkX7uIurnr8Jibr3Q2ycvXyRjcVp7i72URnF0wc",               
		'oauth_access_token_secret' => "6riQfa9rHko8yG21PlYsiMHU0ebH4cJFir5hkWcuN1RII",    
		'consumer_key' => "zMdlnOUxnSOsqyU2O8FCFqK8z",                 
		'consumer_secret' => "Nb4yO8lGxgx4qvsF0vZuMdadGovUf7lR9iZVB667ErTvZ345kE" 
    ];

    public function addAccount(Request $request){
    	try{
            $twitterAccount = new TwitterAccount;
            $twitterAccount->id = $request->id;
            $twitterAccount->username = $request->username;
            $twitterAccount->name = $request->name;
            $twitterAccount->tweets = $request->tweets;
            $twitterAccount->following = $request->following;
            $twitterAccount->followers = $request->followers;
            $twitterAccount->user_id= $request->userId;
            $twitterAccount->save();	
            return $this->sendResponse($twitterAccount, 'Twitter account succesfully added'); 
        }catch (\Illuminate\Database\QueryException $ex){
            return response()->json($ex->getMessage());
        }catch (Exception $e) {
            return response()->json(get_class($e));
            // return $this->sendError(['error' => get_class($e)],'Getting metrics failed'); 
        } 
    }

    public function getTweetMetrics($twitterId, Request $request){
    	// $tweetMetrics = TwitterAccount::find($twitterId)->tweetMetrics->where("created_date", ">=", "{$request->start}")->where("created_date", "<=", "{$request->end}");
    	$tweetMetrics = TwitterAccount::find($twitterId)->tweetMetrics()->orderBy('created_date', 'desc')->offset(0)->limit(10)->get();

    	return response()->json($tweetMetrics);
    }

	public function updateAccount($userId, Request $request){
			$this->settings['oauth_access_token'] = $request->token;
			$this->settings['oauth_access_token_secret'] = $request->tokenSecret;
			$ta_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
			$getfield = "?screen_name={$request->username}
		&include_rts=false&exclude_replies=false"; 
			$requestMethod = 'GET';
			$twitter = new TwitterAPIExchange($this->settings);
			$follow_count=$twitter->setGetfield($getfield)
			->buildOauth($ta_url, $requestMethod)
			->performRequest();

			$data = json_decode($follow_count, true);

			$twitter_account = [];
	        $twitter_account['id'] = $data[0]['user']['id_str'];
	        $twitter_account['username'] = $data[0]['user']['screen_name'];
	        $twitter_account['name'] = $data[0]['user']['name'];
	        $twitter_account['followers'] = $data[0]['user']['followers_count'];
	        $twitter_account['following'] = $data[0]['user']['friends_count'];
	        $twitter_account['tweets'] = $data[0]['user']['statuses_count'];
	        $twitter_account['user_id'] = $userId;    /* change this one to actual user id Auth::user()->id */
	        $user = TwitterAccount::updateOrCreate(['id' => $data[0]['user']['id_str']], $twitter_account); 
	        return response()->json($user);
		
	}

    public function uploadCSV(Request $request, $twitterId){
    	if ($request->hasFile('file')) {
		    if($request->file('file')) {
			    $file = $request->file("file");
		  		$tweetArr = $this->csvToArray($file, $twitterId);

		  		foreach ($tweetArr as $key => $tweet) {
		  			TweetMetric::updateOrCreate(['id' => $tweet['id']], $tweet);
		  		}

		  		return response()->json($tweetArr);
		    }
		}
		return response()->json('no file');

    }

    public function csvToArray($filename, $twitterId){
    	 if (!file_exists($filename))
        	return false;

	    $metrics = [];
	    if(($handle = fopen($filename, 'r')) !== false)
	    {
	    	$header = fgetcsv($handle);

	        while ($row = fgetcsv($handle)) {
	        	$d = [];
			  	$data = array_combine($header, $row);
			  	$d['id'] = $data['Tweet id'];
			  	$d['link'] = $data['Tweet permalink'];
			  	$d['text'] = $data['Tweet text'];
			  	$created_date = new \DateTime($data['time']);
			  	$created_date->format('Y-m-d H:i:s');
			  	$d['created_date'] = $created_date;
			  	$d['impressions'] = $data['impressions'];
			  	$d['engagements'] = $data['engagements'];
			  	$d['engagement_rate'] = $data['engagement rate'];
			  	$d['retweets'] = $data['retweets'];
			  	$d['replies'] = $data['replies'];
			  	$d['likes'] = $data['likes'];
			  	$d['follows'] = $data['follows'];
			  	$d['user_profile_clicks'] = $data['user profile clicks'];
			  	$d['media_views'] = $data['media views'];
			  	$d['media_engagements'] = $data['media engagements'];
			  	$d['twitter_id'] = (string) $twitterId;
			  	array_push($metrics, $d);
			}
	    }

	    return $metrics;
    }

    public function getAccountsOfUser($userId){
    	$accts = User::find($userId)->twitterAccounts;
    	return response()->json($accts);
    }
}
