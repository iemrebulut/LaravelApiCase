<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use Dotenv\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends BaseController
{
    public function login(Request $request) :JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|string',
                'password' => 'required'
            ]);

            if($validator->fails())
                return $this->error(message: $validator->errors(), data: [], statusCode: 422);

            if(Auth::guard('web')->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {

                $user = Auth::user();
                $user['token'] = $user->createToken('MyApp')->platinTextToken;

                return $this->success(message: 'User successfully login', data: []);

            } else {
                return $this->error(message: 'Email or password incorrect', data: [], statusCode: 401);
            }
        } catch (\Exception $e){
            return $this->error(message: $e->getMessage(), data: [], statusCode: $e->getCode());
        }
    }

    public function logout(Request $request) :JsonResponse {
        try {
            if (Auth::check()) {
                $accessToken = $request->bearerToken();
                $token = PersonalAccessToken::findToken($accessToken);
                $token->delete();

                return $this->success(message: 'User successfully logout', data: ['logout'=>'success']);
            }
            else{
                return $this->error(message: 'Unauthorized', data: [], statusCode:401);
            }
        }catch (\Exception $e){
            return $this->error(message: $e->getMessage(), data: [], statusCode: $e->getCode());
        }
    }
}
