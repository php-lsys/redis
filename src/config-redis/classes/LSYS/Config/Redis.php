<?php
/**
 * lsys config
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Config;
use LSYS\Config;
class Redis implements Config{
	protected $_load;
	/**
	 * @var \LSYS\Redis
	 */
	protected $_redis;
	protected $_key;
	protected $_save;
	protected $_node=array();
	/**
	 * php file config
	 * @param string $name
	 */
	public function __construct ($name,RedisDepend $depend=null){
	    $depend=$depend?$depend:RedisDepend::get();
	    $redis=$depend->configRedis();
	    $redis_key=$depend->configRedisKey();
	    $this->_name=$name;
	    $this->_redis=$redis->configConnect();
	    $this->_save=$redis_key;
		$this->_load=false;
		$names=explode(".",$name);
		$this->_key=array_shift($names);
		$data=$this->_redis->hGet($this->_save,$this->_key);
		$data&&$data=@json_decode($data,true);
		if (is_array($data)){
    		$this->_load=true;
    		$node=$data;
    		for ($i=0;$i<count($names);$i++){
    		    if(isset($node[$names[$i]])){
    		        $node=$node[$names[$i]];
    			}else{
    				$this->_load=false;
    				$node=array();
    				break;
    			}
    		}
    		$this->_node=$node;
		}
		$this->_names=$names;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::loaded()
	 */
	public function loaded(){
		return $this->_load;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::name()
	 */
	public function name(){
		return $this->_name;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::get()
	 */
	public function get($key,$default=NULL){
		$group= explode('.', $key);
		$t=$this->_node;
		while (count($group)){
			$node=array_shift($group);
			if(isset($t[$node])){
				$t=&$t[$node];
			}else return $default;
		}
		return $t;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::get()
	 */
	public function exist($key){
		$group= explode('.', $key);
		$t=$this->_node;
		while (count($group)){
			$node=array_shift($group);
			if(isset($t[$node])){
				$t=&$t[$node];
			}else return false;
		}
		return true;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::asArray()
	 */
	public function asArray(){
		return $this->_node;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::set()
	 */
	public function set($key,$value = NULL){
		$keys=explode(".",$key);
		$config=&$this->_node;
		foreach ($keys as $v){
			if(!isset($config[$v]))$config[$v]=array();
			$config=&$config[$v];
		}
		if ($config!=$value){
			$config=$value;
		}
		$data=$this->_redis->hGet($this->_save,$this->_key);
		$data&&$data=@json_decode($data,true);
		if (!is_array($data))$data=[];
		$_data=&$data;
		for ($i=0;$i<count($this->_names);$i++){
		    if (!isset($data[$this->_names[$i]]))$data[$this->_names[$i]]=[];
		    $data=&$data[$this->_names[$i]];
		}
		$data=$this->_node;
		$this->_load=true;
		return $this->_redis->hset($this->_save,$this->_key,json_encode($_data))!==false;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::readonly()
	 */
	public function readonly (){
		return false;
	}
}
