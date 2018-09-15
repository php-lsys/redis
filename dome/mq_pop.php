<?php
include __DIR__."/Bootstarp.php";
$redismq=\LSYS\Redis\DI::get()->redis_mq();

$topic="aaa-------";
while (true){
    $data=$redismq->pop([$topic],false,$ack_key);
    var_dump($data);
    $redismq->ack($topic, $ack_key,$data);
}
