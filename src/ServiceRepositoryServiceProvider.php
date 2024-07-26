<?php

namespace Ranken\ServiceRepositoryGenerator;

use Illuminate\Support\ServiceProvider;

class ServiceRepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/service-repo.php', 'service-repo');

        $this->commands([
            \Ranken\ServiceRepositoryGenerator\Commands\MakeServiceWithRepository::class,
            \Ranken\ServiceRepositoryGenerator\Commands\InstallPackage::class,
        ]);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ .'/config/service-repo.php' => config_path('service-repo.php'),
            ], 'config');

            $this->publishes([
                __DIR__ .'/Providers/ServicePatternProvider.php' => app_path('Providers/ServicePatternProvider.php'),
                __DIR__ .'/Providers/RepositoryPatternProvider.php' => app_path('Providers/RepositoryPatternProvider.php'),
            ], 'providers');
        }
    }
}