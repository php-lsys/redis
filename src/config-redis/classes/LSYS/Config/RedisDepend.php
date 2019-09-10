<?php
namespace LSYS\Config;
use LSYS\DI;
use LSYS\DI\MethodCallback;
/**
 * @method \LSYS\Redis configRedis()
 * @method string configRedisKey()
 */
class RedisDepend extends DI{
    public static function get(){
        if(!self::has())self::set(function(){
            return (new self)
            ->configRedis(new MethodCallback(function(){
                return \LSYS\Redis\DI::get()->redis();
            }))
            ->configRedisKey(new MethodCallback(function(){
                return 'lsys_config';
            }));
        });
        return parent::get();
    }
}