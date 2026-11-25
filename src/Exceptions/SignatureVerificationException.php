<?php

namespace TrackSim\Exceptions;

/** A webhook's `tracksim-signature` header did not match its payload. */
class SignatureVerificationException extends TrackSimException {}
