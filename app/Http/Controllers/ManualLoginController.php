<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualLoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // Authentication passed...
            return redirect()->route('fb.home');

            // if successful try redirecting to a route 
            // then use Auth::check() if logged in tlga sya
            // para matesting kung oks na ung login
        }
        else {
            return redirect()->route('fb.login');
        }
    }
}