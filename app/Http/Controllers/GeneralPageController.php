<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class GeneralPageController extends Controller
{
    public function landing()
    {
        return view('general.placeholder', [
            'title' => 'Landing Page',
        ]);
    }

    public function statistics()
    {
        return view('general.statistics', [
            'title' => 'Statistik PTN',
        ]);
    }

    public function proxyPtnList()
    {
        // Cache the PTN list for 6 hours
        $data = Cache::remember('snpmb_ptn_list', 3600 * 6, function () {
            try {
                $response = Http::timeout(10)->get('https://snpmb.id/proxy-ptn-sn.php');
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                logger()->error('Error fetching PTN list from SNPMB: ' . $e->getMessage());
            }
            return null;
        });

        if ($data === null) {
            return response()->json(['error' => 'Gagal mengambil data PTN dari server pusat.'], 502);
        }

        return response()->json($data);
    }

    public function proxyProdiList(Request $request)
    {
        $ptnId = $request->query('ptn');
        if (!$ptnId) {
            return response()->json(['error' => 'Parameter ptn wajib diisi.'], 400);
        }

        // Validate parameter to prevent directory traversal or arbitrary requests
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $ptnId)) {
            return response()->json(['error' => 'Parameter ptn tidak valid.'], 400);
        }

        // Cache the Prodi list per PTN for 6 hours
        $cacheKey = 'snpmb_prodi_list_' . $ptnId;
        $data = Cache::remember($cacheKey, 3600 * 6, function () use ($ptnId) {
            try {
                $response = Http::timeout(10)->get("https://snpmb.id/proxy-prodi-sn.php", [
                    'ptn' => $ptnId
                ]);
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                logger()->error("Error fetching Prodi list for PTN {$ptnId} from SNPMB: " . $e->getMessage());
            }
            return null;
        });

        if ($data === null) {
            return response()->json(['error' => 'Gagal mengambil data Program Studi dari server pusat.'], 502);
        }

        return response()->json($data);
    }

    public function articles()
    {
        $articles = Article::query()
            ->with('author:id,name')
            ->published()
            ->latest('published_at')
            ->paginate(9);

        $featuredArticle = Article::query()
            ->with('author:id,name')
            ->published()
            ->latest('published_at')
            ->first();

        return view('general.articles.index', compact('articles', 'featuredArticle'));
    }

    public function showArticle(string $slug)
    {
        $article = Article::query()
            ->with('author:id,name')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedArticles = Article::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('general.articles.show', compact('article', 'relatedArticles'));
    }
}
