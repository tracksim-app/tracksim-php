<?php

namespace TrackSim\Resources;

use TrackSim\JobType;
use TrackSim\TrackSim;

/**
 * @phpstan-type RoutePoint array{
 *     x: float,
 *     z: float,
 *     time: int,
 *     speed_kmh?: float,
 *     satisfaction?: float,
 *     peak_lateral_g?: float,
 *     peak_braking_g?: float,
 *     peak_acceleration_g?: float,
 *     peak_vertical_g?: float,
 * }
 */
final class Jobs
{
    public function __construct(private readonly TrackSim $client) {}

    /**
     * The recorded route of a job, from start to finish. Bus routes add speed,
     * passenger satisfaction and peak G-force fields. Routes never change, so cache them.
     *
     * @param  JobType|'truck'|'car'|'bus'  $type
     * @return list<RoutePoint>
     */
    public function route(int|string $jobId, JobType|string $type): array
    {
        $type = is_string($type) ? JobType::from($type) : $type;

        return $this->client->request(
            'GET',
            '/v2/jobs/'.rawurlencode((string) $jobId).'/route',
            query: ['job_type' => $type->value],
        );
    }
}
