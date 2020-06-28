<?php
include __DIR__."/Bootstarp.php";
//得到一个配置对象
$config = new LSYS\Config\Redis("aaa.bbb");
var_dump($config->set("host","sss"));
// var_dump($config->get("host","sss"));
var_dump(unserialize(serialize($config))->asArray());



