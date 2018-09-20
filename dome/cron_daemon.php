<?php
include __DIR__."/Bootstarp.php";
function str_to_val($val){
    if ($val=='*') return LSYS\Redis\Cron\Timer::LOOP;
    if (LSYS\Redis\Cron\Timer::is_loop($val)) return $val;
    return abs($val);
}
$job_list=new LSYS\Redis\Cron\JobList\Callback(function(){
    $jobs=[];
    foreach (file("cron.txt") as $v){
        $v=trim($v);
        if(empty($v)||substr($v, 0,1)=='#')continue;
        $data=explode(" ", $v);
        foreach ($data as $k=>$v){
            $v=trim($v);
            if(empty($v)) unset($data[$k]);
        }
        if (count($data)<=7)continue;
        $timer=new LSYS\Redis\Cron\Timer();
        $timer->set_seconds(str_to_val(array_shift($data)));
        $timer->set_minutes(str_to_val(array_shift($data)));
        $timer->set_hours(str_to_val(array_shift($data)));
        $timer->set_day(str_to_val(array_shift($data)));
        $timer->set_month(str_to_val(array_shift($data)));
        $timer->set_year(str_to_val(array_shift($data)));
        $timer->set_week(str_to_val(array_shift($data)));
        $topic=array_shift($data);
        $message=implode(" ", $data);
        $jobs[]=new LSYS\Redis\Cron\Job($topic, $message,$timer);
    }
    return $jobs;
});
$redis=\LSYS\Redis\DI::get()->redis();
$cron=new LSYS\Redis\Cron($redis);
$cron->daemon($job_list);

