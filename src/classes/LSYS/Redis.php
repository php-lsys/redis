<?php
/**
 * lsys redis
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS;
/**
 * 以下为 Redis 和 RedisCluster 都支持的方法
 * @method mixed close();
 * @method mixed get();
 * @method mixed set();
 * @method mixed mget();
 * @method mixed mset();
 * @method mixed msetnx();
 * @method mixed del();
 * @method mixed setex();
 * @method mixed psetex();
 * @method mixed setnx();
 * @method mixed getset();
 * @method mixed exists();
 * @method mixed keys();
 * @method mixed type();
 * @method mixed lpop();
 * @method mixed rpop();
 * @method mixed lset();
 * @method mixed spop();
 * @method mixed lpush();
 * @method mixed rpush();
 * @method mixed blpop();
 * @method mixed brpop();
 * @method mixed rpushx();
 * @method mixed lpushx();
 * @method mixed linsert();
 * @method mixed lindex();
 * @method mixed lrem();
 * @method mixed brpoplpush();
 * @method mixed rpoplpush();
 * @method mixed llen();
 * @method mixed scard();
 * @method mixed smembers();
 * @method mixed sismember();
 * @method mixed sadd();
 * @method mixed saddarray();
 * @method mixed srem();
 * @method mixed sunion();
 * @method mixed sunionstore();
 * @method mixed sinter();
 * @method mixed sinterstore();
 * @method mixed sdiff();
 * @method mixed sdiffstore();
 * @method mixed srandmember();
 * @method mixed strlen();
 * @method mixed persist();
 * @method mixed ttl();
 * @method mixed pttl();
 * @method mixed zcard();
 * @method mixed zcount();
 * @method mixed zremrangebyscore();
 * @method mixed zscore();
 * @method mixed zadd();
 * @method mixed zincrby();
 * @method mixed hlen();
 * @method mixed hkeys();
 * @method mixed hvals();
 * @method mixed hget();
 * @method mixed hgetall();
 * @method mixed hexists();
 * @method mixed hincrby();
 * @method mixed hset();
 * @method mixed hsetnx();
 * @method mixed hmget();
 * @method mixed hmset();
 * @method mixed hdel();
 * @method mixed hincrbyfloat();
 * @method mixed hstrlen();
 * @method mixed dump();
 * @method mixed zrank();
 * @method mixed zrevrank();
 * @method mixed incr();
 * @method mixed decr();
 * @method mixed incrby();
 * @method mixed decrby();
 * @method mixed incrbyfloat();
 * @method mixed expire();
 * @method mixed pexpire();
 * @method mixed expireat();
 * @method mixed pexpireat();
 * @method mixed append();
 * @method mixed getbit();
 * @method mixed setbit();
 * @method mixed bitop();
 * @method mixed bitpos();
 * @method mixed bitcount();
 * @method mixed lget();
 * @method mixed getrange();
 * @method mixed ltrim();
 * @method mixed lrange();
 * @method mixed zremrangebyrank();
 * @method mixed publish();
 * @method mixed rename();
 * @method mixed renamenx();
 * @method mixed pfcount();
 * @method mixed pfadd();
 * @method mixed pfmerge();
 * @method mixed setrange();
 * @method mixed restore();
 * @method mixed smove();
 * @method mixed zrange();
 * @method mixed zrevrange();
 * @method mixed zrangebyscore();
 * @method mixed zrevrangebyscore();
 * @method mixed zrangebylex();
 * @method mixed zrevrangebylex();
 * @method mixed zlexcount();
 * @method mixed zremrangebylex();
 * @method mixed zunionstore();
 * @method mixed zinterstore();
 * @method mixed zrem();
 * @method mixed sort();
 * @method mixed object();
 * @method mixed subscribe();
 * @method mixed psubscribe();
 * @method mixed unsubscribe();
 * @method mixed punsubscribe();
 * @method mixed eval();
 * @method mixed evalsha();
 * @method mixed scan();
 * @method mixed sscan();
 * @method mixed zscan();
 * @method mixed hscan();
 * @method mixed getmode();
 * @method mixed getlasterror();
 * @method mixed clearlasterror();
 * @method mixed getoption();
 * @method mixed setoption();
 * @method mixed _prefix();
 * @method mixed _serialize();
 * @method mixed _unserialize();
 * @method mixed multi();
 * @method mixed exec();
 * @method mixed discard();
 * @method mixed watch();
 * @method mixed unwatch();
 * @method mixed save();
 * @method mixed bgsave();
 * @method mixed flushdb();
 * @method mixed flushall();
 * @method mixed dbsize();
 * @method mixed bgrewriteaof();
 * @method mixed lastsave();
 * @method mixed info();
 * @method mixed role();
 * @method mixed time();
 * @method mixed randomkey();
 * @method mixed ping();
 * @method mixed echo();
 * @method mixed command();
 * @method mixed rawcommand();
 * @method mixed client();
 * @method mixed config();
 * @method mixed pubsub();
 * @method mixed script();
 * @method mixed slowlog();
 * @method mixed geoadd();
 * @method mixed geohash();
 * @method mixed geopos();
 * @method mixed geodist();
 * @method mixed georadius();
 * @method mixed georadiusbymember();  
 */
class Redis implements \Serializable{
    const REDIS_NOT_FOUND = 0;
    const REDIS_STRING = 1;
    const REDIS_SET = 2;
    const REDIS_LIST = 3;
    const REDIS_ZSET = 4;
    const REDIS_HASH = 5;
    const ATOMIC = 0;
    const MULTI = 1;
    const OPT_SERIALIZER = 1;
    const OPT_PREFIX = 2;
    const OPT_READ_TIMEOUT = 3;
    const SERIALIZER_NONE = 0;
    const SERIALIZER_PHP = 1;
    const OPT_SCAN = 4;
    const SCAN_RETRY = 1;
    const SCAN_NORETRY = 0;
    const AFTER = "after";
    const BEFORE = "before";
    /**
     * @var \LSYS\Config
     */
    protected $_config;
    protected $_redis;
    /**
     * @param \LSYS\Config $config
     * @throws Exception
     */
    public function __construct (Config $config){
        $this->_config=$config;
    }
    /**
     * @throws Exception
     * @return \LSYS\Redis
     */
    public function configConnect(){
        $_config=$this->_config->as_array()+array(
            'cluster'             	=> false,
        );
        if (!$_config['cluster'])$this->_connect_redis();
        else $this->_connect_redis_cluster();
        return $this;
    }
    /**
     * 返回当前使用的redis对象或RedisCluster对象
     * @return \RedisCluster|\Redis
     */
    public function __invoke(){
        return $this->_redis;
    }
    protected function _connect_redis(){
        if ($this->_redis&&$this->_redis->isConnected())return $this;
        if (!$this->_redis) $this->_redis=new \Redis();
        $_config=$this->_config->as_array()+array(
            'host'             	=> 'localhost',
            'port'             	=> 6379,
            'timeout'			=> '60',
            'db'				=> NULL,
        );
        try{
            @$this->_redis->connect($_config['host'],$_config['port'],$_config['timeout']);
            if (isset($_config['auth']))$this->_redis->auth($_config['auth']);
            if (isset($_config['db']))$this->_redis->select($_config['db']);
        }catch (\Exception $e){
            throw new Exception($e->getMessage().strtr(" [Host:host Port:port]",array("host"=>$_config['host'],"port"=>$_config['port'])),$e->getCode());
        }
    }
    protected function _connect_redis_cluster(){
        if ($this->_redis)return $this;
        $_config=$this->_config->as_array()+array(
            'ini'             	=> NULL,
            'hosts'             => [],
        );
        $this->_redis = new \RedisCluster($_config['host'],$_config['hosts']);
    }
    public function serialize (){
	    if (!$this->_config instanceof \Serializable){
	        throw new Exception("your redis config can't be serializable");
	    }
	    return serialize($this->_config);
	}
	public function unserialize ($serialized){
	    $this->__construct(unserialize($serialized));
	}
	public function __call($method,$args){
	    if(!$this->_redis)throw new Exception("your redis not connect,plase call configConnect");
	    return call_user_func_array([$this->_redis,$method],$args);
	}
}