<?php

namespace TrackSim\Resources;

use TrackSim\TrackSim;

/**
 * @phpstan-type ToggleSettings array{job_logging: bool, live_tracking: bool}
 * @phpstan-type Driver array{
 *     id: int,
 *     steam_id: string,
 *     username: string,
 *     profile_photo_url: string,
 *     client: array{is_installed: bool, version: array{version: ?string, branch: ?string, platform: ?string}|null},
 *     settings?: array{
 *         eut2: array{truck: ToggleSettings, coach: ToggleSettings},
 *         ats: array{truck: ToggleSettings, car: ToggleSettings},
 *     },
 *     is_banned: bool,
 *     last_active: string,
 * }
 */
final class Drivers
{
    public function __construct(private readonly TrackSim $client) {}

    /**
     * Add a Steam user to your company by Steam64 ID.
     *
     * Fails with errors such as `steam_profile_private`, `resource_already_exists`,
     * `driver_no_compatible_games` or `max_driver_limit_reached`.
     *
     * @return Driver
     */
    public function add(string|int $steamId): array
    {
        return $this->client->request('POST', '/v1/drivers/add', ['steam_id' => (string) $steamId]);
    }

    /** Remove a driver from your company. Fails with `driver_does_not_exist` if they aren't in it. */
    public function remove(string|int $steamId): void
    {
        $this->client->request('DELETE', '/v1/drivers/remove', ['steam_id' => (string) $steamId]);
    }

    /**
     * Get a driver's details.
     *
     * @return Driver
     */
    public function get(string|int $steamId): array
    {
        return $this->client->request('GET', '/v3/drivers/'.rawurlencode((string) $steamId).'/details');
    }

    /**
     * Change a driver's job logging and live tracking settings. Only the keys you pass change.
     *
     * @param  array{
     *     eut2_job_logging?: bool,
     *     eut2_live_tracking?: bool,
     *     eut2_coach_job_logging?: bool,
     *     eut2_coach_live_tracking?: bool,
     *     ats_job_logging?: bool,
     *     ats_live_tracking?: bool,
     *     ats_car_job_logging?: bool,
     *     ats_car_live_tracking?: bool,
     * }  $settings
     * @return Driver
     */
    public function manage(string|int $steamId, array $settings): array
    {
        return $this->client->request('PATCH', '/v3/drivers/'.rawurlencode((string) $steamId).'/manage', $settings);
    }
}
