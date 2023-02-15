<?php

namespace OwowAgency\ProfanityValidation;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * The name of the package.
     */
    private string $name = 'profanity';

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishableFiles();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__."/../config/$this->name.php", $this->name);
    }

    /**
     * Register files to be published by the publish command.
     */
    protected function registerPublishableFiles(): void
    {
        $this->publishes(
            [
                __DIR__."/../config/$this->name.php" => config_path("$this->name.php"),
            ],
            [$this->name, "$this->name.config", 'config'],
        );
    }
}
