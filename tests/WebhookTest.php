<?php

namespace TrackSim\Tests;

use PHPUnit\Framework\TestCase;
use TrackSim\Exceptions\SignatureVerificationException;
use TrackSim\Webhook;

class WebhookTest extends TestCase
{
    // Matches what TrackSim sends: Python's json.dumps() with its default separators.
    private const BODY = '{"object": "event", "type": "job.delivered", "data": {"id": 1}}';

    public function test_verifies_signature(): void
    {
        $sig = hash_hmac('sha256', self::BODY, 'secret');

        $this->assertTrue(Webhook::verifySignature(self::BODY, $sig, 'secret'));
        $this->assertTrue(Webhook::verifySignature(self::BODY, ' '.strtoupper($sig).' ', 'secret'));
        $this->assertFalse(Webhook::verifySignature(self::BODY, $sig, 'wrong'));
        $this->assertFalse(Webhook::verifySignature(self::BODY.' ', $sig, 'secret'));
        $this->assertFalse(Webhook::verifySignature(self::BODY, null, 'secret'));
        $this->assertFalse(Webhook::verifySignature(self::BODY, $sig, ''));
        $this->assertFalse(Webhook::verifySignature(self::BODY, $sig, null));
    }

    public function test_constructs_event(): void
    {
        $event = Webhook::constructEvent(self::BODY, hash_hmac('sha256', self::BODY, 'secret'), 'secret');

        $this->assertSame('job.delivered', $event->type);
        $this->assertSame(['id' => 1], $event->data);
        $this->assertSame('event', $event->object);
    }

    public function test_rejects_bad_signature(): void
    {
        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent(self::BODY, 'bad', 'secret');
    }
}
