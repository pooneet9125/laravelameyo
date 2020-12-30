<?php

namespace App\Http\Controllers;
//use JwtAuthController;
use Illuminate\Http\Request;
use App\User;
use JWTAuth;
use Validator;
use DateTime;


class JwtAuthController extends Controller
{
    public $token = true;

    public function register(Request $request)
    {

         $validator = Validator::make($request->all(), 
                      [ 
                      'name' => 'required',
                      'email' => 'required|email',
                      'password' => 'required',  
                      'c_password' => 'required|same:password', 
                      'token_ttl' => 'required'
                     ]);  

         if ($validator->fails()) {  

               return response()->json(['error'=>$validator->errors()], 401); 

            }   


        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 0;
        $user->token_ttl = $request->token_ttl;
        $user->save();

        if ($this->token) {
            return $this->login($request);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function updateUser(Request $request, $id)
    {

         $validator = Validator::make($request->all(), 
                      [ 
                      'name' => 'required',
                      'email' => 'required|email',
                      'password' => 'required',  
                      'c_password' => 'required|same:password', 
                      'token_ttl' => 'required'
                     ]);  

         if ($validator->fails()) {  

               return response()->json(['error'=>$validator->errors()], 401); 

            }   


        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 0;
        $user->token_ttl = $request->token_ttl;
        $user->save();

        if ($this->token) {
            return $this->login($request);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function login(Request $request)
    {
        $input = $request->only('email', 'password');
        $jwt_token = null;

        if (!$jwt_token = JWTAuth::attempt($input)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Email or Password',
            ], Response::HTTP_UNAUTHORIZED);
        }else{
        	$userArr = auth()->user();
        	$userId = $userArr->id;
        	$user = User::find($userId);
        	$user->token_created_date = now();
        	$user->save();
        }

        return response()->json([
            'success' => true,
            'token' => $jwt_token
        ]);
    }

    public function logout(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, the user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUser(Request $request)
    { 

        $this->validate($request, [
            'token' => 'required'
        ]);

        $userArr = auth()->user();
        $token_ttl = $userArr->token_ttl;
        $token_created_date = $userArr->token_created_date;
        if($token_ttl != -1){
        	$currentDateTime = date('Y-m-d H:i:s');
        	$to_time = strtotime($currentDateTime);
			$from_time = strtotime($token_created_date);
			$diffInTime = round(abs($to_time - $from_time) / 60,2);
			if($diffInTime<0){
				JWTAuth::invalidate($request->token);

	            return response()->json([
	                'success' => false,
	                'message' => 'Token expired!!',
	            ], Response::HTTP_UNAUTHORIZED);
			}
        }
        $user = JWTAuth::authenticate($request->token);
        
        return response()->json(['user' => $user]);
    }
}
