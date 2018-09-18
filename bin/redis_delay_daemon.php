<?php
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
	require __DIR__ . '/../vendor/autoload.php';
} elseif (is_file(__DIR__ . '/../../../autoload.php')) {
	require __DIR__ . '/../../../autoload.php';
} else {
	echo 'Cannot find the vendor directory, have you executed composer install?' . PHP_EOL;
	echo 'See https://getcomposer.org to get Composer.' . PHP_EOL;
	exit(1);
}
if (isset($argv)&&array_search( "--help",$argv)||count($argv)==1){
	echo "delay message daemon:\n";
	echo "\t--topic= your delay topic,more topic split use[,]\n";	
	echo "\t--config_dir= config dir\n";
	echo "\t--config= config name\n";	
	exit;
}
function cli_param($name,$defalut=NULL){
	static $param;
	if ($param===NULL){
		global $argv;
		$param=array();
		foreach ($argv as $v){
			$p=strpos($v, "=");
			if ($p!==false&&substr($v, 0,2)=='--'){
				$param[substr($v, 2,$p-2)]=substr($v,$p+1);
			}
		}
	}
	if (isset($param[$name])) return trim($param[$name]);
	return $defalut;
};

$config=cli_param("config_dir",null);
if ($config!=null) LSYS\Config\File::dirs(array($config));

$config=cli_param("config",null);

$topic=cli_param("topic");
$topic=explode(",",$topic);
foreach ($topic as $k=>$v){
    if (empty($v))unset($topic[$k]);
}
if(!count($topic))die("topic is empty");
$redismq=\LSYS\Redis\DI::get()->redis_mq($config);
$redismq->delay_daemon($topic);

