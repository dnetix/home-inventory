<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Lend;
use App\Models\Place;
use App\Models\Tag;
use App\Models\UpkeepLog;
use App\Models\UpkeepTask;
use App\Enums\Unit;
use App\Policies\HomeScopedPolicy;
use App\Support\CurrentHome;
use App\Support\UnitFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentHome::class);

        $this->app->scoped(UnitFormatter::class, function () {
            return UnitFormatter::for(Auth::user()?->unit ?? Unit::Metric);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Category::class, Tag::class, Place::class, Item::class, Lend::class, UpkeepTask::class, UpkeepLog::class] as $model) {
            Gate::policy($model, HomeScopedPolicy::class);
        }
    }
}
