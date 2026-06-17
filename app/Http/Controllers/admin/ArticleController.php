<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $articles = Article::query()
            ->with('author:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('excerpt', 'like', '%' . $search . '%');
                });
            })
            ->when(in_array($status, [Article::STATUS_DRAFT, Article::STATUS_PUBLISHED], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $counts = [
            'all' => Article::count(),
            'published' => Article::where('status', Article::STATUS_PUBLISHED)->count(),
            'draft' => Article::where('status', Article::STATUS_DRAFT)->count(),
        ];

        return view('admin.pages.general.articles.index', compact('articles', 'counts', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.pages.general.articles.form', [
            'article' => new Article([
                'status' => Article::STATUS_DRAFT,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateArticle($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? $validated['title']);
        $validated['author_id'] = Auth::id();
        $validated['excerpt'] = $this->normalizeExcerpt($validated['excerpt'] ?? null, $validated['content']);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('articles/covers', 'public');
        }

        if (($validated['status'] ?? Article::STATUS_DRAFT) === Article::STATUS_PUBLISHED && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        Article::create($validated);

        return redirect()
            ->route('admin.artikel.index')
            ->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function edit(Article $artikel)
    {
        return view('admin.pages.general.articles.form', [
            'article' => $artikel,
        ]);
    }

    public function update(Request $request, Article $artikel)
    {
        $validated = $this->validateArticle($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? $validated['title'], $artikel->id);
        $validated['excerpt'] = $this->normalizeExcerpt($validated['excerpt'] ?? null, $validated['content']);

        if ($request->hasFile('cover_image')) {
            $this->deleteCover($artikel->cover_image);
            $validated['cover_image'] = $request->file('cover_image')->store('articles/covers', 'public');
        } elseif ($request->boolean('remove_cover')) {
            $this->deleteCover($artikel->cover_image);
            $validated['cover_image'] = null;
        }

        if (($validated['status'] ?? Article::STATUS_DRAFT) === Article::STATUS_PUBLISHED && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if (($validated['status'] ?? Article::STATUS_DRAFT) === Article::STATUS_DRAFT) {
            $validated['published_at'] = null;
        }

        $artikel->update($validated);

        return redirect()
            ->route('admin.artikel.index')
            ->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Article $artikel)
    {
        $this->deleteCover($artikel->cover_image);
        $artikel->delete();

        return redirect()
            ->route('admin.artikel.index')
            ->with('success', 'Artikel berhasil dihapus.');
    }

    private function validateArticle(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:' . Article::STATUS_DRAFT . ',' . Article::STATUS_PUBLISHED],
            'published_at' => ['nullable', 'date'],
            'remove_cover' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul artikel wajib diisi.',
            'content.required' => 'Isi artikel wajib diisi.',
            'cover_image.max' => 'Cover artikel maksimal 5MB.',
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'artikel';
        $slug = $base;
        $counter = 2;

        while (Article::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function normalizeExcerpt(?string $excerpt, string $content): string
    {
        $excerpt = trim((string) $excerpt);

        if ($excerpt !== '') {
            return $excerpt;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 180);
    }

    private function deleteCover(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
