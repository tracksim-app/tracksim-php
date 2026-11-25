<?php

namespace TrackSim\Exceptions;

/** HTTP 429: too many requests. Wait before retrying. */
class RateLimitException extends ApiException {}
