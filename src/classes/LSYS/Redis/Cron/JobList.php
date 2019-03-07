<?php
/**
 * lsys task
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Redis\Cron;
interface JobList{
    /**
     * 获取job列表
     * @return Job[]
     */
    public function getList();
    /**
     * 删除指定job
     * @param Job $job
     */
    public function finish(Job $job);
}