<?php
use LSYS\Redis\Cron\Timer;

include __DIR__."/Bootstarp.php";
function timer_string($timer){
    return implode(" ", array(
        val_to_str($timer->get_seconds()),val_to_str($timer->get_minutes()),val_to_str($timer->get_month()),
        val_to_str($timer->get_day()),val_to_str($timer->get_month()),val_to_str($timer->get_year()),
        val_to_str($timer->get_week())
    ));
}
function val_to_str($val){
    if ($val===Timer::LOOP) return "*";
    return $val;
}

$times=[];

$timer= new Timer();
$timer->set_seconds(Timer::create_loop(5));//每5秒执行一次
$times[]=timer_string($timer)." task_queue_name message1";

$timer1= new Timer();
$timer1->set_seconds(Timer::create_loop(1));//每1秒执行一次
$times[]=timer_string($timer1)." task_queue_name message2";

file_put_contents("cron.txt",implode("\n", $times));
$redis=\LSYS\Redis\DI::get()->redis();
$cron=new LSYS\Redis\Cron($redis);
$cron->reload();