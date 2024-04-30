<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return response()->json(['token' => $token,
	        						'user'=> [
	        							"name" => $request->name,
	        							'email' => $request->email
	        						],
    							], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = $request->user();
        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        return response()->json([
            'token' => $token,
            'user'=> [
                "name" => $user->name,
                'email' => $user->email
            ],
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        //create initial user details
        $user = User::create([
            'name' => 'Temp',
            'email' => $request->email,
            'password' => Hash::make($request->email),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $otp = $this->otpResend($user);

        return response()->json([
            'otp' => $otp,
            'message' => 'OTP verfication email sent',
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'OTP already verified'], 409);
        }

        $otp = $this->otpResend($user);
        return response()->json([
            'otp' => $otp,
            'message' => 'OTP verfication email resend successfull',
        ]);
    }

    public function otpResend(User $user)
    {
    	//generate some info
        $otp = rand(100000, 999999); // Generate OTP
		$user->otp = $otp;
		$user->otp_expires_at = now()->addMinutes(10); // The OTP is valid for 10 minutes
		$user->save();

		Mail::to($user->email)->send(new OtpMail($user));

		return $otp;
    }

	public function validateOtp(Request $request)
	{
		$user = User::where('email', $request->email)->first();
	    $inputOtp = $request->input('otp');

		if ($user === null) {
		    return response()->json(['message' => 'User not found'], 404);
		} else {
            if ($user->otp_expires_at->lessThan(now())) {
                return response()->json(['message' => 'OTP expired.'], 410);
            }
            if ($user->email_verified_at !== null) {
                return response()->json(['message' => 'OTP already verified'], 409);
            }
            if($user->otp != $inputOtp) {
		        return response()->json(['message' => 'Invalid OTP.'], 409);
		    }
		}

        $user->email_verified_at = Carbon::now();
        $user->save();

		$token = $user->createToken('Laravel Password Grant Client')->accessToken;
		return response()->json(['token' => $token,
									'message' => 'Email successfully verified',
	        						'user'=> [
	        							'email' => $user->email
	        						],
    							], 200);
	}

}
