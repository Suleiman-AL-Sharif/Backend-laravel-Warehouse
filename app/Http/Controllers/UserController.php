<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function register(Request $request)
    {

        $request->validate([
            "email" => "required|unique:users",
            "password" => "required|confirmed",
            "type" => "required"
        ]);

        $user = new User();
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->phone_n = $request->phone_n;
        $user->type = $request->type;
        $user->save();

        $address = new Address();
        $address->country = $request->country;
        $address->city = $request->city;
        $address->district = $request->district;
        $address->user_id = $user->id;

        $address->save();

        return response()->json([
            "message" => "user created"
        ], 201);
    }


    public function login(Request $request)
    {

        $login_data = $request->validate([

            "email" => "required",
            "password" => "required",
        ]);

        if (!auth()->attempt($login_data)) {

            return response()->json([
                "status" => false,
                "message" => "invalid "
            ]);
        }

        $token = auth()->user()->createToken("auth_token")->accessToken;
        $type = auth()->user()->type;
        return response()->json([
            "status" => true,
            "message" => "logged in successfully ",
            "access token" => $token,
            "type" => $type
        ]);
    }


    public function logout(Request $request)
    {


        $token = $request->user()->token();

        $token->revoke();

        return response()->json([
            "status" => true,
            "message" => "logged out successfully ",

        ]);
    }


    //change password
    public function change(Request $request)
    {

        $user = User::findOrFail(auth()->user()->id);
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            "status" => true,
            "message" => "password change successfully ",

        ]);
    }
}
