<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Models\SocialNetwork;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Exception\ClientException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Auth\AuthenticationException;
use App\Http\Requests\Api\Auth\RegisterSocialNetworkUserRequest;

class RegisterSocialNetworkUserController extends ApiController
{
    public function __invoke(RegisterSocialNetworkUserRequest $request, $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->userFromToken($request->token);
        } catch (ClientException $exception) {
            throw new AuthenticationException(__('Invalid login'));
        }

        $user = User::firstOrCreate(
            [
                'email' => $socialUser->getEmail()
            ],
            [
                'email_verified_at' => now(),
                'name' => $socialUser->getName(),
                'username' => $socialUser->getNickname(),
                // 'status_id' => Status::enabled()->value('id'),
            ]
        );

        // $user->syncRoles(Role::user()->value('id'));

        $user->socialNetworks()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ],
            [
                'avatar' => $socialUser->getAvatar()
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->jsonResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    protected function validateProvider($provider)
    {
        if (!in_array($provider, SocialNetwork::PROVIDERS)) {
            $providers = implode(', ', SocialNetwork::PROVIDERS);

            throw new \Exception(__("Please login using one of the following providers: {$providers}"));
        }
    }
}
