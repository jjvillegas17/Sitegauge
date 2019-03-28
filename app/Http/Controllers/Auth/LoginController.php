<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Facebook\Facebook;
use Socialite;

class LoginController extends Controller
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

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider(Facebook $fb)
    {
        return Socialite::driver('facebook')->scopes(["read_insights", "manage_pages"])->stateless()->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Facebook $fb)
    {
        $user = Socialite::driver('facebook')->stateless()->user();

        // get long-lived user token
        $userToken = $fb->getOAuth2Client();

        try {
            $longLivedUserToken = $userToken->getLongLivedAccessToken($user->token)->getValue();
        } catch(Facebook $e){
            echo $e->getMessage();
        }

        try { // returns the access token for each page
            $graphEdge = $fb->get('/me/accounts?fields=access_token', $longLivedUserToken)->getGraphEdge();
        } catch (FacebookSDKException $e) {
            echo $e->getMessage();
        }

        // TODO: have an assoc array where key = page_name & value = access_token
        // so that add pages fxnality will be correct
        
        $pageToken = $graphEdge->asArray()['0']['access_token'];
        $pageId = $graphEdge->asArray()['0']['id'];
        $fb->setDefaultAccessToken($pageToken);

        return redirect()->route('fb.addPage', 
                    ['pageToken' => $pageToken, 
                     'pageId' => $pageId,
                ]);
    }
}
