<?php
/**
 * Class YunpianApi
 * @see http://www.yunpian.com/api/sms.html
 */
class YunpianApi
{
	protected $apikey;
	protected $name;
	protected $password;
	protected $cookie;

	/**
	 * 查屏蔽词
	 * @param string $text
	 * @return mixed
	 */
	public function get_black_word($text = '')
	{
		$response = Desire_Http::post('https://sms.yunpian.com/v1/sms/get_black_word.json', array(array(
			'apikey' => $this->apikey,
			'text' => $text,
		), array()));

		$res = json_decode($response->data, true);
		if ($res['code'] === 0) {
			if ($res['result']['black_word']) {
				return explode(',', $res['result']['black_word']);
			}
		}

		return array();
	}

	/**
	 * 发短信
	 * params [mobile, text[,args[,...]]
	 * @return mixed
	 */
	public function send()
	{
		$args = func_get_args();
		$mobile = array_shift($args);

		$response = Desire_Http::post('https://sms.yunpian.com/v1/sms/send.json', array(array(
			'apikey' => $this->apikey,
			'mobile' => $mobile,
			'text' => call_user_func_array('sprintf', $args),
		), array()));

		return json_decode($response->data, true);
	}

	/**
	 * 查短信发送记录
	 * @param $start_time 2013-08-11 00:00:00
	 * @param $end_time 2013-08-12 00:00:00
	 * @param int $page_num 页码，从1开始
	 * @param int $page_size 每页个数，最大100个
	 * @return array
	 */
	public function get_record($start_time, $end_time = null, $page_num = 1, $page_size = 20)
	{
		return $this->get_record_with_mobile(null, $start_time, $end_time, $page_num, $page_size);
	}


	/**
	 * 查短信发送记录
	 * @param $mobile
	 * @param $start_time 2013-08-11 00:00:00
	 * @param $end_time 2013-08-12 00:00:00
	 * @param int $page_num 页码，从1开始
	 * @param int $page_size 每页个数，最大100个
	 * @return array
	 */
	public function get_record_with_mobile($mobile, $start_time, $end_time = null, $page_num = 1, $page_size = 20)
	{
		$formvars = array(
			'apikey' => $this->apikey,
			'start_time' => Desire_Time::format('Y-m-d H:i:s', $start_time),
			'end_time' => $end_time ? Desire_Time::format('Y-m-d H:i:s', $end_time) : Desire_Time::format(),
			'page_num' => $page_num,
			'page_size' => $page_size,
		);
		if ($mobile) $formvars['mobile'] = $mobile;
		$response = Desire_Http::post('https://sms.yunpian.com/v1/sms/get.json', array($formvars, array()));
		$res = json_decode($response->data, true);
		if ($res['code'] === 0 && $res['sms']) {
			return $res['sms'];
		}

		return array();
	}

	/**
	 * 查短信发送记录总条数
	 * @param $start_time 2013-08-11 00:00:00
	 * @param $end_time 2013-08-12 00:00:00
	 * @return array
	 */
	public function get_record_count($start_time, $end_time = null)
	{
		return $this->get_record_count_with_mobile(null, $start_time, $end_time);
	}


	/**
	 * 查短信发送记录总条数
	 * @param $mobile
	 * @param $start_time 2013-08-11 00:00:00
	 * @param $end_time 2013-08-12 00:00:00
	 * @param $fee
	 * @return array
	 */
	public function get_record_count_with_mobile($mobile, $start_time, $end_time = null, $fee = false)
	{
		$totals = array();
		$formvars = array(
			'apikey' => $this->apikey,
			'start_time' => Desire_Time::format('Y-m-d H:i:s', $start_time),
			'end_time' => $end_time ? Desire_Time::format('Y-m-d H:i:s', $end_time) : Desire_Time::format(),
		);
		if ($mobile) $formvars['mobile'] = $mobile;
		$response = Desire_Http::post('https://sms.yunpian.com/v1/sms/count.json', array($formvars, array()));

		$res = json_decode($response->data, true);
		if ($res['code'] === 0) {
			$totals['number'] = $res['total'];
		} else {
			$totals['number'] = 0;
		}

		//TODO 因为没有直接获取fee的条数的接口，循环获取
		//<!--
		$perpage = 100;
		if ($fee && $totals['number']) {
			$totals['fee'] = 0;
			$totals['send_status'] = 0;
			$totals['report_status'] = 0;

			$page = ceil($totals['number']/$perpage);

			while($page) {
				if ($mobile) {
					$records = $this->get_record_with_mobile($mobile, $formvars['start_time'], $formvars['end_time'], $page--, $perpage);
				} else {
					$records = $this->get_record($formvars['start_time'], $formvars['end_time'], $page--, $perpage);
				}
				foreach($records as $record) {
					$totals['fee'] += (int)$record['fee'];
					$totals['send_status'] += (($record['send_status'] == 'SUCCESS') ? 1 : 0);
					$totals['report_status'] += (($record['report_status'] == 'SUCCESS') ? 1 : 0);
				}
			}
		}
		//-->
		return $totals;
	}

	public function login()
	{
		$response = Desire_Http::post('https://www.yunpian.com/component/login', array(array(
			'name' => $this->name,
			'password' => $this->password,
		), array()));

		$state = json_decode($response->data, true);
		if (!isset($state['success']) || !$state['success']) {
			return false;
		}
		print_r($response);
		print_r( $response->headers);
		$this->cookie = $response->headers['Set-Cookie'];
	}

	public function proxy($uri, $data = '', array $headers = array())
	{
		$headers['Cookie'] = $this->cookie;
		print_r(Desire_Http::post('https://www.yunpian.com'.$uri, $data, $headers));
	}

}