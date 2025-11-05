<?php

return [
    // ... existing validation messages

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'first_name' => 'first name',
        'last_name' => 'last name',
        'email' => 'email address',
        'mobile_number' => 'mobile number',
        'country_id' => 'country',
        'industry_id' => 'industry',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    */
    'custom' => [
        'first_name' => [
            'required' => 'Please enter your first name.',
            'min' => 'First name must be at least :min characters.',
            'max' => 'First name cannot exceed :max characters.',
        ],
        'last_name' => [
            'required' => 'Please enter your last name.',
            'min' => 'Last name must be at least :min characters.',
            'max' => 'Last name cannot exceed :max characters.',
        ],
        'email' => [
            'required' => 'Please enter your email address.',
            'email' => 'Please enter a valid email address.',
            'unique' => 'This email is already registered. Please log in or use a different email.',
        ],
        'mobile_number' => [
            'required' => 'Please enter your mobile number.',
            'regex' => 'Please enter a valid mobile number (digits only, 10-15 characters).',
            'unique' => 'This mobile number is already registered.',
        ],
        'country_id' => [
            'required' => 'Please select your country.',
            'exists' => 'Please select a valid country.',
        ],
        'industry_id' => [
            'required' => 'Please select your industry.',
            'exists' => 'Please select a valid industry.',
        ],
        'password' => [
            'required' => 'Please enter a password.',
            'min' => 'Password must be at least :min characters.',
            'confirmed' => 'Password confirmation does not match.',
        ],
    ],
];
