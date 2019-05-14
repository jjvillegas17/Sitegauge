<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Facebook\Facebook;
use Socialite;
use App\TwitterAccount;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Http\Controllers\API\BaseController as BaseController;
use Google_Client;
use Google_Service_Analytics;

class LoginController extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectToGoogle(){
        $url = Socialite::driver('google')->scopes(['openid', 'profile', 'email', 'https://www.googleapis.com/auth/analytics.readonly'])->with(["access_type" => "offline"])->redirect()->getTargetUrl();
        return response()->json($url . '&approval_prompt=force');
    }

    public function handleGoogleCallback(Request $request, Google_Client $client){
        $client->authenticate($request->code);
        $token = $client->getAccessToken();

        $user = Socialite::driver('google')->userFromToken($token["access_token"]);
        
        $token["email"] = $user->email;

        return redirect()->away("https://localhost:3000/addWebsite?" . http_build_query($token));
    }    

    public function redirectToTwitter(Request $request){
        $tempId = str_random(40);

        $consumerKey = "zMdlnOUxnSOsqyU2O8FCFqK8z";
        $consumerSecret = "Nb4yO8lGxgx4qvsF0vZuMdadGovUf7lR9iZVB667ErTvZ345kE";
        $oauthToken = "1051570758-8nsagsCOk74IJGns0GQ0VVn6REeL2ulMS13e2vu";
        $oauthTokenSecret = "57Swp9BCHGuu1XKBrXCpjIet0sci6mN5ek397i3ziCWo7";

        $connection = new TwitterOAuth($consumerKey,$consumerSecret,$oauthToken,$oauthTokenSecret);
        $connection->setTimeouts(60, 120);
        $requestToken = $connection->oauth('oauth/request_token', array('oauth_callback' => 'https://sitegauge.io/login/twitter/callback'.'?user='.$tempId));

        \Cache::put($tempId, $requestToken['oauth_token_secret'], 1);
         $url = $connection->url('oauth/authorize', array('oauth_token' => $requestToken['oauth_token']));
        return $url . "&userId={$request->userId}";
    }

    public function handleTwitterLoginCallback(Request $request) {
        $consumerKey = "zMdlnOUxnSOsqyU2O8FCFqK8z";
        $consumerSecret = "Nb4yO8lGxgx4qvsF0vZuMdadGovUf7lR9iZVB667ErTvZ345kE";
        $oauthToken = "1051570758-rgijW7EyFycBc36cBSdLFM5025jo8j3d0NIKEQn";
        $oauthTokenSecret = "4gtIZ9ntLvW6uSJwBHcPfW6jHql55wg2nEY3XbsH4s8p0";

        $connection = new TwitterOAuth($consumerKey, $consumerSecret, $request->oauth_token, $request->user);
        $connection->setTimeouts(60, 120);
        $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $request->oauth_verifier]);
        $content = $this->setTwitterDetails($access_token['oauth_token'],$access_token['oauth_token_secret'], $request->userId);
        return redirect()->away("https://localhost:3000/addSM?id={$content['id']}&name={$content['name']}&followers={$content['followers']}&following={$content['following']}&tweets={$content['tweets']}&username={$content['username']}&type=1");
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallbackFacebook(Request $request, Facebook $fb)
    {
        try { // exchange short lived token to long lived
            $token = $fb->post('/oauth/access_token', [
                    'grant_type' => 'fb_exchange_token',           
                    'client_id' => '774972786221082',
                    'client_secret' => '77c42cb8c4872c52564edb61320245fa',
                    'fb_exchange_token' => $request->token], $request->token);
        } catch(FacebookSDKException $e){
            return response()->json($e->getMessage());
        }
        // return response()->json($token->getDecodedBody());
        $token = $token->getDecodedBody();
        $token = $token['access_token'];

        try { // returns the access token for each page
            $graphEdge = $fb->get('/me/accounts?fields=name,access_token', $token)->getGraphEdge();
        } catch (FacebookSDKException $e) {
            return response()->json($e->getMessage());
        }

        $pages = [];
        foreach ($graphEdge->asArray() as $key => $page) {
            $pageObj = [];
            $pageObj['name'] = $page['name'];
            $pageObj['token'] = $page['access_token'];
            $pageObj['id'] = $page['id'];
            array_push($pages, $pageObj);
        }
        return response()->json($pages);
     }

     public function setTwitterDetails($token, $tokenSecret, $userId){
        $user = Socialite::driver('twitter')->userFromTokenAndSecret($token, $tokenSecret);
        $twitter_account = [];
        $twitter_account['id'] = $user->id;
        $twitter_account['username'] = $user->nickname;
        $twitter_account['name'] = $user->name;
        $twitter_account['followers'] = $user->user['followers_count'];
        $twitter_account['following'] = $user->user['friends_count'];
        $twitter_account['tweets'] = $user->user['statuses_count'];
        $twitter_account['user_id'] = $userId;    /* change this one to actual user id Auth::user()->id */
        // $user = TwitterAccount::updateOrCreate(['nickname' => $user->nickname ], $twitter_account);
        return $twitter_account;

        return redirect()->route('updateAccount', 
                    ['username' => $twitter_account['username'],
                     'token' => $token, 
                     'tokenSecret' => $tokenSecret,
                ]);
    }
        
}
