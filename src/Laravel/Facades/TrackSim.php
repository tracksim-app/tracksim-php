<?php

namespace TrackSim\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> me()
 * @method static \TrackSim\Resources\Drivers drivers()
 * @method static \TrackSim\Resources\Jobs jobs()
 * @method static \TrackSim\Resources\Maps maps()
 * @method static mixed request(string $method, string $path, array<string, mixed>|null $json = null, array<string, mixed> $query = [])
 *
 * @see \TrackSim\TrackSim
 */
class TrackSim extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \TrackSim\TrackSim::class;
    }
}
