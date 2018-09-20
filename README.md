# redis 服务封装

> 已实现基于redis延时队列和消息确认 参考:dome/mq_* 等文件
> 已实现定时消息队列(可实现类似crontab的功能) 参考:dome/cron_* 等文件


基本使用:
```php
//默认配置存放:/dome/config 目录
//外部直接使用
$r=\LSYS\Redis\DI::get()->redis()->set("aa","ccc");
print_r($r);
```