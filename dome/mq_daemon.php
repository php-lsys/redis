<?php
include __DIR__."/Bootstarp.php";
$redismq=\LSYS\Redis\DI::get()->redis_mq();
$topic=["aaa-------"];
$redismq->delay_daemon($topic);