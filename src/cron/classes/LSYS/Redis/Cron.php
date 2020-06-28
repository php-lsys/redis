<?php
/**
 * lsys task
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Redis;
use LSYS\Redis;
use LSYS\Redis\Cron\JobList;
class Cron{
    /**
     * @var Redis
     */
    protected $redis;
    protected $sleep_queue;
    protected $jobs;
    protected $jobs_list;
    /**
     * 任务管理类
     * @param Redis $redis
     */
    public function __construct(Redis $redis,string $sleep_queue="lsys_task_sleep"){
        $this->redis=$redis;
        $this->sleep_queue=$sleep_queue;
    }
    /**
     * 重置新加载任务
     */
    public function reload():void{
        $this->redis->configConnect();
        $this->unSleep('1');
    }
    protected function _jobInit(){
        $jobs=[];
        foreach ($this->jobs->getList() as $job){
            $next_run_time=$job->getNextRunTime();
            if ($next_run_time===false)$next_run_time=time();
            $jobs[]=array($job,$next_run_time);
        }
        $this->jobs_list=$jobs;
    }
    /**
     * 暂停
     * @param int $time
     * @return $this
     */
    protected function onSleep(int $time){
        $time=intval($time);
        $time=$time<=0?0:$time;
        $data=$this->redis->brPop($this->sleep_queue,$time);
        if (isset($data[1]))return $data[1];
        return 0;
    }
    /**
     * 解除暂停
     * @return $this
     */
    protected function unSleep($cmd='0'){
        $this->redis->lPush($this->sleep_queue,$cmd);//
        return $this;
    }
    /**
     * 后台监听
     */
    public function daemon(JobList $job_list,MQ $mq=null):void{
        $this->jobs=$job_list;
        $this->redis->configConnect();
        if (!$this->redis->getoption(Redis::OPT_READ_TIMEOUT)){
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        }
        $mq=$mq?$mq:\LSYS\Redis\DI::get()->redisMQ();
        $bulid_job=true;
        while (true){
            if($bulid_job){
                $this->_jobInit();
            }
            $now_time=time();
            $_times=array();
            /**
             * @var \LSYS\Redis\Cron\Job $job
             */
            foreach ($this->jobs_list as $k=>list($job,$run_time)){
                $offtime=$run_time-$now_time;
                
//                 var_dump($job);
//                 var_dump($run_time);
//                 var_dump($offtime);
                
                if ($offtime<=0){
                  //  var_dump($job->getMessage());
                    $mq->push($job->getTopic(), $job->getMessage());
                    $next_time=$job->getNextRunTime($run_time);
                    if ($next_time===false){
                        $this->jobs->finish($job);
                        unset($this->jobs_list[$k]);
                        continue;
                    }
                    $this->jobs_list[$k][1]=$next_time;
                    $offtime=$next_time-$now_time;
                    if ($offtime<=0)$offtime=0;
                }
                $_times[]=$offtime;
            }
//             var_dump($_times);
            if (count($_times)==0) $sleep_time=0;
            else{
                $sleep_time=intval(min($_times));
                if ($sleep_time<=0){
                    continue;
                }
            }
            $bulid_job=boolval($this->onSleep($sleep_time));
        }
    }
}