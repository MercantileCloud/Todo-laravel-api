<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'token', 'tokenable_id', 'last_used_at', 'tokenable_type', 'abilities'
    ];

    public static function removeTokens($user)
    {
        PersonalAccessToken::where('tokenable_id', $user->id)->delete();
    }

    public static function removeParticularToken($user, $token)
    {
        PersonalAccessToken::where('tokenable_id', $user->id)->where('token', $token)->delete();
    }
}
