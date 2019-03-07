<?php
/**
 * lsys task
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Redis\Cron\JobList;
use LSYS\Redis\Cron\JobList;
use LSYS\Redis\Cron\Job;
class Callback implements JobList{
    protected $_jobs=[];
    protected $_call;
    protected $_finish;
    public function __construct(callable $callback,callable $finish_callback=null){
        $this->_call=$callback;
        $this->_finish=$finish_callback;
    }
    public function finish(Job $job){
        foreach ($this->_jobs as $k=>$v){
            if($job==$v)$this->_jobs[$k];
        }
        if (is_callable($this->_finish))call_user_func($this->_finish,$job);
        return $this;
    }
    public function getList(){
        $this->_jobs=call_user_func($this->_call);
        foreach ($this->_jobs as $k=>$v){
            if (!$v instanceof Job)unset($this->_jobs[$k]);
        }
        $this->_jobs=array_values($this->_jobs);
        return $this->_jobs;
    }
}