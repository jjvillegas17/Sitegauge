<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\User;
 
class ManualRegisterController extends Controller
{   
    public function try() {
        return response()->json(['name' => 'jj']);
        dd($request->email, $request->password);
    } 

    public function register(Request $request){
        $this->validate(request(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        $user = User::create(request(['name', 'email', 'password']));
        
        auth()->login($user);
        // return redirect()->route('manual.login');

        return redirect()->route('fb.home');
    }
}