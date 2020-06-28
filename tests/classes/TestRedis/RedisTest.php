<?php
namespace TestEvent;
use PHPUnit\Framework\TestCase;
final class RedisTest extends TestCase
{
    public function testBase()
    {
        $redis=\LSYS\Redis\DI::get()->redis()->configConnect();
        $redis->setex("a",10,"b");
        $this->assertEquals($redis->get("a"), "b");
    }
    public function testConfig()
    {
        $config = new \LSYS\Config\Redis("aaa.bbb");
        $this->assertTrue($config->set("host","sss"));
        $this->assertEquals($config->get("host"),"sss");
        $this->assertTrue(unserialize(serialize($config)) instanceof \LSYS\Config\Redis);
    }
    public function testMQ()
    {
        $redismq=\LSYS\Redis\DI::get()->redisMQ();
        $topic=uniqid("topic");
        $val="dddddddddddddd";
        $redismq->push($topic,$val);
        $ack_key=null;
        $data=$redismq->pop($topic,false,$ack_key);
        $this->assertEquals($data[0], $topic);
        $this->assertEquals($data[1], $val);
        isset($data[0])&&$redismq->ack($data[0], $ack_key,$data);
    }
}