<?php

namespace Ranken\ServiceRepositoryGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallPackage extends Command
{
    protected $signature = 'service-repository:install';

    protected $description = 'Install the Service Repository package';

    public function handle()
    {
        $this->info('Installing Service Repository Package...');

        $this->publishConfiguration();
        $this->publishProviders();
        $this->registerProviders();

        $this->info('Service Repository Package installed successfully.');
    }

    protected function publishConfiguration()
    {
        if (!File::exists(config_path('service-repo.php'))) {
            $this->call('vendor:publish', [
                '--provider' => "Ranken\ServiceRepositoryGenerator\ServiceRepositoryServiceProvider",
                '--tag' => "config"
            ]);
        } else {
            if ($this->confirm('The service-repo configuration file already exists. Do you want to overwrite it?')) {
                $this->call('vendor:publish', [
                    '--provider' => "Ranken\ServiceRepositoryGenerator\ServiceRepositoryServiceProvider",
                    '--tag' => "config",
                    '--force' => true
                ]);
            }
        }
    }

    protected function publishProviders()
    {
        $this->call('vendor:publish', [
            '--provider' => "Ranken\ServiceRepositoryGenerator\ServiceRepositoryServiceProvider",
            '--tag' => "providers"
        ]);
    }

    protected function registerProviders()
    {
        $appConfig = file_get_contents(config_path('app.php'));

        $providers = [
            'App\Providers\ServicePatternProvider::class',
            'App\Providers\RepositoryPatternProvider::class',
        ];

        foreach ($providers as $provider) {
            if (strpos($appConfig, $provider) === false) {
                $appConfig = str_replace(
                    "        App\Providers\RouteServiceProvider::class,",
                    "        App\Providers\RouteServiceProvider::class,\n        $provider,",
                    $appConfig
                );
            }
        }

        file_put_contents(config_path('app.php'), $appConfig);
    }
}