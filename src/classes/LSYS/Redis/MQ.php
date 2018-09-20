<?php
namespace LSYS\Redis;
use LSYS\Redis;
//[UUID] 当前数据 和 当前时间+延时 加入有序队列
//触发处理队列
//处理队列接到请求 -> 按最小值弹出 如果小于当前时间 根据UUID得到数据 转发到正常队列 处理下一个 大于当前时间 等待
//确认机制[注意可能会重复消费]
//数据UUID hash=>UUID=>数据 -> 获取 -> 完成 -> 删除
class MQ{
    protected $_redis;
    protected $_uuid_len=36;
    protected $_delay_suffix;
    protected $_wait_suffix;
    protected $_ack_suffix;
    /**
     * 基于REDIS的消息队列
     * @param Redis $redis
     * @param string $delay_suffix 延时队列存储后缀
     * @param string $wait_suffix 延时开关队列存储后缀
     * @param string $ack_suffix 消息队列存储后缀
     */
    public function __construct(Redis $redis,$delay_suffix="_delay",$wait_suffix="_wait",$ack_suffix="_ack"){
        $this->_redis=$redis;
        $this->_ack_suffix=$ack_suffix;
        $this->_wait_suffix=$wait_suffix;
        $this->_delay_suffix=$delay_suffix;
    }
    protected function _delay_list_name($topic){
        if (is_array($topic)){
            $out=array();
            foreach ($topic as $v){
                $out[$v]=$this->_delay_list_name($v);
            }
            return $out;
        }
        return $topic.$this->_delay_suffix;
    }
    protected function _wait_list_name($topic){
        if (is_array($topic)){
            $out=array();
            foreach ($topic as $v){
                $out[$v]=$this->_wait_list_name($v);
            }
            return $out;
        }
        return $topic.$this->_wait_suffix;
    }
    protected function _ack_list_name($topic){
        if (is_array($topic)){
            $out=[];
            foreach ($topic as $k=>$v){
                $out[$v]=$this->_ack_list_name($v);
            }
            return $out;
        }
        return $topic.$this->_ack_suffix;
    }
    protected function _redis(){
        $this->_redis->configConnect();
        return $this->_redis;
    }
    /**
     * 生成36位长度UUID
     * @return string
     */
    protected function _uuid(){
        //3C79079B-98FF-450C-A2F1-D2B4075D7FD1
        if(!extension_loaded('uuid')){
            $charid = strtoupper ( md5 ( uniqid ( rand (), true ) ) ); //根据当前时间（微秒计）生成唯一id.
            $hyphen = chr ( 45 ); // "-"
            $uuid = '' . //chr(123)// "{"
            substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 4 ) . $hyphen . substr ( $charid, 20, 12 );
                //.chr(125);// "}"
            return $uuid;
        }
        return  strtoupper(uuid_create());
    }
    /**
     * 推送消息
     * @param string $topic 队列名
     * @param string $message 消息内容
     * @param number $delay 延时时间,单位:秒
     * @return bool
     */
    public function push($topic,$message,$delay=0){
        $redis=$this->_redis();
        $data=$this->_uuid().$message;
        if ($delay>0){
           // var_dump($this->_delay_list_name($topic),time()+$delay,$data);
            $status=$redis->zAdd($this->_delay_list_name($topic),time()+$delay,$data);
            $redis->lPush($this->_wait_list_name($topic),1);
            return $status;
        }
        return $redis->lPush($topic,$data);
    }
    /**
     * 确认消息
     * @param string $topic 队列名
     * @param string $ack_key 消息KEY(多个相同消息需要被确认时确认时通过此值确认那个消息被确认)
     * @param string $message 消息内容
     * @return bool
     */
    public function ack($topic,$ack_key,$message){
        $ackname=$this->_ack_list_name($topic);
        if (is_array($message)){
            if (!isset($message[1]))return true;
            $message=$message[1];
        }else{
            if (empty($message))return true;
        }
        return $this->_redis->zrem($ackname, $ack_key.$message);
    }
    /**
     * 弹出队列数据 可能会重复弹出
     * 返回跟redis的结果保持一致
     * @param string|array $topic 队列名
     * @param boolean $ack 消息是否自动确认
     * @param string $ack_key 当$ack为false用于确认消息的KEY(解决相同消息同时被取出问题)
     * @param string $ack_timemout 当超过此时间未确认消息将重回队列,因为监控程序挂掉方式不靠谱
     * @param string $timeout 队列等待超时
     * @return array
     */
    public function pop($topic,$ack=true,&$ack_key=null,$ack_timeout=60,$timeout=30){
        assert($ack_timeout>0);//必须大于0,不然会导致消息立即回炉
        $ackname=$this->_ack_list_name($topic);
        $redis=$this->_redis();
        if(!$redis->getOption(Redis::OPT_READ_TIMEOUT)){
            $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        }
        if (is_array($ackname)){
            foreach ($ackname as $_topic=>$_ackname){
                $data=$redis->zRangeByScore($_ackname, 0, time());
                if (is_array($data)){
                   //有超时未处理消息，回炉
                    foreach($data as $v){
                        //加个事务靠谱点
                        $redis->multi();
                        $redis->lPush($_topic,$v);
                        $redis->zRem($_ackname, $v);
                        $redis->exec();
                    }
                }
            }
        }
        
        $message=$redis->brPop($topic,$timeout);
        if (!$ack&&isset($message[1])){
            $_ackname=$this->_ack_list_name($message[0]);
            $ack_key=substr($message[1],0,$this->_uuid_len);
            $redis->zAdd($_ackname,time()+$ack_timeout,$message[1]);
        }
        if(isset($message[1])){
            $message[1]=substr($message[1],$this->_uuid_len);
        }
        return $message;
    }
    /**
     * 延时队列后台处理daemon
     * @param string|array $topic
     */
    public function delay_daemon($topic){
        $waitname_=$waitname=$this->_wait_list_name($topic);
        if (!is_array($waitname))$waitname=[$waitname=>$topic];
        else $waitname=array_flip($waitname);
        if (is_array($waitname_))$waitname_=array_values($waitname_);
        $delayname=$this->_delay_list_name($topic);
        if (!is_array($delayname))$delayname=[$topic=>$delayname];
        $redis=$this->_redis();
        if(!$redis->getOption(Redis::OPT_READ_TIMEOUT)){
            $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        }
        $delayname_=$delayname;
        while (true){
            $wait=true;
          //  var_dump($delayname_);
            foreach ($delayname_ as $topic=>$_delayname){
                $time=time();
                $data=$redis->zRangeByScore($_delayname, 0, $time);
                if (is_array($data))foreach ($data as $v){
                    //加个事务靠谱点
                    $redis->multi();
                    $redis->lPush($topic,$v);
                    $redis->zRem($_delayname, $v);
                    $redis->exec();
                }
                $data=$redis->zRange($_delayname, 0, 0,1);
              //  var_dump($_delayname,$data);
                if (is_array($data))$data=array_shift($data);
                if (!empty($data)){
                    $_wait=intval($data-time());
                    if($wait===true||$wait>=$_wait)$wait=$_wait;
                }
            }
           // var_dump($wait);
            if($wait>0){
                if($wait===true)$wait=0;
                $_=$redis->brPop($waitname_,$wait);//阻塞休眠
              //  var_dump($_);
                if(count($_)==2){
                    list($_topic)=$_;
                    $delayname_=[$waitname[$_topic]=>$this->_delay_list_name($waitname[$_topic])];
                }else $delayname_=$delayname;
            }
        }
    }
}
