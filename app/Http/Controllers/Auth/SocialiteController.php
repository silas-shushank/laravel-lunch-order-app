<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Models\SocialAccount;
use App\Models\User;

class SocialiteController extends Controller
{

    /**
     * Redirect the user to the Provider authentication page.
     *
     * @param $provider String
     * @return mixed
     */
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from Provider.
     *
     * @param $provider string
     * @throws \Exception
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback($provider)
    {
        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            // Send actual error message in development
            if (config('app.debug')) {
                throw $e;
            }
            // We are not logging this error to error reporting service
            alert()->error('Unable to authenticate. Please try again.');
            return redirect()->route('login');
        }

        DB::beginTransaction();
        try {
            $user = $this->createOrGetUser($provider, $providerUser);
            Auth::login($user, true);
            // This session variable determines if user can change his password without entering old
            session()->put(['auth.social_id' => $providerUser->getId()]);

            DB::commit();

            return $this->authenticated($user)
                ?: redirect()->intended('/');
        } catch (\Exception $e) {
            DB::rollBack();

            // Send actual error message in development
            if (config('app.debug')) {
                throw $e;
            }

            // Log errors
            Log::error($e);
        }

        alert()->error('Something went wrong. Please try again.');
        return redirect()->route('login');
    }

    /**
     * Create a user if does not exist
     *
     * @param $providerName string
     * @param $providerUser
     * @return mixed
     */
    private function createOrGetUser($providerName, $providerUser)
    {
        $social = SocialAccount::firstOrNew([
            'provider_user_id' => $providerUser->getId(),
            'provider' => $providerName
        ]);

        if ($social->exists) {
            return $social->user;
        } else {

            $user = User::firstOrNew([
                'email' => $providerUser->getEmail()
            ]);

            if (!$user->exists) {
                $user->save();
                $user->assignRole('customer');
                $user->profile()->create($this->getProfileData($providerUser));
            }

            $social->user()->associate($user);
            $social->save();

            return $user;

        }

    }

    /**
     * The user has been authenticated.
     *
     * @param  User $user
     * @return mixed
     */
    private function authenticated(User $user)
    {
        if ($user->is_blocked) {
            // Logout this user
            Auth::guard()->logout();
            session()->invalidate();

            alert()->error('Your account is disabled. Please contact administrator for assistance.');
            return redirect()->route('login');
        }
    }

    /**
     * Extract user profile data
     *
     * @param $providerUser
     * @return array
     */
    private function getProfileData($providerUser)
    {
        $nameParts = explode(' ', $providerUser->getName());
        return [
            'first_name' => $nameParts[0],
            'last_name' => optional($nameParts)[1],
            'avatar' => preg_replace('/\?sz=[\d]*$/', '', $providerUser->getAvatar()),
        ];
    }
}
