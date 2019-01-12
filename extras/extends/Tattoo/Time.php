<?php
/**
 * Time functions
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Time.php 4536 2016-04-19 07:04:12Z alacner $
 */

final class Tattoo_Time
{
	public static $typeOptions = array(
		"month" => "每月",
		"week" => "每周",
		"day" => "每日",
		"hour" => "每小时",
		"interval" => "每隔",
		"custom" => "自定义"
	);

	public static $dayOptions = array(
		"1" => "1日",
		"2" => "2日",
		"3" => "3日",
		"4" => "4日",
		"5" => "5日",
		"6" => "6日",
		"7" => "7日",
		"8" => "8日",
		"9" => "9日",
		"10" => "10日",
		"11" => "11日",
		"12" => "12日",
		"13" => "13日",
		"14" => "14日",
		"15" => "15日",
		"16" => "16日",
		"17" => "17日",
		"18" => "18日",
		"19" => "19日",
		"20" => "20日",
		"21" => "21日",
		"22" => "22日",
		"23" => "23日",
		"24" => "24日",
		"25" => "25日",
		"26" => "26日",
		"27" => "27日",
		"28" => "28日",
		"29" => "29日",
		"30" => "30日",
		"31" => "31日",
		"L" => "最后一天",
	);

	public static $hourOptions = array(
		"0" => "0点",
		"1" => "1点",
		"2" => "2点",
		"3" => "3点",
		"4" => "4点",
		"5" => "5点",
		"6" => "6点",
		"7" => "7点",
		"8" => "8点",
		"9" => "9点",
		"10" => "10点",
		"11" => "11点",
		"12" => "12点",
		"13" => "13点",
		"14" => "14点",
		"15" => "15点",
		"16" => "16点",
		"17" => "17点",
		"18" => "18点",
		"19" => "19点",
		"20" => "20点",
		"21" => "21点",
		"22" => "22点",
		"23" => "23点",
	);

	public static $weekOptions = array(
		"1" => "周一",
		"2" => "周二",
		"3" => "周三",
		"4" => "周四",
		"5" => "周五",
		"6" => "周六",
		"0" => "周日",
	);

	public static $minuteOptions = array(
		"0" => "00分",
		"5" => "5分",
		"10" => "10分",
		"15" => "15分",
		"20" => "20分",
		"25" => "25分",
		"30" => "30分",
		"35" => "35分",
		"40" => "40分",
		"45" => "45分",
		"50" => "50分",
		"55" => "55分",
	);

	public static $intervalTypes = array(
		"minute" => "分钟",
		"hour" => "小时",
		"day" => "天",
	);

	public static function cronExpressionBuilder($cron)
	{
		$args = array();
		$args[] = $cron['type'];

		switch($cron['type']) {
			case 'month':
				$args[] = $cron['month']['day'];
				$args[] = $cron['month']['hour'];
				break;
			case 'week':
				$args[] = $cron['week']['day'];
				$args[] = $cron['week']['hour'];
				break;
			case 'day':
				$args[] = $cron['day']['hour'];
				break;
			case 'hour':
				$args[] = $cron['hour']['minute'];
				break;
			case 'interval':
				$args[] = $cron['interval']['num'];
				$args[] = $cron['interval']['type'];
				break;
			case 'custom':
			default:
				$args[] = $cron['custom']['expression'];
		}

		return call_user_func_array('self::_cronExpressionBuilder', $args);
	}

	public static function _cronExpressionBuilder($type)
	{
		$args = func_get_args();
		$type = array_shift($args);
		list($m, $n) = $args;

		switch($type){
			case 'month':
				return "0 0 $n $m * ?";
			case 'week':
				return "0 0 $n ? * $m";
			case 'day':
				return "0 0 $m * * ?";
			case 'hour':
				return "0 $m * * * ?";
			case 'interval':
				switch($n) {
					case 'hour':
						return "0 * */$m * * ?";
					case 'day':
						return "0 * * */$m * ?";
					case 'minute':
					default:
						return "0 */$m * * * ?";
				}
			case 'custom':
				return $m;
			default:
				return "0 */1 * * * ?";
		}
	}

	public static function cronExpressionParser($expression)
	{
		$cron = array();
		if (preg_match('|0 0 (\d+) (\d+) \* \?|', $expression, $m)) {
			$cron['type'] = 'month';
			$cron['month']['day'] = $m[2];
			$cron['month']['hour'] = $m[1];
		} else if (preg_match('|0 0 (\d+) \? \* (\d+)|', $expression, $m)) {
			$cron['type'] = 'week';
			$cron['week']['day'] = $m[2];
			$cron['week']['hour'] = $m[1];
		} else if (preg_match('|0 0 (\d+) \* \* \?|', $expression, $m)) {
			$cron['type'] = 'day';
			$cron['day']['hour'] = $m[1];
		} else if (preg_match('|0 (\d+) \* \* \* \?|', $expression, $m)) {
			$cron['type'] = 'hour';
			$cron['hour']['minute'] = $m[1];
		} else if (preg_match('|0 \* \* \*\/(\d+) \* \?|', $expression, $m)) {
			$cron['type'] = 'interval';
			$cron['interval']['num'] = $m[1];
			$cron['interval']['type'] = 'day';
		} else if (preg_match('|0 \* \*\/(\d+) \* \* \?|', $expression, $m)) {
			$cron['type'] = 'interval';
			$cron['interval']['num'] = $m[1];
			$cron['interval']['type'] = 'hour';
		} else if (preg_match('|0 \*\/(\d+) \* \* \* \?|', $expression, $m)) {
			$cron['type'] = 'interval';
			$cron['interval']['num'] = $m[1];
			$cron['interval']['type'] = 'minute';
		} else {
			$cron['type'] = 'custom';
			$cron['custom']['expression'] = $expression;
		}

		return $cron;
	}
}