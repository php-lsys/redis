<?php
use LSYS\Redis\Cron\Timer;

include __DIR__."/Bootstarp.php";
function timer_string($timer){
    return implode(" ", array(
        val_to_str($timer->getSeconds()),val_to_str($timer->getMinutes()),val_to_str($timer->getMonth()),
        val_to_str($timer->getDay()),val_to_str($timer->getMonth()),val_to_str($timer->getYear()),
        val_to_str($timer->getWeek())
    ));
}
function val_to_str($val){
    if ($val===Timer::LOOP) return "*";
    return $val;
}

$times=[];

$timer= new Timer();
$timer->setSeconds(Timer::createLoop(5));//每5秒执行一次
$times[]=timer_string($timer)." task_queue_name message1";

$timer1= new Timer();
$timer1->setSeconds(Timer::createLoop(1));//每1秒执行一次
$times[]=timer_string($timer1)." task_queue_name message2";

file_put_contents("cron.txt",implode("\n", $times));
$redis=\LSYS\Redis\DI::get()->redis();
$cron=new LSYS\Redis\Cron($redis);
$cron->reload();