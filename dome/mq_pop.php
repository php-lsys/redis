<?php
include __DIR__."/Bootstarp.php";
$redismq=\LSYS\Redis\DI::get()->redisMQ();

$topic=["aaa-------"];
while (true){
    $data=$redismq->pop($topic,false,$ack_key);
    var_dump($data);
    isset($data[0])&&$redismq->ack($data[0], $ack_key,$data);
}
