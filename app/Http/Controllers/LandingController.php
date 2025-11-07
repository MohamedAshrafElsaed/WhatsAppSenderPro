<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class LandingController extends Controller
{
    /**
     * Display the landing page with dynamic pricing
     */
    public function index(): Response
    {
        $packages = Package::active()
            ->sorted()
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'name_en' => $package->name_en,
                    'name_ar' => $package->name_ar,
                    'slug' => $package->slug,
                    'price' => $package->price,
                    'formatted_price' => number_format($package->price, 0) . ' ' . (app()->getLocale() === 'ar' ? 'جنيه/شهر' : 'EGP/month'),
                    'features' => $package->features,
                    'limits' => $package->limits,
                    'is_popular' => $package->is_popular,
                    'is_best_value' => $package->is_best_value,
                    'color' => $package->color,
                ];
            });

        return Inertia::render('Welcome', [
            'packages' => $packages,
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    }
}
