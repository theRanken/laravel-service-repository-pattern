<?php

namespace Ranken\ServiceRepositoryGenerator\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServiceWithRepository extends Command
{
    protected $signature = 'make:service {name} {--r|repository}';
    protected $description = 'Create a service and optionally a repository with interfaces and implementations, and update the service/repository provider.';

    protected $baseNamespace;
    protected $servicesPath;
    protected $repositoriesPath;

    public function handle()
    {
        $this->initializeProperties();

        $name = $this->filterName($this->argument('name'));
        $createRepository = $this->option('repository');

        $this->createService($name);

        if ($createRepository) {
            $this->createRepository($name);
            $this->updateRepositoryProvider($name);
        }

        $this->updateServiceProvider($name);
    }

    protected function initializeProperties()
    {
        $this->baseNamespace = app()->getNamespace();
        $this->servicesPath = config('service-repo.services_path', 'Services');
        $this->repositoriesPath = config('service-repo.repositories_path', 'Repositories');
    }

    protected function filterName($name)
    {
        return preg_replace('/(Service|Repository)/i', '', $name);
    }

    protected function createService($name)
    {
        $namespace = $this->baseNamespace . str_replace('/', '\\', $this->servicesPath) . "\\{$name}";
        $interfacePath = app_path("{$this->servicesPath}/{$name}/{$name}ServiceInterface.php");
        $implementationPath = app_path("{$this->servicesPath}/{$name}/{$name}Service.php");

        $interfaceContent = $this->getStubContent('service.interface', [
            'namespace' => $namespace,
            'name' => $name
        ]);

        $implementationContent = $this->getStubContent('service.class', [
            'namespace' => $namespace,
            'name' => $name
        ]);

        File::ensureDirectoryExists(dirname($interfacePath));
        File::put($interfacePath, $interfaceContent);
        File::put($implementationPath, $implementationContent);

        $this->info("{$name} Service created successfully.");
    }

    protected function createRepository($name)
    {
        $namespace = $this->baseNamespace . str_replace('/', '\\', $this->repositoriesPath) . "\\{$name}";
        $interfacePath = app_path("{$this->repositoriesPath}/{$name}/{$name}RepositoryInterface.php");
        $implementationPath = app_path("{$this->repositoriesPath}/{$name}/{$name}Repository.php");

        $interfaceContent = $this->getStubContent('repository.interface', [
            'namespace' => $namespace,
            'name' => $name
        ]);

        $implementationContent = $this->getStubContent('repository.class', [
            'namespace' => $namespace,
            'name' => $name
        ]);

        File::ensureDirectoryExists(dirname($interfacePath));
        File::put($interfacePath, $interfaceContent);
        File::put($implementationPath, $implementationContent);

        $this->info("{$name} Repository created successfully.");
    }

    protected function updateServiceProvider($name)
    {
        $providerPath = app_path('Providers/ServicePatternProvider.php');

        if (!File::exists($providerPath)) {
            $this->error('ServicePatternProvider not found!');
            return;
        }

        $namespace = $this->baseNamespace . str_replace('/', '\\', $this->servicesPath);
        $interface = "{$namespace}\\{$name}\\{$name}ServiceInterface";
        $implementation = "{$namespace}\\{$name}\\{$name}Service";
        $binding = "\$this->app->bind({$interface}::class, {$implementation}::class);";

        $this->updateProvider($providerPath, $binding);
    }

    protected function updateRepositoryProvider($name)
    {
        $providerPath = app_path('Providers/RepositoryPatternProvider.php');

        if (!File::exists($providerPath)) {
            $this->error('RepositoryPatternProvider not found!');
            return;
        }

        $namespace = $this->baseNamespace . str_replace('/', '\\', $this->repositoriesPath);
        $interface = "{$namespace}\\{$name}\\{$name}RepositoryInterface";
        $implementation = "{$namespace}\\{$name}\\{$name}Repository";
        $binding = "\$this->app->bind({$interface}::class, {$implementation}::class);";

        $this->updateProvider($providerPath, $binding);
    }

    protected function updateProvider($providerPath, $binding)
    {
        $content = File::get($providerPath);
        if (strpos($content, $binding) === false) {
            $content = str_replace('//:end-bindings:', "{$binding}\n        //:end-bindings:", $content);
            File::put($providerPath, $content);
            $this->info("Provider updated successfully.");
        } else {
            $this->info("Binding already exists in the Provider.");
        }
    }

    protected function getStubContent($stub, $replacements)
    {
        $stubPath = __DIR__.'/../stubs/'.$stub.'.stub';
        $content = file_get_contents($stubPath);
        foreach ($replacements as $search => $replace) {
            $content = str_replace('{{ '.$search.' }}', $replace, $content);
        }
        return $content;
    }
}