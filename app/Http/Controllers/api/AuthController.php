<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $personalAccessToken;

    public function __construct(PersonalAccessToken $personalAccessToken)
    {
        $this->personalAccessToken = $personalAccessToken;
    }


    public function login(Request $request)
    {
        $fields = array(
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device' => 'required|string',
        );
        $errors = Validator::make($request->all(), $fields)
            ->errors()->all();
        if (count($errors) > 0) {
            $response = [
                'success' => false,
                'message' => $errors
            ];
            return response()->json($response, 400);
        }
        // Check email
        $user = User::where('email', $request->email)->first();

        if ($user == null) {
            $response = [
                'success' => false,
                'message' => ['User is not registered']
            ];
            return response($response, 401);
        }

        // Check password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'success' => false,
                'message' => ['The password is incorrect.']
            ], 401);
        }

        $token = $user->createToken(
            $request->device,
            ['user']
        )->plainTextToken;
        $user->setToken($token);
        $user = $user->toArray();
        $user['token'] = $token;
        $response = [
            'success' => true,
            'status' => 201,
            'message' => ['Login Successful.'],
            'user' => $user
        ];

        return response($response, 201);
    }

    public function logout(Request $request)
    {
        // if not auth then return
        if (!auth('sanctum')->check()) {
            $response = [
                'success' => false,
                'message' => ['You are not authenticated.']
            ];
            return response($response, 401);
        }
        $user = auth('sanctum')->user();
        if ($request->token) {
            PersonalAccessToken::removeParticularToken($user, $request->token);
            $response = [
                'success' => true,
                'message' => ['Logout was Successful.']
            ];
            return response($response, 200);
        }
        PersonalAccessToken::removeTokens($user);
        $response = [
            'success' => true,
            'message' => ['You have been successfully logged out From all devices.']
        ];
        return response($response, 200);
    }

    public function getDetails()
    {
        $user = auth('sanctum')->user();
        $user = User::find($user->id);
        $token = request()->bearerToken();
        $user = $user->toArray();
        $user['token'] = $token;
        return customResponse(true, 200, 'User details', $user);
    }

    public function changeDetails(Request $request)
    {

        Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ])->validate();

        $user = auth('sanctum')->user();
        $user = User::find($user->id);
        $user->name = $request->name;
        $user->save();
        return customResponse(true, 200, 'Details were changed successfully.', $user);
    }
}
