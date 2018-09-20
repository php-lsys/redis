<?php
namespace LSYS\Config;
use LSYS\DI;
use LSYS\DI\MethodCallback;
/**
 * @method \LSYS\Redis config_redis()
 * @method string config_redis_key()
 */
class RedisDepend extends DI{
    public static function get(){
        if(!self::has())self::set(function(){
            return (new self)
            ->config_redis(new MethodCallback(function(){
                return \LSYS\Redis\DI::get()->redis();
            }))
            ->config_redis_key(new MethodCallback(function(){
                return 'lsys_config';
            }));
        });
        return parent::get();
    }
}