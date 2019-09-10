<?php
/**
 * lsys service
 * 示例配置 未引入
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
return array(
	"default"=>array(//单台REDIS配置
		'host'=>'127.0.0.1',
		'port'=>6379,
		'timeout'=>10,
// 		'db'=>0,
// 		'auth'=>0,
	),
    "cluster"=>array(//redis集群配置
        'cluster'=>true,//声明为集群配置
        'ini'=>NULL,//通过INI文件配置
        'hosts'=>['127.0.0.1:6379'],//直接使用多个地址
    ),
);