<?php

namespace TrackSim\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use TrackSim\Exceptions\ApiException;
use TrackSim\Exceptions\NetworkException;
use TrackSim\Exceptions\NotFoundException;
use TrackSim\Exceptions\PermissionDeniedException;
use TrackSim\JobType;
use TrackSim\TrackSim;
use ValueError;

class TrackSimTest extends TestCase
{
    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    private function client(Response|\Throwable ...$responses): TrackSim
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new TrackSim('key', baseUrl: 'http://x/', httpClient: new Client(['handler' => $stack]));
    }

    private function json(mixed $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    private function sent(int $i): RequestInterface
    {
        return $this->history[$i]['request'];
    }

    public function test_sends_auth_header_method_path_and_json_body(): void
    {
        $driver = $this->client($this->json(['id' => 1, 'steam_id' => '765']))->drivers()->add('765');

        $this->assertSame(1, $driver['id']);
        $this->assertSame('POST', $this->sent(0)->getMethod());
        $this->assertSame('http://x/v1/drivers/add', (string) $this->sent(0)->getUri());
        $this->assertSame('Api-Key key', $this->sent(0)->getHeaderLine('Authorization'));
        $this->assertSame('tracksim-php/'.TrackSim::VERSION, $this->sent(0)->getHeaderLine('User-Agent'));
        $this->assertSame(['steam_id' => '765'], json_decode((string) $this->sent(0)->getBody(), true));
    }

    public function test_builds_versioned_paths_and_query_strings(): void
    {
        $ts = $this->client($this->json([]), $this->json([]), $this->json([]), new Response(200), new Response(200));

        $ts->jobs()->route(42, 'bus');
        $ts->jobs()->route(42, JobType::Car);
        $ts->drivers()->manage('765', ['ats_live_tracking' => false]);
        $ts->maps()->delete('a/b');
        $ts->drivers()->remove(765);

        $this->assertSame('http://x/v2/jobs/42/route?job_type=bus', (string) $this->sent(0)->getUri());
        $this->assertSame('http://x/v2/jobs/42/route?job_type=car', (string) $this->sent(1)->getUri());
        $this->assertSame('PATCH', $this->sent(2)->getMethod());
        $this->assertSame('http://x/v3/drivers/765/manage', (string) $this->sent(2)->getUri());
        $this->assertSame('{"ats_live_tracking":false}', (string) $this->sent(2)->getBody());
        $this->assertSame('http://x/v3/maps/a%2Fb/route', (string) $this->sent(3)->getUri());
        $this->assertSame('', (string) $this->sent(3)->getBody());
        $this->assertSame('DELETE', $this->sent(4)->getMethod());
        $this->assertSame('{"steam_id":"765"}', (string) $this->sent(4)->getBody());
    }

    public function test_rejects_unknown_job_type(): void
    {
        $this->expectException(ValueError::class);
        $this->client()->jobs()->route(1, 'plane');
    }

    public function test_throws_typed_exception_with_api_error_code(): void
    {
        try {
            $this->client($this->json(['error' => 'job_does_not_exist_in_company'], 404))->jobs()->route(1, 'truck');
            $this->fail('Expected an exception');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->status);
            $this->assertSame(404, $e->getCode());
            $this->assertSame('job_does_not_exist_in_company', $e->error);
            $this->assertSame(['error' => 'job_does_not_exist_in_company'], $e->body);
        }
    }

    public function test_maps_status_codes_to_exceptions(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->client($this->json(['error' => 'Map not found'], 403))->maps()->get('id');
    }

    public function test_non_json_error_body_is_kept_raw(): void
    {
        try {
            $this->client(new Response(500, [], 'oops'))->me();
            $this->fail('Expected an exception');
        } catch (ApiException $e) {
            $this->assertSame(ApiException::class, $e::class);
            $this->assertSame('Internal Server Error', $e->error);
            $this->assertSame('oops', $e->body);
        }
    }

    public function test_wraps_connection_errors(): void
    {
        $this->expectException(NetworkException::class);
        $this->client(new ConnectException('down', new Request('GET', 'http://x')))->me();
    }

    public function test_requires_an_api_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TrackSim(' ');
    }
}
