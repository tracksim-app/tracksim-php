<?php

namespace TrackSim\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use TrackSim\TrackSim;

class TrackSimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/tracksim.php', 'tracksim');

        $this->app->singleton(TrackSim::class, function (Application $app) {
            $config = $app->make('config')->get('tracksim');

            return new TrackSim(
                apiKey: (string) ($config['api_key'] ?? '') ?: throw new InvalidArgumentException(
                    'TrackSim API key is missing. Set TRACKSIM_API_KEY in your .env file.'
                ),
                baseUrl: $config['base_url'] ?? TrackSim::DEFAULT_BASE_URL,
                timeout: (float) ($config['timeout'] ?? 30),
            );
        });

        $this->app->alias(TrackSim::class, 'tracksim');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/tracksim.php' => $this->app->configPath('tracksim.php'),
            ], 'tracksim-config');
        }
    }
}
