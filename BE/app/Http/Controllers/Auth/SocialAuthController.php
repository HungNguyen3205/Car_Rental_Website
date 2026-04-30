<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            return $this->handleSocialUser($googleUser, 'google');
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => 'Lỗi xác thực Google: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Redirect the user to the Facebook authentication page.
     */
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Facebook.
     */
    public function handleFacebookCallback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();
            return $this->handleSocialUser($facebookUser, 'facebook');
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => 'Lỗi xác thực Facebook: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Find or create a user and return a token.
     */
    protected function handleSocialUser($socialUser, $provider)
    {
        $idField = $provider . '_id';
        
        $user = User::where($idField, $socialUser->getId())
                    ->orWhere('email', $socialUser->getEmail())
                    ->first();

        if (!$user) {
            $user = User::create([
                'ho_ten'    => $socialUser->getName(),
                'email'     => $socialUser->getEmail(),
                'avatar'    => $socialUser->getAvatar(),
                $idField    => $socialUser->getId(),
                'password'  => Hash::make(Str::random(24)),
                'role'      => 'customer',
                'tinh_trang'=> 1,
            ]);
        } else {
            // Update existing user with social info
            $user->update([
                $idField => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        $token = $user->createToken('token_social')->plainTextToken;
        $feUrl = env('FRONTEND_URL', 'http://localhost:5173');

        return redirect($feUrl . '/auth/callback?token=' . $token . '&user=' . urlencode(json_encode([
            'ho_ten' => $user->ho_ten,
            'role'   => $user->role,
            'email'  => $user->email,
            'avatar' => $user->avatar
        ])));
    }
}
