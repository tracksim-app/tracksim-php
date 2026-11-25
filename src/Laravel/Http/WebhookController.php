<?php

namespace TrackSim\Laravel\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use TrackSim\Exceptions\SignatureVerificationException;
use TrackSim\Laravel\Events\WebhookReceived;
use TrackSim\Webhook;

/** Verifies a TrackSim webhook and dispatches it as a WebhookReceived event. */
class WebhookController
{
    public function __invoke(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->headers->get(Webhook::SIGNATURE_HEADER),
                config('tracksim.webhook_secret'),
            );
        } catch (SignatureVerificationException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        }

        WebhookReceived::dispatch($event->type, $event->data);

        return new Response('', 204);
    }
}
