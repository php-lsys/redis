<?php
include __DIR__."/Bootstarp.php";
$redismq=\LSYS\Redis\DI::get()->redisMQ();

$topic=["task_queue_name"];
while (true){
    $ack_key=null;
    $data=$redismq->pop($topic,false,$ack_key);
    echo date("H:i:s")."\n";
    var_dump($data);
    isset($data[0])&&$redismq->ack($data[0], $ack_key,$data);
}
