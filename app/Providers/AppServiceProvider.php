<?php

namespace App\Providers;

use App\Models\ClinicSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        // Geliştirmede N+1 sorgularını ve tanımsız özellik erişimini erkenden yakala.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        View::composer(['layouts.app', 'layouts.navigation', 'layouts.guest'], function ($view) {
            $view->with('clinic', ClinicSetting::current());
        });

        Blade::directive('money', fn (string $expression) => "<?php echo e(\App\Support\Money::format({$expression})); ?>");
    }
}
