<?php

class Taobao_MessageCenter {
	/**
	 * 根据通道设置的情况 填充收件人地址
	 * @var string
	 */
	 var $address;
	 
	 /**
	  * 模板主题中的变量KEY-VALUE对，格式 key1:value1;key2:value2
	  * @var string
	  */
	 var $subject;
	 
	 /**
	  * 模板内容中的变量KEY-VALUE对，格式 key1:value1;key2:value2
	  * @var string
	  */
	 var $content;
	 
	 /**
	  * 四个通道数字标识  1, 2, 4, 8 	 分别对应  	邮件,短信,站内信,旺旺
	  * @var int
	  */
	 var $channel = 8;
	 
	 /**
	  * 调用应用标示
	  * @var string
	  */
	 var $sourceId;
	 
	 /**
	  * 模板ID
	  * @var string
	  */
	 var $templateId;
	 
	 /**
	  * 消息类型ID
	  * @var string
	  */
	 var $messageTypeId;
	 
	 /**
	  * 接口地址
	  * @var string
	  */
	 var $serviceUrl;
	 
	 /**
	  * 
	  * @var string
	  */
	 var $serviceMethod = "send";
	 
	 /**
	  * 
	  * mc配置
	  * @var array
	  */
	 var $config;
	 
	function Taobao_MessageCenter($config = array()) {
		$this->config = $config;
		$this->sourceId = $this->config["source_id"];
		$this->templateId = $this->config["template_id"];
		$this->serviceUrl = $this->config["service_url"];
	}
	
	/**
	 * @desc 发送旺旺消息
	 * 
	 * @param array $param 消息属性
	 * 		taobao_nick : 接受消息的淘宝nick
	 * 		subject : 消息标题
	 * 		content : 消息内容
	 * @param string $type 消息类型
	 * 
	 * @return boolean
	 */
	function send(array $param, $type = "notice") {
		# 判断消息类型
		switch ($type) {
			case "notice":
				$this->messageTypeId = $this->config["message_type_notice"];
				break;
			case "emersion":
				$this->messageTypeId = $this->config["message_type_emersion"];
				break;
		}
		
		# 判断关键属性是否存在,不处理异常
		if (isset($param["taobao_nick"]) && isset($param["subject"]) && isset($param["content"])) {
			$this->address = $param["taobao_nick"];
			$this->subject = $param["subject"];
			$this->content = $param["content"];
			
			$ary = array(
				$this->address, 
				$this->subject,
				$this->content,
				$this->channel,
				$this->sourceId,
				$this->templateId,
				$this->messageTypeId
			);

			$result = $this->soapCall($this->serviceUrl, $this->serviceMethod, $ary);
			$status = strpos($result, "true") ? "true" : "false";
			
			$insert_param = array(
				"type" => $type,
				"taobao_nick" => $this->address,
				"status" => $status,
				"send_time" => time(),
				"data" => $result,
				"msg_type" => $param["msg_type"]
			);
			
			return $insert_param;
		} else {
			return false;
		}
	}
	
	function soapCall($url, $functionName, $params) {
	   $c = new SoapClient($url, array('encoding'=>'UTF8'));
	   $types = $c->__getTypes();
	   $paramArray = array();
	   foreach ($types as $type) {
	      $type = trim(str_replace('struct', '', $type));
	      if (strpos($type, $functionName.'') === 0) {
	         $type = str_replace($functionName.' ', '', $type);
	         $type = str_replace('{', '', $type);
	         $type = str_replace('}', '', $type);
	         $type = str_replace(';', '', $type);
	         $tmp = explode(' ', trim($type));
	         for ($i = 1; $i < count($tmp); $i+=2) {
	            $paramArray[] = trim($tmp[$i]);
	         }
	         break;
	      }
	   }
	   if (count($paramArray) != count($params)) {
//	       exit('传递参数数量不正确.');
			return false;
	   }
	   $p = array();
	   for ($i = 0; $i < count($params); ++$i) {
	      $p[$paramArray[$i]] = $params[$i];
	   }
	   $r = $c->$functionName($p);
	   foreach ($r as $key => $val) {
	      return $val;
	   }
	}
}