<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required|email",
            "password" => "required",
            "c_password" => "required|same:password",
        ]);

        if ($validator->fails()) {
            return $this->sendError("Validation Error.", $validator->errors());
        }

        $input = $request->all();

        $input["password"] = bcrypt($input["password"]);

        $user = User::create($input);

        $user->sendEmailVerificationNotification();

        $success["token"] = $user->createToken("Fluidlabs CRM")->accessToken;

        $success["name"] = $user->name;

        return $this->sendResponse($success, "User register successfully.");
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        if (Auth::attempt([
            "email" => $request->email,
            "password" => $request->password,
        ])) {
            $user = Auth::user();

            $success["token"] = $user->createToken("Fluidlabs CRM")->accessToken;
            $success["first_name"] = $user->first_name;
            $success["last_name"] = $user->last_name;
            $success["email"] = $user->email;
            $success["role"] = $user->role;

            return response()->json([
                'success' => true,
                'data' => $success,
                'message' => 'User login successfully.'
            ], 200); // HTTP 200 OK
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'error' => 'Unauthorised'
            ], 401); // HTTP 401 Unauthorized
        }
    }
}
