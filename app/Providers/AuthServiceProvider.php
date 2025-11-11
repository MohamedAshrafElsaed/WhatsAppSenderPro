<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\Template;
use App\Policies\ContactImportPolicy;
use App\Policies\ContactPolicy;
use App\Policies\TemplatePolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected array $policies = [
        Contact::class => ContactPolicy::class,
        ContactImport::class => ContactImportPolicy::class,
        Template::class => TemplatePolicy::class,
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
