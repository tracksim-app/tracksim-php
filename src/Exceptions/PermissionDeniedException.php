<?php

namespace TrackSim\Exceptions;

/** HTTP 403. TrackSim also uses 403 for invalid parameters and other companies' resources, so check `$error`. */
class PermissionDeniedException extends ApiException {}
