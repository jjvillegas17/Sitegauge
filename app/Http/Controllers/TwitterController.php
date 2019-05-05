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

    public function addAccount(Request $request, $userId){
    	try{
    		$account = TwitterAccount::find($request->id);
            $twitterAccount = empty($account)? new TwitterAccount : $account;
            $twitterAccount->id = $request->id;
            $twitterAccount->username = $request->username;
            $twitterAccount->name = $request->name;
            $twitterAccount->tweets = $request->tweets;
            $twitterAccount->following = $request->following;
            $twitterAccount->followers = $request->followers;
            $twitterAccount->save();
            $accs = User::find($userId)->twitterAccounts()->where('user_id', $userId)->get();
            // return response()->json($accs);
            if(count($accs) == 0){
            	// return response()->json("mt");
            	User::find($userId)->twitterAccounts()->attach($request->id);
            }
            return $this->sendResponse($twitterAccount, 'Twitter account succesfully added'); 
        }catch (\Illuminate\Database\QueryException $ex){
            return response()->json($ex->getMessage());
        }catch (Exception $e) {
            return response()->json(get_class($e));
            // return $this->sendError(['error' => get_class($e)],'Getting metrics failed'); 
        } 
    }

    public function getTweetMetrics($userId, $twitterId, Request $request){
    	// $tweetMetrics = TwitterAccount::find($twitterId)->tweetMetrics->where("created_date", ">=", "{$request->start}")->where("created_date", "<=", "{$request->end}");
    	$tweetMetrics = TwitterAccount::find($twitterId)->tweetMetrics()
    			->where(function ($query) use ($userId) {
                	$query->where("uploader_id", "{$userId}")
                    ->orWhereNull("uploader_id");
           		 })->orderBy('created_date', 'desc')->offset(0)->limit(10)->get();

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

    public function uploadCSV(Request $request, $userId, $twitterId){
    	if ($request->hasFile('file')) {
		    if($request->file('file')) {
			    $file = $request->file("file");
		  		$tweetArr = $this->csvToArray($file, $twitterId, $userId);

		  		foreach ($tweetArr as $key => $metric) {
		  			$m = TweetMetric::where([
                        ['tweet_id', $metric['tweet_id']],
                        ['twitter_id', $metric['twitter_id']]
                        ])->get();

		  			if(empty($m)){ // if wala dun sa 2 year range data, insert
                        TweetMetric::insert($metric);
                    }
                    // problem in twitter accounts and fb accts 
                    else{
                        $hasSameUploaderId = false;
                        foreach ($m as $key => $row) {
                            if($row->uploader_id == $userId){ // upload only rows that are from upload and not from api
                                TweetMetric::updateOrCreate(['tweet_id' => $metric['tweet_id'], 'uploader_id' => $metric['uploader_id']],$metric);
                                $hasSameUploaderId = true;
                                break;    
                            }
                            else if(is_null($row->uploader_id)){ // galing from api
                                $hasSameUploaderId = true;
                                break; 
                            }
                        }
                        if($hasSameUploaderId == false){
                        	TweetMetric::insert($metric);
                        }                          
                    }
		  		}

		  		return response()->json($tweetArr);
		    }
		}
		return response()->json('no file');

    }

    public function csvToArray($filename, $twitterId, $userId){
    	if (!file_exists($filename))
        	return false;

	    $metrics = [];
	    if(($handle = fopen($filename, 'r')) !== false)
	    {
	    	$header = fgetcsv($handle);

	        while ($row = fgetcsv($handle)) {
	        	$d = [];
			  	$data = array_combine($header, $row);
			  	$d['tweet_id'] = $data['Tweet id'];
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
			  	$d['uploader_id'] = $userId;
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
