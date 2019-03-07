<?php
//本程序可用以下命令实现,已封装到 bin
// php bin/redis_delay_daemon.php --topic=aaa,aaa1 --config_dir=./
include __DIR__."/Bootstarp.php";
$redismq=\LSYS\Redis\DI::get()->redisMQ();
$topic=["aaa-------"];
$redismq->delayDaemon($topic);