<?php

namespace TrackSim;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use JsonException;
use TrackSim\Exceptions\ApiException;
use TrackSim\Exceptions\AuthenticationException;
use TrackSim\Exceptions\NetworkException;
use TrackSim\Exceptions\NotFoundException;
use TrackSim\Exceptions\PermissionDeniedException;
use TrackSim\Exceptions\RateLimitException;
use TrackSim\Resources\Drivers;
use TrackSim\Resources\Jobs;
use TrackSim\Resources\Maps;

class TrackSim
{
    public const VERSION = '1.0.0';

    public const DEFAULT_BASE_URL = 'https://api.tracksim.app';

    private readonly string $baseUrl;

    private readonly ClientInterface $http;

    /**
     * @param  string  $apiKey  API key from the API tab of https://tracksim.app/integrations.
     * @param  string  $baseUrl  API root, without a version. Normally left unchanged.
     * @param  float  $timeout  Maximum number of seconds to wait for a response.
     * @param  ClientInterface|null  $httpClient  A Guzzle client to send requests with.
     */
    public function __construct(
        private readonly string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $timeout = 30.0,
        ?ClientInterface $httpClient = null,
    ) {
        if (trim($apiKey) === '') {
            throw new InvalidArgumentException('A TrackSim API key is required.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->http = $httpClient ?? new Client(['timeout' => $timeout]);
    }

    /**
     * Information about the company linked to the API key.
     *
     * @return array<string, mixed>
     */
    public function me(): array
    {
        return $this->request('GET', '/v1/me');
    }

    public function drivers(): Drivers
    {
        return new Drivers($this);
    }

    public function jobs(): Jobs
    {
        return new Jobs($this);
    }

    /** Route Planner: needs a paid plan and the Route Planner add-on (60 requests a minute). */
    public function maps(): Maps
    {
        return new Maps($this);
    }

    /**
     * Send an authenticated request. `$path` includes the version, e.g. `/v1/me`.
     *
     * @param  array<string, mixed>|null  $json
     * @param  array<string, mixed>  $query
     *
     * @throws ApiException
     * @throws NetworkException
     */
    public function request(string $method, string $path, ?array $json = null, array $query = []): mixed
    {
        $options = [
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Api-Key '.trim($this->apiKey),
                'Accept' => 'application/json',
                'User-Agent' => 'tracksim-php/'.self::VERSION,
            ],
        ];
        if ($json !== null) {
            $options['json'] = $json;
        }
        if ($query !== []) {
            $options['query'] = $query;
        }

        try {
            $response = $this->http->request($method, $this->baseUrl.$path, $options);
        } catch (GuzzleException $e) {
            throw new NetworkException('Could not connect to the TrackSim API: '.$e->getMessage(), 0, $e);
        }

        $text = (string) $response->getBody();
        try {
            $body = $text === '' ? null : json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $body = $text;
        }

        $status = $response->getStatusCode();
        if ($status >= 200 && $status < 300) {
            return $body;
        }

        $error = $response->getReasonPhrase() ?: 'unknown_error';
        if (is_array($body)) {
            foreach (['error', 'reason', 'message', 'detail'] as $key) {
                if (isset($body[$key]) && is_string($body[$key]) && $body[$key] !== '') {
                    $error = $body[$key];
                    break;
                }
            }
        }

        $class = match ($status) {
            401 => AuthenticationException::class,
            403 => PermissionDeniedException::class,
            404 => NotFoundException::class,
            429 => RateLimitException::class,
            default => ApiException::class,
        };

        throw new $class($status, $error, $body);
    }
}
