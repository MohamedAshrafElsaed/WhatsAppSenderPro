<?php

namespace App\Http\Middleware;

use Exception;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Log;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'full_name' => $request->user()->full_name,
                    'email' => $request->user()->email,
                    'mobile_number' => $request->user()->mobile_number,
                    'formatted_mobile' => $request->user()->formatted_mobile,
                    'country' => $request->user()->country ? [
                        'id' => $request->user()->country->id,
                        'name' => $request->user()->country->name,
                        'phone_code' => $request->user()->country->phone_code,
                        'iso_code' => $request->user()->country->iso_code,
                    ] : null,
                    'industry' => $request->user()->industry ? [
                        'id' => $request->user()->industry->id,
                        'name' => $request->user()->industry->name,
                        'slug' => $request->user()->industry->slug,
                    ] : null,
                    'locale' => $request->user()->locale,
                    'email_verified_at' => $request->user()->email_verified_at?->toISOString(),
                    'mobile_verified_at' => $request->user()->mobile_verified_at?->toISOString(),
                ] : null,
                'jwt_token' => session('jwt_token'),
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info'),
            ],
            'errors' => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object)[],
            'locale' => app()->getLocale(),
            'translations' => $this->getTranslations(),
            'import_preview' => fn() => $request->session()->get('import_preview'),
            'import_summary' => fn() => $request->session()->get('import_summary'),
        ];
    }

    /**
     * Get translations for current locale
     *
     * @return array
     */
    protected function getTranslations(): array
    {
        $locale = app()->getLocale();
        $filePath = lang_path("{$locale}.json");

        if (!file_exists($filePath)) {
            return [];
        }

        try {
            $contents = file_get_contents($filePath);
            $translations = json_decode($contents, true);

            return $translations ?? [];
        } catch (Exception $e) {
            Log::error("Failed to load translations for locale: {$locale}", [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }


    public function handle(Request $request, $next): Response
    {
        $response = parent::handle($request, $next);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
