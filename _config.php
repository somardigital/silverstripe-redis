<?php

use Somar\Redis\SessionHandler;
use SilverStripe\Core\Environment;

// Redis session handler for handling sessions behind load balancer.
if (
    Environment::getEnv('SS_REDIS_SCHEME')
    && Environment::getEnv('SS_REDIS_HOST')
    && Environment::getEnv('SS_REDIS_PORT')
) {
    $sessionHandler = new SessionHandler();
}
