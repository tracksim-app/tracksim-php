<?php

namespace TrackSim\Resources;

use TrackSim\TrackSim;

/**
 * Route Planner. Needs a paid plan and the Route Planner add-on, limited to 60 requests a minute.
 *
 * @phpstan-type MapLink array{full_url: string, flag: string}
 * @phpstan-type MapRouteSummary array{
 *     id: string,
 *     game: 'eut2'|'ats',
 *     map: string,
 *     name: ?string,
 *     convoy: bool,
 *     created_at: string,
 *     updated_at: string,
 * }
 * @phpstan-type MapRoute array{
 *     id: string,
 *     game: 'eut2'|'ats',
 *     map: string,
 *     name: ?string,
 *     convoy: bool,
 *     waypoints: mixed,
 *     tenant: array{id: int, slug: string, name: string, custom_domain: array{domain: ?string}},
 *     created_at: string,
 *     updated_at: string,
 * }
 */
final class Maps
{
    public function __construct(private readonly TrackSim $client) {}

    /**
     * A one-time link for planning a new route. The `flag` JWT expires after 24 hours;
     * treat it and `full_url` as secrets.
     *
     * @return MapLink
     */
    public function create(): array
    {
        return $this->client->request('POST', '/v3/maps/create');
    }

    /**
     * A one-time link for editing an existing route. Expires after 24 hours.
     *
     * @return MapLink
     */
    public function edit(string $routeId): array
    {
        return $this->client->request('PATCH', '/v3/maps/'.rawurlencode($routeId).'/edit');
    }

    /**
     * Every saved route in your company, without waypoints.
     *
     * @return list<MapRouteSummary>
     */
    public function list(): array
    {
        return $this->client->request('GET', '/v3/maps/routes');
    }

    /**
     * One saved route, including its waypoints.
     *
     * @return MapRoute
     */
    public function get(string $routeId): array
    {
        return $this->client->request('GET', '/v3/maps/'.rawurlencode($routeId).'/route');
    }

    public function delete(string $routeId): void
    {
        $this->client->request('DELETE', '/v3/maps/'.rawurlencode($routeId).'/route');
    }
}
