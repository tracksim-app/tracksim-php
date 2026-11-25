<?php

namespace TrackSim\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use TrackSim\Laravel\Events\WebhookReceived;
use TrackSim\Laravel\Facades\TrackSim as TrackSimFacade;
use TrackSim\Laravel\Http\WebhookController;
use TrackSim\Laravel\TrackSimServiceProvider;
use TrackSim\Resources\Drivers;
use TrackSim\TrackSim;

class LaravelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [TrackSimServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('tracksim.api_key', 'key');
        $app['config']->set('tracksim.webhook_secret', 'secret');
    }

    protected function defineRoutes($router): void
    {
        Route::post('tracksim/webhook', WebhookController::class);
    }

    public function test_resolves_client_from_container_and_facade(): void
    {
        $this->assertSame($this->app->make(TrackSim::class), $this->app->make('tracksim'));
        $this->assertInstanceOf(Drivers::class, TrackSimFacade::drivers());
    }

    public function test_missing_api_key_has_a_helpful_error(): void
    {
        config(['tracksim.api_key' => null]);
        $this->app->forgetInstance(TrackSim::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TRACKSIM_API_KEY');
        $this->app->make(TrackSim::class);
    }

    public function test_webhook_controller_dispatches_event(): void
    {
        Event::fake();
        $body = '{"object": "event", "type": "fine", "data": {"id": 7}}';

        $this->call('POST', 'tracksim/webhook', [], [], [], [
            'HTTP_TRACKSIM_SIGNATURE' => hash_hmac('sha256', $body, 'secret'),
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertNoContent();

        Event::assertDispatched(WebhookReceived::class, fn (WebhookReceived $e) => $e->type === 'fine' && $e->data === ['id' => 7]);
    }

    public function test_webhook_controller_rejects_bad_signature(): void
    {
        Event::fake();

        $this->call('POST', 'tracksim/webhook', [], [], [], ['HTTP_TRACKSIM_SIGNATURE' => 'bad'], '{}')->assertForbidden();

        Event::assertNotDispatched(WebhookReceived::class);
    }
}
