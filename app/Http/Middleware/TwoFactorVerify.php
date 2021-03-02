<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Mail\twoFactor;
use Illuminate\Support\Facades\Mail;

class TwoFactorVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if($user->token_2fa_expiry > \Carbon\Carbon::now()){
            return $next($request);
        } 
        
        $user->token_2fa = mt_rand(10000000,99999999);
        $user->save();        
        // This is the twilio way
        //Twilio::message($user->phone_number, 'Two Factor Code: ' . $user->token_2fa);
        // If you want to use email instead just 
        // send an email to the user here ..
        Mail::to($user->email)->send(new twoFactor($user));
        
        return redirect('/2fa');  
    }
}
