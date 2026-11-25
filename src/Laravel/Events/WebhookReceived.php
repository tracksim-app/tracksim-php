<?php

namespace TrackSim\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Dispatched for every TrackSim webhook with a valid signature. */
class WebhookReceived
{
    use Dispatchable;

    /**
     * @param  string  $type  e.g. `job.delivered`, `car_job.started`, `bus_job.completed`, `fine`.
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data,
    ) {}
}
