<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LandingpageHero;
use App\Models\LandingpageFeature;
use App\Models\LandingpageGallery;
use App\Models\LandingpageTestimonial;
use App\Models\LandingpageCta;
use Illuminate\Support\Facades\Storage;

class AdminLandingPageController extends Controller
{
    // Hero Section Methods
    public function heroIndex()
    {
        $hero = LandingpageHero::latest()->first();

        if ($hero) {
            return redirect()->route('admin.landing-page.hero.edit', $hero);
        }

        return redirect()->route('admin.landing-page.hero.create');
    }

    public function heroCreate()
    {
        return view('admin.landing-page.hero.create');
    }

    public function heroStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string',
            'description' => 'nullable|string',
            'button_text' => 'required|string|max:255',
            'button_link' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stat_1_number' => 'required|string|max:50',
            'stat_1_text' => 'required|string|max:100',
            'stat_2_number' => 'required|string|max:50',
            'stat_2_text' => 'required|string|max:100',
            'stat_3_number' => 'required|string|max:50',
            'stat_3_text' => 'required|string|max:100',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('landing-page/hero', 'public');
        }

        LandingpageHero::create($data);

        return redirect()->route('admin.landing-page.hero.index')
            ->with('success', 'Hero section berhasil ditambahkan');
    }

    public function heroEdit(LandingpageHero $hero)
    {
        return view('admin.landing-page.hero.edit', compact('hero'));
    }

    public function heroUpdate(Request $request, LandingpageHero $hero)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string',
            'description' => 'nullable|string',
            'button_text' => 'required|string|max:255',
            'button_link' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stat_1_number' => 'required|string|max:50',
            'stat_1_text' => 'required|string|max:100',
            'stat_2_number' => 'required|string|max:50',
            'stat_2_text' => 'required|string|max:100',
            'stat_3_number' => 'required|string|max:50',
            'stat_3_text' => 'required|string|max:100',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            if ($hero->image) {
                Storage::disk('public')->delete($hero->image);
            }
            $data['image'] = $request->file('image')->store('landing-page/hero', 'public');
        }

        $hero->update($data);

        return redirect()->route('admin.landing-page.hero.index')
            ->with('success', 'Hero section berhasil diperbarui');
    }

    public function heroDestroy(LandingpageHero $hero)
    {
        if ($hero->image) {
            Storage::disk('public')->delete($hero->image);
        }
        $hero->delete();

        return redirect()->route('admin.landing-page.hero.index')
            ->with('success', 'Hero section berhasil dihapus');
    }

    // Features Methods
    public function featuresIndex()
    {
        $features = LandingpageFeature::ordered()->get();
        return view('admin.landing-page.features.index', compact('features'));
    }

    public function featuresCreate()
    {
        return view('admin.landing-page.features.create');
    }

    public function featuresStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        LandingpageFeature::create($request->all());

        return redirect()->route('admin.landing-page.features.index')
            ->with('success', 'Keunggulan berhasil ditambahkan');
    }

    public function featuresEdit(LandingpageFeature $feature)
    {
        return view('admin.landing-page.features.edit', compact('feature'));
    }

    public function featuresUpdate(Request $request, LandingpageFeature $feature)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        $feature->update($request->all());

        return redirect()->route('admin.landing-page.features.index')
            ->with('success', 'Keunggulan berhasil diperbarui');
    }

    public function featuresDestroy(LandingpageFeature $feature)
    {
        $feature->delete();

        return redirect()->route('admin.landing-page.features.index')
            ->with('success', 'Keunggulan berhasil dihapus');
    }

    // Gallery Methods
    public function galleryIndex()
    {
        $gallery = LandingpageGallery::ordered()->get();
        return view('admin.landing-page.gallery.index', compact('gallery'));
    }

    public function galleryCreate()
    {
        return view('admin.landing-page.gallery.create');
    }

    public function galleryStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        $data = $request->all();
        $data['image'] = $request->file('image')->store('landing-page/gallery', 'public');

        LandingpageGallery::create($data);

        return redirect()->route('admin.landing-page.gallery.index')
            ->with('success', 'Gallery berhasil ditambahkan');
    }

    public function galleryEdit(LandingpageGallery $gallery)
    {
        return view('admin.landing-page.gallery.edit', compact('gallery'));
    }

    public function galleryUpdate(Request $request, LandingpageGallery $gallery)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            if ($gallery->image) {
                Storage::disk('public')->delete($gallery->image);
            }
            $data['image'] = $request->file('image')->store('landing-page/gallery', 'public');
        }

        $gallery->update($data);

        return redirect()->route('admin.landing-page.gallery.index')
            ->with('success', 'Gallery berhasil diperbarui');
    }

    public function galleryDestroy(LandingpageGallery $gallery)
    {
        if ($gallery->image) {
            Storage::disk('public')->delete($gallery->image);
        }
        $gallery->delete();

        return redirect()->route('admin.landing-page.gallery.index')
            ->with('success', 'Gallery berhasil dihapus');
    }

    // Testimonials Methods
    public function testimonialsIndex()
    {
        $testimonials = LandingpageTestimonial::ordered()->get();
        return view('admin.landing-page.testimonials.index', compact('testimonials'));
    }

    public function testimonialsCreate()
    {
        return view('admin.landing-page.testimonials.create');
    }

    public function testimonialsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'content' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rating' => 'required|integer|min:1|max:5',
            'order' => 'required|integer|min:0',
        ]);

        $data = $request->all();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('landing-page/testimonials', 'public');
        }

        LandingpageTestimonial::create($data);

        return redirect()->route('admin.landing-page.testimonials.index')
            ->with('success', 'Testimoni berhasil ditambahkan');
    }

    public function testimonialsEdit(LandingpageTestimonial $testimonial)
    {
        return view('admin.landing-page.testimonials.edit', compact('testimonial'));
    }

    public function testimonialsUpdate(Request $request, LandingpageTestimonial $testimonial)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'content' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rating' => 'required|integer|min:1|max:5',
            'order' => 'required|integer|min:0',
        ]);

        $data = $request->all();

        if ($request->hasFile('photo')) {
            if ($testimonial->photo) {
                Storage::disk('public')->delete($testimonial->photo);
            }
            $data['photo'] = $request->file('photo')->store('landing-page/testimonials', 'public');
        }

        $testimonial->update($data);

        return redirect()->route('admin.landing-page.testimonials.index')
            ->with('success', 'Testimoni berhasil diperbarui');
    }

    public function testimonialsDestroy(LandingpageTestimonial $testimonial)
    {
        if ($testimonial->photo) {
            Storage::disk('public')->delete($testimonial->photo);
        }
        $testimonial->delete();

        return redirect()->route('admin.landing-page.testimonials.index')
            ->with('success', 'Testimoni berhasil dihapus');
    }

    // CTA Section Methods
    public function ctaIndex()
    {
        $cta = LandingpageCta::first();
        return view('admin.landing-page.cta.index', compact('cta'));
    }

    public function ctaCreate()
    {
        return view('admin.landing-page.cta.create');
    }

    public function ctaStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'primary_button_text' => 'required|string|max:100',
            'secondary_button_text' => 'required|string|max:100',
        ]);

        // Hanya boleh ada 1 CTA section, hapus yang lama jika ada
        LandingpageCta::truncate();

        LandingpageCta::create($request->all());

        return redirect()->route('admin.landing-page.cta.index')
            ->with('success', 'CTA section berhasil ditambahkan');
    }

    public function ctaEdit()
    {
        $cta = LandingpageCta::first();
        if (!$cta) {
            return redirect()->route('admin.landing-page.cta.create');
        }
        return view('admin.landing-page.cta.edit', compact('cta'));
    }

    public function ctaUpdate(Request $request, LandingpageCta $cta)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'primary_button_text' => 'required|string|max:100',
            'secondary_button_text' => 'required|string|max:100',
        ]);

        $cta->update($request->all());

        return redirect()->route('admin.landing-page.cta.index')
            ->with('success', 'CTA section berhasil diperbarui');
    }

    public function ctaDestroy(LandingpageCta $cta)
    {
        $cta->delete();

        return redirect()->route('admin.landing-page.cta.index')
            ->with('success', 'CTA section berhasil dihapus');
    }
}
