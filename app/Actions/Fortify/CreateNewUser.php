<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Throwable;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param array<string, string> $input
     * @throws Throwable
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique(User::class),
            ],
            'country_id' => ['required', 'exists:countries,id'],
            'industry_id' => ['required', 'exists:industries,id'],
            'password' => $this->passwordRules(),
        ])->validate();

        // Device tracking and JWT generation handled by event listener
        $user = User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'mobile_number' => $input['mobile_number'],
            'country_id' => $input['country_id'],
            'industry_id' => $input['industry_id'],
            'password' => Hash::make($input['password']),
            'locale' => app()->getLocale(),
        ]);

        // Create trial subscription automatically
        $subscriptionService = new SubscriptionService();
        $subscriptionService->createTrialSubscription($user);

        return $user;
    }
}
