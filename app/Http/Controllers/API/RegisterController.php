<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;

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
    
    public function details(){
        $user = Auth::user(); 
        return $this->sendResponse($user, 'Fetching of user successful');
    } 

    public function getAllAccounts($userId){
        $pages = User::find($userId)->facebookPages()->get()->toArray();
        $twitters = User::find($userId)->twitterAccounts()->get()->toArray();
        $googles = User::find($userId)->googleAnalyticsAccounts()->get()->toArray();
        $accounts = ['pages' => $pages, 'twitters' => $twitters, 'googles' => $googles];
        return response()->json($accounts);
    }

    public function getUser($userId){
        return response()->json(User::find($userId));
    }
}