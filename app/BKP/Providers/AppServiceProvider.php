<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenAI\Factory;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    public function register()
    {
        $this->app->singleton(\OpenAI\Client::class, function () {
            return Factory::build()->withKey(env('OPENAI_API_KEY'))->make();
        });
    }
}
