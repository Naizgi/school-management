<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'), // Default to API (JWT)
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | Supported: "session", "jwt"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [ // ✅ JWT Guard
            'driver' => 'jwt',
            'provider' => 'users',
            'hash' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    */

   'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class, // ✅ Make sure the User model is correctly referenced
    ],
],

    /*
    |--------------------------------------------------------------------------
    | Role-Based Guards (Optional)
    |--------------------------------------------------------------------------
    |
    | If you want separate authentication for Admins, Instructors, and Parents,
    | you can define them here using different providers and guards.
    |
    */

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [ // ✅ Make sure this exists
        'driver' => 'jwt',
        'provider' => 'users',
        'hash' => false,
    ],
],


    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | This defines the amount of seconds before a password confirmation
    | window expires and users are asked to re-enter their password.
    |
    */

    'password_timeout' => 10800,

];
