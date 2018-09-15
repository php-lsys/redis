# 常用服务封装
```php
//默认配置存放:/dome/config 目录
//外部直接使用
$r=\LSYS\Redis\DI::get()->redis()->set("aa","ccc");
print_r($r);
```

其他示例参考:/dome/目录