<?php
/**
 * lsys task
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Redis\Cron;
class Job{
	protected $topic;
	protected $message;
	protected $timer;
	public function __construct(string $topic,$message,Timer $timer=null){
		$this->topic=$topic;
		$this->message=$message;
		if ($timer)$this->setTimer($timer);
	}
	/**
	 * @return string
	 */
	public function getTopic():string{
		return $this->topic;
	}
	public function getMessage(){
		return $this->message;
	}
	/**
	 * 设置运行时间对象
	 * @param Timer $timer
	 * @return $this
	 */
	public function setTimer(Timer $timer){
		$this->timer=$timer;
		return $this;
	}
	/**
	 * 返回运行时间对象
	 * @return Timer
	 */
	public function getTimer(){
		if ($this->timer==null)$this->timer=new Timer();
		return $this->timer;
	}
	/**
	 * 计算下一次执行的时间
	 * @return int
	 */
	public function getNextRunTime($now_time=null){
		$timer=$this->getTimer();
		if ($now_time==null)$now_time=time();
		
		$h=$timer->getHours();
		$i=$timer->getMinutes();
		$s=$timer->getSeconds();
		$d=$timer->getDay();
		$m=$timer->getMonth();
		$y=$timer->getYear();
		$w=$timer->getWeek();

		$nh=date("G",$now_time);
		$ni=intval(date("i",$now_time));
		$ns=intval(date("s",$now_time));
		$ny=date("Y",$now_time);
		$nm=date("n",$now_time);
		$nd=date("j",$now_time);
		$nw=date("w",$now_time);

		//定量循环
		if(Timer::isLoop($y)){
			$loop_val=Timer::parseLoop($y);
			$_m=Timer::isFix($m)?$m:'01';
			$_d=Timer::isFix($d)?$d:'01';
			$_h=Timer::isFix($h)?$h:'00';
			$_i=Timer::isFix($i)?$i:'00';
			$_s=Timer::isFix($s)?$s:'00';
			return strtotime(date("Y",strtotime("+ {$loop_val} year",$now_time))."-{$_m}-{$_d} {$_h}:{$_i}:{$_s}");
		}
		if(Timer::isLoop($m)){
			$loop_val=Timer::parseLoop($m);
			$_d=Timer::isFix($d)?$d:'01';
			$_h=Timer::isFix($h)?$h:'00';
			$_i=Timer::isFix($i)?$i:'00';
			$_s=Timer::isFix($s)?$s:'00';
			return strtotime(date("Y-m",strtotime("+ {$loop_val} months",$now_time))."-{$_d} {$_h}:{$_i}:{$_s}");
		}
		if(Timer::isLoop($d)){
			$loop_val=Timer::parseLoop($d);
			$_h=Timer::isFix($h)?$h:'00';
			$_i=Timer::isFix($i)?$i:'00';
			$_s=Timer::isFix($s)?$s:'00';
			return strtotime(date("Y-m-d",strtotime("+ {$loop_val} day",$now_time))." {$_h}:{$_i}:{$_s}");
		}
		if(Timer::isLoop($h)){
			$loop_val=Timer::parseLoop($h);
			$_i=Timer::isFix($i)?$i:'00';
			$_s=Timer::isFix($s)?$s:'00';
			return strtotime(date("Y-m-d H",strtotime("+ {$loop_val} hours",$now_time)).":{$_i}:{$_s}");
		}
		if(Timer::isLoop($i)){
			$loop_val=Timer::parseLoop($i);
			$_s=Timer::isFix($s)?$s:'00';
			return strtotime(date("Y-m-d H:i",strtotime("+ {$loop_val} minute",$now_time)).":{$_s}");
		}
		if(Timer::isLoop($s)){
			$loop_val=Timer::parseLoop($s);
			return strtotime("+ {$loop_val} seconds",$now_time);
		}
		
		//指定日期及循环
		//* * * * * *
		//s i G j n Y
		//2016 11 11 22 22 22
		//8-31 9-31 9-30
		list($n,$_s)=$this->_opFind($s, $ns,0, 59,false);
		list($n,$_i)=$this->_opFind($i, $ni,0, 59,!$n);
		list($n,$_h)=$this->_opFind($h, $nh,0, 23,!$n);//

		if ($w!==Timer::LOOP){//以星期循环
			list($n,$next)=$this->_loopFind($w, $nw, 0, 6,!$n);
			if ($n==true){
				$nt=strtotime("+ {$next} day",$now_time);
				$_y=date("Y",strtotime("+ {$next} day",$now_time));
				$__y=$this->_valFind($y, $_y);
				if ($__y!=$_y&&$y!==Timer::LOOP){//跨过年
					$_y=$__y;
					if ($_y===false) return false;//没下一年
					$_m=1;
					$_sw=date("w",strtotime(date("{$_y}-01-01 0:0:1")));//第一日星期
					$w=is_array($w)?$w:array($w);
					$_w=min($w);//最小星期
					if ($_sw>$_w){
						$_d=6-$_sw+$w+2;//6-4+2 =4//4
					}else $_d=$_w-$_sw+1;
				}else{
					$_m=date("n",$nt);
					$_d=date("d",$nt);
				}
			}
		}else{//以月日循环
			list($n,$_d)=$this->_opFind($d, $nd,1, date("t",$now_time),!$n);
			$year=0;
			$maxm=max(is_array($m)?$m:array($m));
			$x=($n==true||$nm>$maxm)?false:true;
			while (true){
				//$m 规则 $nm 当前月份 8-31 9
				list($_n,$_m)=$this->_opFind($m, $nm,1,12,$x);
				if ($_n)$year++;
				if ($_d>$this->_methodDay($_m,date("L",strtotime(($ny+$year)."-{$_m}-01 0:00:01")))){
					$x=false;
					$nm=$_m;
				}else break;
			}
			
			$_y=$ny+$year;
				
			if ($y!==Timer::LOOP){
				$bad=true;
				$y=is_array($y)?$y:array($y);
				$maxy=max($y);
				$a=($_m==2&&$_d==29)?4:1;
				while (true){
					if (in_array($_y,$y)){
						unset($bad);break;
					}
					$_y+=$a;
					if ($_y>$maxy)break;
				}
			}
			
			if (isset($bad)) return false;
		}
		$dt="{$_y}-{$_m}-{$_d} {$_h}:{$_i}:{$_s}";
		return strtotime($dt);
	}
	//得到某个月的最大天数
	protected function _methodDay($m,$y=true){
		$m31=array(1,3,5,7,8,10,12);
		$m30=array(4,6,9,11);
		if(in_array($m, $m31)) return 31;
		if(in_array($m, $m30)) return 30;
		if ($y) return 29;
		else return 28;
	}
	//查找某值的下一个规则值
	protected function _valFind($var,$now_var){
		$bad=true;
		$var=is_array($var)?$var:array($var);
		$max_var=max($var);
		while (true){
			if (in_array($now_var,$var)){
				unset($bad);break;
			}
			$now_var+=1;
			if ($now_var>$max_var)break;
		}
		if (isset($bad)) return false;//没下一年
		return $now_var;
	}
	//按规则返回下个数间的间隔
	protected function _loopFind($var,$now_var,$start,$end,$is_cp=false){
		if($var===Timer::LOOP){
			if ($is_cp){
				return array(true,0);
			}else{
				$_m=false;
				if ($now_var+1>$end){
					$_m=true;
				}
				return array($_m,1);
			}
		}
		if (!is_array($var))$var=array($var);
		//4 156
		$op=false;
		$min=false;
		$next=false;
		while (count($var)){
			$now=array_shift($var);
			if ($now_var>$now){
				if($min===false) $min=$now;
				continue;
			}
			if ($now==$now_var){
				$op=true;continue;
			}
			if($now>$now_var){
				$next=$now;break;
			}
		}
		if ($is_cp&&$op)$next=$now_var;
		if ($next!==false) return array(true,$next-$now_var);
		else{
			if ($min!==false) return array(true,$end-$now_var+($min-$start)+1);//5
			else return array(true,$end-$start+1);//
		}
	}
	//按规则返回下个数
	protected function _opFind($var,$now_var,$start,$end,$is_cp=false){
		$_m=false;
		if($var===Timer::LOOP){
			if ($is_cp){
				$ns=$now_var;
			}else{
				$ns=$now_var+1;
				if ($ns>$end){
					$_m=true;
					$ns=$start;
				}
			}
		}else{
			$var=is_array($var)?$var:array($var);
			$_s=false;
			foreach ($var as $v){
				if ($is_cp?($v>=$now_var):($v>$now_var)){
					$ns=$v;
					$_s=true;
					break;
				}
			}
			if (!$_s){
				$ns=min($var);
				$_m=true;
			}
		}
		return array($_m,$ns);
	}
}