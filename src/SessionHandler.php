<?php

namespace Somar\Redis;

use RuntimeException;
use Predis\Client;
use Predis\Session\Handler as PredisHandler;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Config\Configurable;

class SessionHandler extends PredisHandler
{
    use Configurable;

    private $prefix = 'PHPSESSION:';

    private static $timeout = 7200;

    public function __construct()
    {
        if (!Environment::getEnv('SS_REDIS_SCHEME') ||
            !Environment::getEnv('SS_REDIS_HOST') ||
            !Environment::getEnv('SS_REDIS_PORT')) {
            throw new RuntimeException(
                'These environment variables are required: SS_REDIS_SCHEME, SS_REDIS_HOST, SS_REDIS_PORT'
            );
        }

        $client = new Client([
            'scheme'   => Environment::getEnv('SS_REDIS_SCHEME'),
            'host'     => Environment::getEnv('SS_REDIS_HOST'),
            'port'     => Environment::getEnv('SS_REDIS_PORT'),
            'database' => Environment::getEnv('SS_REDIS_DATABASE'),
        ], [
            'prefix' => Environment::getEnv('SS_REDIS_PREFIX'),
        ]);

        $timeoutConfig = static::config()->get('timeout');

         if (!empty($timeoutConfig)) {
            static::$timeout = $timeoutConfig;
        }

        parent::__construct($client, [
            'gc_maxlifetime' => static::$timeout,
        ]);

        $this->register();
    }

    public function read($session_id)
    {
        if ($data = $this->client->get($this->getRedisKey($session_id))) {
            return $data;
        }

        return '';
    }

    public function write($session_id, $session_data)
    {
        $this->client->setex($this->getRedisKey($session_id), $this->ttl, $session_data);

        return true;
    }

    public function destroy($session_id)
    {
        $this->client->del($this->getRedisKey($session_id));

        return true;
    }

    private function getRedisKey($session_id)
    {
        return $this->prefix . $session_id;
    }
}
