<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LandingpageHero;
use App\Models\LandingpageFeature;
use App\Models\LandingpageGallery;
use App\Models\LandingpageTestimonial;
use App\Models\LandingpageCta;
use App\Models\ClientProfile;

class LandingPageController extends Controller
{
    public function index()
    {
        $hero = LandingpageHero::where('is_active', true)->first();
        $features = LandingpageFeature::active()->ordered()->get();
        $gallery = LandingpageGallery::active()->ordered()->get();
        $testimonials = LandingpageTestimonial::active()->ordered()->get();
        $cta = LandingpageCta::where('is_active', true)->first();
        
        $clientBranding = config('client.branding');

        return view('landing-page.index', compact('hero', 'features', 'gallery', 'testimonials', 'cta', 'clientBranding'));
    }
}
