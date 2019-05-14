<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\FacebookPage;
use App\GoogleAnalytics;
use App\TwitterAccount;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request){
        // 'isAdmin required'
        // '
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $request->email,
            'password' => 'required|confirmed',
            'firstName' => 'required',
            'lastName' => 'required',
        ]);

        if($validator->fails()){
            $errors = [];
            array_push($errors, $validator->errors());
            return $this->sendError($errors, 'User signup unsuccessful');     
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = new User;
        $user->first_name = $input['firstName'];
        $user->last_name = $input['lastName'];
        $user->email = $input['email'];
        $user->password = $input['password'];
        if(!empty($input['isAdmin'])){
            $user->is_admin = $input['isAdmin'];
        }
        $user->save();

        $success['token'] =  $user->createToken('MyApp')->accessToken;
        $success['email'] =  $user->email;
        return $this->sendResponse($success, 'User register successfully.');
    }

    public function login(){
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){ 
            $user = Auth::user();
            $success['user_id'] = $user->id; 
            $success['token'] =  $user->createToken('MyApp')-> accessToken;
            $success['is_admin'] = $user->is_admin;
            $success['is_blocked'] = $user->is_blocked;
            return $this->sendResponse($success, 'User login successful');
        } 
        else{ 
            return $this->sendError('Incorrect username or password', 'User login unsuccessful');
            // return response()-j>on(['error'=>'Unauthorised'], 401); 
        }
    }

    public function logout(){   
        if (Auth::check()) {
            // get all the tokens of the user and revoke it all not only the last token
            Auth::user()->token()->revoke();
            return $this->sendResponse(['sucess' => 'logout_sucess'],'Logout success'); 
        }
        else{
            return $this->sendError(['error' => 'api_something_went_wrong'],'Logout fail');
        }
    }

    public function getAllAccounts($userId){
        $pages = User::find($userId)->facebookPages()->get()->toArray();
        $twitters = User::find($userId)->twitterAccounts()->get()->toArray();
        $googles = User::find($userId)->googleAnalyticsAccounts()->get()->toArray();
        $accounts = ['pages' => $pages, 'twitters' => $twitters, 'googles' => $googles];
        return response()->json($accounts);
    }

    public function getAllUsers(){
        return response()->json(User::where('is_admin', 0)->get());
    }

    public function getUnBlockedusers(){
        return response()->json(User::where('is_blocked', 0)->where('is_admin', 0)->get());
    }

    public function getBlockedUsers(){
        return response()->json(User::where('is_blocked', 1)->where('is_admin', 0)->get());
    }

    public function getUser($userId){
        return response()->json(User::find($userId));
    }

    public function deleteUsers(Request $request){
        try{
            foreach ($request->users as $key => $user) {
                User::find($user)->twitterAccounts()->detach();
                User::find($user)->googleAnalyticsAccounts()->detach();
                User::find($user)->facebookPages()->detach();
                User::find($user)->delete();            
            }            
        } catch(\Exception $e){
            return response()->json([$e->getMessage()]);
        }


        return response()->json([]);
    }

    public function blockUsers(Request $request){
            foreach ($request->users as $key => $user) {
                $a = User::find($user);
                $a->is_blocked = 1;
                $a->save();
            }

            return response()->json([]);
    }

    public function unblockUsers(Request $request){
            foreach ($request->users as $key => $user) {
                $a = User::find($user);
                $a->is_blocked = 0;
                $a->save();
            }


            return response()->json([]);
    }
}