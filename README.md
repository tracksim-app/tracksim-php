<p align="center">
  <img src="https://tracksim-prod.s3.eu-west-2.amazonaws.com/public/logos/TrackSim-logo-full-color.png" alt="TrackSim" width="360" />
</p>

<h1 align="center">TrackSim PHP / Laravel SDK</h1>

<p align="center">
  The official PHP client for the <a href="https://docs.tracksim.app">TrackSim API</a> and webhooks.<br/>
  Works in any PHP 8.2+ project, with first-class Laravel 12 and 13 support.
</p>

---

## Install

```bash
composer require tracksim/tracksim-php
```

Generate an API key on the **API** tab of [tracksim.app/integrations](https://tracksim.app/integrations). You can only see the key once, so copy it when you create it.

## Laravel

The service provider and `TrackSim` facade register themselves. Add your credentials to `.env`:

```dotenv
TRACKSIM_API_KEY=your-api-key
TRACKSIM_WEBHOOK_SECRET=your-webhook-secret
```

Then use the facade, or inject `TrackSim\TrackSim` anywhere the container resolves dependencies:

```php
use TrackSim\Laravel\Facades\TrackSim;

$company = TrackSim::me();
$driver = TrackSim::drivers()->add('76561198000000000');
```

```php
use TrackSim\TrackSim;

class SyncDriver
{
    public function __construct(private TrackSim $tracksim) {}

    public function handle(string $steamId): array
    {
        return $this->tracksim->drivers()->get($steamId);
    }
}
```

To change the base URL or timeout, publish the config file:

```bash
php artisan vendor:publish --tag=tracksim-config
```

## Plain PHP

```php
use TrackSim\TrackSim;

$tracksim = new TrackSim(getenv('TRACKSIM_API_KEY'));

$company = $tracksim->me();
```

The constructor also takes `baseUrl`, `timeout` (seconds, default 30) and `httpClient` (any Guzzle `ClientInterface`) as named arguments.

## API

Responses are returned as associative arrays, with [PHPStan array shapes](https://phpstan.org/writing-php-code/phpdoc-types#array-shapes) so your IDE and static analysis know the fields.

### Drivers

```php
$tracksim->drivers()->add('76561198000000000');      // POST   /v1/drivers/add
$tracksim->drivers()->remove('76561198000000000');   // DELETE /v1/drivers/remove
$tracksim->drivers()->get('76561198000000000');      // GET    /v3/drivers/{steam_id}/details

$tracksim->drivers()->manage('76561198000000000', [  // PATCH  /v3/drivers/{steam_id}/manage
    'eut2_job_logging' => true,
    'eut2_live_tracking' => false,
    'ats_car_job_logging' => true,
]);
```

Only the settings you pass change. The keys are `eut2_job_logging`, `eut2_live_tracking`, `eut2_coach_job_logging`, `eut2_coach_live_tracking`, `ats_job_logging`, `ats_live_tracking`, `ats_car_job_logging` and `ats_car_live_tracking`.

### Jobs

```php
use TrackSim\JobType;

// GET /v2/jobs/{job_id}/route?job_type=truck|car|bus
$points = $tracksim->jobs()->route(12345, JobType::Truck); // [['x' => …, 'z' => …, 'time' => …], …]
$bus = $tracksim->jobs()->route(12345, 'bus');             // adds speed_kmh, satisfaction, peak_*_g
```

A job's route doesn't change once it's recorded, so cache it to stay under the rate limits:

```php
$points = Cache::rememberForever("tracksim.route.{$jobId}", fn () => TrackSim::jobs()->route($jobId, JobType::Truck));
```

### Route Planner (maps)

This needs a paid plan and the Route Planner add-on. It's limited to 60 requests a minute.

```php
['full_url' => $url, 'flag' => $flag] = $tracksim->maps()->create(); // POST   /v3/maps/create
$tracksim->maps()->edit($routeId);                                    // PATCH  /v3/maps/{route_id}/edit
$tracksim->maps()->list();                                            // GET    /v3/maps/routes
$tracksim->maps()->get($routeId);                                     // GET    /v3/maps/{route_id}/route
$tracksim->maps()->delete($routeId);                                  // DELETE /v3/maps/{route_id}/route
```

`flag` is a JWT that expires after 24 hours. Treat it, and `full_url`, as secrets.

### Anything else

If an endpoint isn't wrapped yet, use the low-level helper. It adds auth and error handling:

```php
$data = $tracksim->request('GET', '/v1/me');
$data = $tracksim->request('POST', '/v1/drivers/add', json: ['steam_id' => $steamId]);
```

### Errors

A non-2xx response throws a `TrackSim\Exceptions\ApiException`, or one of its subclasses:

| Status | Exception |
| --- | --- |
| 401 | `AuthenticationException` |
| 403 | `PermissionDeniedException` |
| 404 | `NotFoundException` |
| 429 | `RateLimitException` |

```php
use TrackSim\Exceptions\ApiException;

try {
    $tracksim->drivers()->add($steamId);
} catch (ApiException $e) {
    $e->status; // 403
    $e->error;  // e.g. "steam_profile_private", "max_driver_limit_reached"
    $e->body;   // decoded response body
}
```

TrackSim also uses 403 for invalid parameters and for resources that belong to another company, so check `$e->error`. If the API can't be reached, a `NetworkException` is thrown instead. Every exception in this package extends `TrackSim\Exceptions\TrackSimException`.

## Webhooks

Set up webhooks in [tracksim.app/settings](https://tracksim.app/settings). TrackSim signs each delivery with HMAC-SHA256 using your webhook secret and puts the hex digest in the `tracksim-signature` header.

### In Laravel

Register the included controller in `routes/web.php`:

```php
use TrackSim\Laravel\Http\WebhookController;

Route::post('tracksim/webhook', WebhookController::class);
```

TrackSim can't send a CSRF token, so exclude the route in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['tracksim/*']);
})
```

The controller checks the signature against `TRACKSIM_WEBHOOK_SECRET`. It returns 403 if the signature doesn't match. Otherwise it dispatches a `TrackSim\Laravel\Events\WebhookReceived` event and returns 204. Handle that event with a listener:

```php
use TrackSim\Laravel\Events\WebhookReceived;

class HandleTrackSimWebhook
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->type === 'job.delivered') {
            Log::info('Delivered job', ['id' => $event->data['id']]);
        }
    }
}
```

Make the listener queued (`implements ShouldQueue`) if it does slow work, so TrackSim gets its response quickly.

### Plain PHP

Pass the raw request body. Re-encoding parsed JSON can change the bytes and break the signature.

```php
use TrackSim\Exceptions\SignatureVerificationException;
use TrackSim\Webhook;

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_TRACKSIM_SIGNATURE'] ?? null;

try {
    $event = Webhook::constructEvent($payload, $signature, getenv('TRACKSIM_WEBHOOK_SECRET'));
} catch (SignatureVerificationException) {
    http_response_code(401);
    exit;
}

if ($event->type === 'job.delivered') {
    // $event->data['id'], …
}
http_response_code(200);
```

If you only need the check, `Webhook::verifySignature($payload, $signature, $secret)` returns a `bool`.

Any 2xx response counts as delivered. TrackSim retries a failed delivery up to 3 times (after 5, 10 and 20 minutes).

### Event types

| Job type | Events |
| --- | --- |
| Truck | `job.started`, `job.delivered`, `job.cancelled` |
| Car | `car_job.started`, `car_job.delivered`, `car_job.cancelled` |
| Coach | `bus_job.started`, `bus_job.completed`, `bus_job.cancelled`, `satisfaction_decrease`, `satisfaction_increase` |
| Shared | `fine`, `tollgate`, `ferry`, `train`, `collision`, `repair`, `refuel`, `teleport`, `speeding` |

See the [payload reference](https://docs.tracksim.app/docs/truck-jobs/integrations/webhook-payloads) for full payloads.

## Testing your app

The client takes any Guzzle client, so you can swap in Guzzle's `MockHandler` in your tests:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use TrackSim\TrackSim;

$mock = new MockHandler([new Response(200, [], json_encode(['id' => 1]))]);

$this->app->instance(TrackSim::class, new TrackSim('test', httpClient: new Client(['handler' => $mock])));
```

## Development

```bash
composer install
composer test      # PHPUnit
composer analyse   # PHPStan
composer format    # Laravel Pint
```

Contributions are welcome. See [CONTRIBUTING.md](./CONTRIBUTING.md).

## License

[MIT](./LICENSE)
