<?php
include __DIR__."/Bootstarp.php";
$redismq=\LSYS\Redis\DI::get()->redisMQ();

$topic="aaa-------";
$i=0;
while ($i<100000){
    $i++;
    $redismq->push($topic,uniqid("dddddddddddddd").date("H:i:s"),50);
}