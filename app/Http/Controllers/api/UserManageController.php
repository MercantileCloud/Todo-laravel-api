<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Models\User;
use App\Mail\SendResetMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;


class UserManageController extends Controller
{
    protected $resetPassword;

    public function __construct(PasswordReset $resetPassword)
    {
        $this->resetPassword = $resetPassword;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = $request->input('paginate') ?? 12;
        $search = $request->input('search') ?? null;
        $page = $request->input('page') ?? 1;
        $users = User::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', '%' . $search . '%');
        })->paginate($paginate, ['*'], 'page', $page);
        return customResponse(true, 200, 'User fetched successfully', $users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255'
        ])->validate();

        //check if user already exists
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->email_verified_at != null) {
                return customResponse(false, 400, 'User already verified.', $user);
            }
            $link = $this->resetPassword->createLink($request->email);
            if (Mail::to($request->email)->send(new SendResetMail($link)));
            return customResponse(true, 200, 'Re-invitation link has been sent to the user.', $user);
        }
        $user = User::create([
            'email' => $request->email,
            'password' => bcrypt($this->resetPassword->createToken()),
            'name' => $request->name,
            'phone' => $request->phone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $link = $this->resetPassword->createLink($request->email);
        //check if the email is successfully sent
        $invitedBy = auth()->user()->name;
        if (Mail::to($request->email)->send(new SendResetMail($link))) {
            return customResponse(true, 200, 'Invitation link has been sent to the user.', $user);
        }
        $user->delete();
        $error["email"] = "Please enter a valid email address";
        return response()->json(['message' => 'User not created', 'errors' => $error], 422);
    }


    public function sendReinvitation(Request $request)
    {
        Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ])->validate();
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->email_verified_at != null) {
                return customResponse(false, 400, 'User already verified.', $user);
            }
            $link = $this->resetPassword->createLink($request->email);
            if (Mail::to($request->email)->send(new SendResetMail($link)));
            return customResponse(true, 200, 'Re-invitation link has been sent to the user.', $user);
        }
        $error["email"] = "Please enter a valid email address";
        return response()->json(['message' => 'User not created', 'errors' => $error], 422);
    }


    public function createPassword(Request $request)
    {
        Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
            'token' => 'required',
        ])->validate();
        $user = $this->resetPassword->getUserByToken($request->token);
        if (!$user) {
            return customResponse(false, 400, 'Link has been Expired. Please ask the administrator to reinvite.');
        }
        $user->password = bcrypt($request->password);
        $user->email_verified_at = now();
        $user->save();
        $this->resetPassword->removeToken($request->token);
        return customResponse(true, 200, 'Password changed successfully');
    }

    public function verifyToken(Request $request)
    {
        $user = $this->resetPassword->getUserByToken($request->token);
        if (!$user) {
            return customResponse(false, 400, 'Link has been Expired. Please ask the administrator to reinvite.');
        }
        return customResponse(true, 200, 'Token is valid');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ])->validate();

        $user = User::find($id);
        if (!$user) {
            return customResponse(false, 400, 'User not found');
        }

        $user->name = $request->name;
        $user->save();

        return customResponse(true, 200, 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword(Request $request)
    {
        Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ])->validate();
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return customResponse(false, 404, 'User not found');
        }
        $link = $this->resetPassword->createLink($request->email);
        if (Mail::to($request->email)->send(new SendResetMail($link)));
        return customResponse(true, 200, 'Password reset link has been sent to your email');
    }
}
