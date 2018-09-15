<?php
include __DIR__."/Bootstarp.php";


$redismq=\LSYS\Redis\DI::get()->redis_mq();

//生成消息
$topic="aaa-------";
$redismq->push($topic,"dddddddddddddd");
exit;

//外部直接使用
$r=LSYS\Redis\DI::get()->redis()->configConnect();
var_dump($r->set("aaa","bbb"));
var_dump($r->get("aaa"));
//使用全局的redis对象
$r=LSYS\Redis\DI::get()->redis("redis.testconfig");
print_r($r);
