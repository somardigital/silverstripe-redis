<?php

namespace Somar\Redis;

use Predis\Client;
use RuntimeException;
use SilverStripe\Core\Cache\CacheFactory as SilverstripeCacheFactory;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

class CacheFactory implements SilverstripeCacheFactory
{
    protected $client;

    public function __construct()
    {
        if (
            !Environment::getEnv('SS_REDIS_SCHEME') ||
            !Environment::getEnv('SS_REDIS_HOST') ||
            !Environment::getEnv('SS_REDIS_PORT')
        ) {
            throw new RuntimeException(
                'These environment variables are required: SS_REDIS_SCHEME, SS_REDIS_HOST, SS_REDIS_PORT'
            );
        }

        $this->client = new Client([
            'scheme'   => Environment::getEnv('SS_REDIS_SCHEME'),
            'host'     => Environment::getEnv('SS_REDIS_HOST'),
            'port'     => Environment::getEnv('SS_REDIS_PORT'),
            'database' => Environment::getEnv('SS_REDIS_DATABASE'),
        ], [
            'prefix' => Environment::getEnv('SS_REDIS_PREFIX'),
        ]);
    }

    public function create($service, array $params = [])
    {
        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);

        $defaultLifetime = $params['defaultLifetime'] ?? 0;

        $psr6Cache = Injector::inst()->createWithArgs(RedisAdapter::class, [
            $this->client,
            $namespace,
            $defaultLifetime,
        ]);

        return Injector::inst()->createWithArgs(Psr16Cache::class, [$psr6Cache]);
    }
}
