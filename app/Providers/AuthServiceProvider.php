<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\ContactImport;
use App\Policies\ContactImportPolicy;
use App\Policies\ContactPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected array $policies = [
        Contact::class => ContactPolicy::class,
        ContactImport::class => ContactImportPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
