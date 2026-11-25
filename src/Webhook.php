<?php

namespace TrackSim;

use JsonException;
use TrackSim\Exceptions\SignatureVerificationException;
use TrackSim\Exceptions\TrackSimException;

final class Webhook
{
    public const SIGNATURE_HEADER = 'tracksim-signature';

    /**
     * Check the `tracksim-signature` header: a hex HMAC-SHA256 of the body using your webhook secret.
     * Pass the raw request body exactly as received, not re-encoded JSON.
     */
    public static function verifySignature(string $payload, ?string $signature, ?string $secret): bool
    {
        if ($signature === null || $signature === '' || $secret === null || $secret === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), strtolower(trim($signature)));
    }

    /**
     * Verify the signature and decode the event.
     *
     * @throws SignatureVerificationException
     */
    public static function constructEvent(string $payload, ?string $signature, ?string $secret): WebhookEvent
    {
        if (! self::verifySignature($payload, $signature, $secret)) {
            throw new SignatureVerificationException('Invalid TrackSim webhook signature.');
        }

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TrackSimException('TrackSim webhook payload is not valid JSON.', 0, $e);
        }

        return new WebhookEvent(
            type: (string) ($data['type'] ?? ''),
            data: (array) ($data['data'] ?? []),
            object: (string) ($data['object'] ?? 'event'),
        );
    }
}
