<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    // public function login(Request $request)
    // {

	// 	$request->validate([
    //         'username' => 'required',
    //         'password' => 'required'
    //     ]);

    //     $response = Http::post(env('REMOTE_LOGIN_API_URL'), [
    //         'username' => $request->username,
    //         'password' => $request->password,
    //     ]);

    //     if ($response->failed()) {

	// 		return response()->json([
	// 			'message' => $response->json('message') ?? 'Remote authentication failed',
	// 			'remote_status' => $response->status(),
	// 			'remote_body' => $response->json()
	// 		], $response->status());
	// 	}

    //     $remoteUser = $response->json('data.user');

	// 	$localUser = User::updateOrCreate(
	// 		['username' => $remoteUser['username']],
	// 		[
	// 			'firstname' => $remoteUser['firstname'],
	// 			'lastname'  => $remoteUser['lastname'],
	// 			'employee_id' => $remoteUser['employee_id'],
	// 		]
	// 	);

	// 	$token = $localUser->createToken('api_token')->plainTextToken;

	// 	return response()->json([
	// 		'message' => 'Login successful',
	// 		'token' => $token,
	// 		'user' => $remoteUser
	// 	]);
    // }

    // public function logout(Request $request)
	// {
	// 	$request->user()->currentAccessToken()->delete();

	// 	return response()->json([
	// 		'message' => 'Logged out successfully'
	// 	]);
	// }

	public function profile(Request $request)
	{
		return response()->json([
			'user' => $request->user()
		]);
	}
}