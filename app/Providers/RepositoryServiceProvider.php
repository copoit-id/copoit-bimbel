<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\PackageRepositoryInterface;
use App\Repositories\PackageRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PackageRepositoryInterface::class, PackageRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
