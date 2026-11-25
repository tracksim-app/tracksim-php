<?php

namespace TrackSim\Exceptions;

/** The TrackSim API returned a non-2xx response. */
class ApiException extends TrackSimException
{
    /**
     * @param  int  $status  HTTP status code (also available as getCode()).
     * @param  string  $error  Error code from the API, e.g. `job_does_not_exist_in_company`.
     * @param  mixed  $body  Decoded response body, or the raw text if it wasn't JSON.
     */
    public function __construct(
        public readonly int $status,
        public readonly string $error,
        public readonly mixed $body = null,
    ) {
        parent::__construct("TrackSim API error {$status}: {$error}", $status);
    }
}
