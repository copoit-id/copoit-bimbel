# AGENTS.md - BIMBELHUB

## Primary Reference

**SELALU BACA DAN PATUHI:** `docs/CODING_RULES.md`

Dokumen ini berisi aturan wajib untuk:
- File system naming (case-sensitive!)
- Laravel best practices (Controller, Model, Route, View)
- Query optimization (hindari N+1)
- Migration safety (production safe)
- Performance checklist

## Project Overview

- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Database:** MySQL
- **Frontend:** Blade + Tailwind CSS + Alpine.js
- **Server:** Linux (Ubuntu) - **CASE SENSITIVE!**

## Critical Rules

### 1. File System (CASE SENSITIVE)
```
❌ resources/views/components/ui/Button/
✅ resources/views/components/ui/button/

❌ <x-ui.Button>
✅ <x-ui.button>
```

### 2. No N+1 Query
```php
// ❌ N+1
Package::all()->map(fn($p) => $p->category->name);

// ✅ Eager load
Package::with('category')->get();
```

### 3. Migration Safety
```php
if (!Schema::hasColumn('table', 'column')) {
    $table->string('column');
}
```

## Quick Commands

```bash
# Development
php artisan serve
npm run dev

# Production deploy
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Clear cache (debugging)
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Common Issues

### 1. Component not found (Linux only)
**Cause:** Folder name case mismatch  
**Fix:** Rename folder to lowercase
```bash
cd resources/views/components/ui
mv Button button
mv Card card
```

### 2. Migration failed
**Cause:** Duplicate column or missing column  
**Fix:** Check `docs/CODING_RULES.md` section 4

### 3. Slow query
**Cause:** N+1 or missing eager load  
**Fix:** Add `->with()` atau cek Laravel Debugbar

## Environment

- **Local:** macOS (case-insensitive)
- **Production:** Linux (case-sensitive)
- **Always assume case-sensitive!**
