<?php
include __DIR__."/Bootstarp.php";
$redismq=\LSYS\Redis\DI::get()->redisMQ();
$topic=["aaa-------"];
$redismq->delayDaemon($topic);