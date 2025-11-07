<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class StaticPageController extends Controller
{
    /**
     * Display the About Us page
     */
    public function about(): Response
    {
        return Inertia::render('staticPages/About');
    }

    /**
     * Display the Contact page
     */
    public function contact(): Response
    {
        return Inertia::render('staticPages/Contact');
    }

    /**
     * Display the Privacy Policy page
     */
    public function privacy(): Response
    {
        return Inertia::render('staticPages/Privacy');
    }

    /**
     * Display the Terms of Service page
     */
    public function terms(): Response
    {
        return Inertia::render('staticPages/Terms');
    }
}
