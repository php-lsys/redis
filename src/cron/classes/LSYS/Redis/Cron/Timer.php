<?php
/**
 * lsys task
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Redis\Cron;
use LSYS\Exception;

class Timer{
	const LOOP=TRUE;
	protected $hours;
	protected $minutes;
	protected $seconds;
	protected $day;
	protected $month;
	protected $year;
	protected $week;
	/**
	 * 任务时间,不做任何设置,按每分钟执行一次
	 */
	public function __construct(){
		$this->hours=self::LOOP;
		$this->minutes=self::LOOP;
		$this->seconds=0;
		$this->day=self::LOOP;
		$this->month=self::LOOP;
		$this->year=self::LOOP;
		$this->week=self::LOOP;
	}
	//循环标记
	private static $P='/';
	/**
	 * 创建定时循环
	 * 设置循环值,比循环值小的单位必须为固定值
	 * 比循环值大的单位作废,如:设置日循环,月跟年的设置作废
	 * @param int $var
	 * @return string
	 */
	public static function createLoop($var){
		return self::$P.intval($var);
	}
	/**
	 * 解析一个定时循环,返回具体值
	 * @param string $var
	 * @return boolean|number
	 */
	public static function parseLoop($var){
		if (!self::isLoop($var)) return false;
		return intval(substr($var, 1));
	}
	/**
	 * 指定值是否是定时循环值
	 * @param string $var
	 * @return boolean
	 */
	public static function isLoop($var){
		if ($var===self::LOOP) return false;
		if (is_array($var)) return false;
		return self::$P==substr($var,0,1);
	}
	/**
	 * 指定值是否是单一固定值
	 * @param mixed $var
	 * @return boolean
	 */
	public static function isFix($var){
		if ($var===self::LOOP) return false;
		return !is_array($var);
	}
	/**
	 * 通过时间戳设置运行时间
	 * 必须大于当前时间
	 * @param int $time
	 * @return Timer
	 */
	public function setTime($time){
		$time=$time<time()?time():$time;
		$this->setWeek(self::LOOP);
		$this->setYear(date("Y",$time))
			->setMonth(date("n",$time))
			->setDay(date("j",$time))
			->setHours(date("G",$time))
			->setMinutes(intval(date("i",$time)))
			->setSeconds(intval(date("s",$time)));
		return $this;
	}
	/**
	 * 过滤设置变量
	 * @param mixed $vars
	 * @param int $start
	 * @param int $end
	 * @param bool $allow_loop 
	 * @return mixed
	 */
	protected function _limitSet($vars,$start,$end,$allow_loop=false){
		if ($vars===self::LOOP)return $vars;
		else{
			if (is_array($vars)){
				foreach ($vars as &$v){
					$v=intval($v);
					$v=$v>$end?$start:$v;
					$v=$v<$start?$start:$v;
				}
				if (count($vars)==0) throw new Exception("not support set empty data");
				if (count($vars)>1){
					sort($vars,SORT_NUMERIC);
					return array_unique($vars);
				}else $vars=array_pop($vars);
			}
			if($allow_loop&&self::isLoop($vars)){
				$vars=str_replace(self::$P, '', $vars);
				$vars=intval($vars);
				return self::$P.$vars;
			}
			$vars=intval($vars);
			$vars=$vars>$end?$start:$vars;
			$vars=$vars<$start?$start:$vars;
			return $vars;
		}
	}
	/**
	 * 设定运行 时
	 * @param mixed $hours
	 * @return $this
	 */
	public function setHours($hours){
		$this->hours=$this->_limitSet($hours, 0, 23,true);
		return $this;
	}
	/**
	 * 设定运行 秒
	 * @param mixed $seconds
	 * @return $this
	 */
	public function setSeconds($seconds){
		$this->seconds=$this->_limitSet($seconds, 0, 59,true);
		return $this;
	}
	/**
	 * 设定运行 分
	 * @param mixed $minutes
	 * @return $this
	 */
	public function setMinutes($minutes){
		$this->minutes=$this->_limitSet($minutes, 0, 59,true);
		return $this;
	}
	/**
	 * 设定运行 天 如果只有2月不允许出现30 31
	 * @param mixed $day
	 * @return $this
	 */
	public function setDay($day){
		$this->day=$this->_limitSet($day, 1, 31,true);
		$this->_checkDay();
		return $this;
	}
	protected function _checkDay(){
		if (!is_array($this->month)&&intval($this->month)===2){
			$day=is_array($this->day)?$this->day:array($this->day);
			//当只有2月时不能设置 30或31
			if (in_array(30, $day)||in_array(31, $day)) throw new Exception("2 month not support day 30 or 31");
		}
	}
	/**
	 * 设定运行 月 如果只有2月不允许出现30 31
	 * @param mixed $day
	 * @return $this
	 */
	public function setMonth($month){
		$this->month=$this->_limitSet($month, 1, 12,true);
		$this->_checkDay();
		return $this;
	}
	/**
	 * 设定运行 年 如果只有2月不允许出现30 31
	 * @param mixed $day
	 * @return $this
	 */
	public function setYear($year){
		$this->year=$this->_limitSet($year, date('Y'), 9999,true);
		return $this;
	}
	/**
	 * 设定运行 星期 如果设置此值,将导致 日 月设置无效,任务按星期循环
	 * @param mixed $day
	 * @return $this
	 */
	public function setWeek($week){
		$this->week=$this->_limitSet($week, 0, 6);
		return $this;
	}
	/**
	 * 取得 时
	 * @return mixed
	 */
	public function getHours(){
		return $this->hours;
	}
	/**
	 * 取得 秒
	 * @return mixed
	 */
	public function getSeconds(){
		return $this->seconds;
	}
	/**
	 * 取得 分
	 * @return mixed
	 */
	public function getMinutes(){
		return $this->minutes;
	}
	/**
	 * 取得 日
	 * @return mixed
	 */
	public function getDay(){
		return $this->day;
	}
	/**
	 * 取得 月
	 * @return mixed
	 */
	public function getMonth(){
		return $this->month;
	}
	/**
	 * 取得 年
	 * @return mixed
	 */
	public function getYear(){
		return $this->year;
	}
	/**
	 * 取得 星期
	 * @return mixed
	 */
	public function getWeek(){
		return $this->week;
	}
}