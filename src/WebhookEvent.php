<?php

namespace TrackSim;

/**
 * A verified webhook delivery. See https://docs.tracksim.app for each event's `data`.
 */
final class WebhookEvent
{
    /**
     * @param  string  $type  e.g. `job.delivered`, `car_job.started`, `bus_job.completed`, `fine`.
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data,
        public readonly string $object = 'event',
    ) {}
}
