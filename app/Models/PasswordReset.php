<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    //create a reset link
    public function createLink($email)
    {
        $domain = env('APP_DOMAIN');
        //check if the email is already in the database
        $passwordReset = PasswordReset::where('email', $email)->first();
        if ($passwordReset) {
            //update the token
            $passwordReset->token = $this->createToken();
            $passwordReset->created_at = now();
            $passwordReset->save();
        } else {
            //create a new token
            $passwordReset = new PasswordReset();
            $passwordReset->email = $email;
            $passwordReset->token = $this->createToken();
            $passwordReset->created_at = now();
            $passwordReset->save();
        }
        //remove api- from domain
        $domain = 'https://' . $domain . '/reset-password' . '/' .  $passwordReset->token;
        return $domain;
    }


    function createToken()
    {
        //six random characters
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 31; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        //check if the token is already in the database
        $passwordReset = PasswordReset::where('token', $randomString)->first();
        if ($passwordReset) {
            //create a new token
            $randomString = $this->createToken();
        }
        return $randomString;
    }

    //get the user by the token
    public function getUserByToken($token)
    {
        $passwordReset = PasswordReset::where('token', $token)->first();
        if ($passwordReset) {
            //allow the token for 24 hours
            $now = now();
            $created_at = $passwordReset->created_at;
            $difference = $now->diffInHours($created_at);
            if ($difference > 24) {
                $this->removeToken($token);
                return null;
            }
            return User::where('email', $passwordReset->email)->first();
        }
        return null;
    }

    //remove the token
    public function removeToken($token)
    {
        $passwordReset = PasswordReset::where('token', $token)->first();
        if ($passwordReset) {
            $passwordReset->delete();
        }
    }
}
