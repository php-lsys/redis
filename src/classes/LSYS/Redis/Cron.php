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
    public function __construct(Redis $redis,$sleep_queue="lsys_task_sleep"){
        $this->redis=$redis;
        $this->sleep_queue=$sleep_queue;
    }
    /**
     * 重置新加载任务
     */
    public function reload(){
        $this->redis->configConnect();
        $this->un_sleep('1');
    }
    protected function _job_init(){
        $jobs=[];
        foreach ($this->jobs->get_list() as $job){
            $next_run_time=$job->get_next_run_time();
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
    protected function on_sleep($time){
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
    protected function un_sleep($cmd='0'){
        $this->redis->lPush($this->sleep_queue,$cmd);//
        return $this;
    }
    /**
     * 后台监听
     */
    public function daemon(JobList $job_list,MQ $mq=null){
        $this->jobs=$job_list;
        $this->redis->configConnect();
        if (!$this->redis->getoption(Redis::OPT_READ_TIMEOUT)){
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        }
        $mq=$mq?$mq:\LSYS\Redis\DI::get()->redis_mq();
        $bulid_job=true;
        while (true){
            if($bulid_job){
                $this->_job_init();
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
                  //  var_dump($job->get_message());
                    $mq->push($job->get_topic(), $job->get_message());
                    $next_time=$job->get_next_run_time($run_time);
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
            $bulid_job=boolval($this->on_sleep($sleep_time));
        }
    }
}