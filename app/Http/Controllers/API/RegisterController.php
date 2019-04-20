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
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $input = $request->all();
        // return $this->sendResponse($input, 'user details');
        $input['password'] = bcrypt($input['password']);
        
        $user = new User;
        $user->first_name = $input['first_name'];
        $user->last_name = $input['last_name'];
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
        $accounts = ['pages' => $pages, 'twitters' => $twitters];
        return response()->json($accounts);
    }

    public function getUser($userId){
        return response()->json(User::find($userId));
    }
}