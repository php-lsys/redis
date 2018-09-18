<?php
namespace LSYS\Redis;
/**
 * @method \LSYS\Redis redis($config=null)
 * @method \LSYS\Redis\MQ redis_mq()
 */
class DI extends \LSYS\DI{
    /**
     *
     * @var string default config
     */
    public static $config = 'redis.default';
    /**
     * @return static
     */
    public static function get(){
        $di=parent::get();
        !isset($di->redis)&&$di->redis(new \LSYS\DI\ShareCallback(function($config=null){
            return $config?$config:self::$config;
        },function($config=null){
            $config=\LSYS\Config\DI::get()->config($config?$config:self::$config);
            return new \LSYS\Redis($config);
        }));
        !isset($di->redis_mq)&&$di->redis_mq(
			new \LSYS\DI\ShareCallback(function($config=null){
				return $config?$config:self::$config;
			},function($config=null)use($di){
				$config=\LSYS\Config\DI::get()->config($config?$config:self::$config);
				return new \LSYS\Redis\MQ($di->redis($config));
			})
		);
        return $di;
    }
}