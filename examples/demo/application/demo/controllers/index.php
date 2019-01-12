<?php

class Demo_IndexController extends Desire_Application_Controller_Abstract
{
	public function init()
	{
		//setPartialDir($partialDir)
		//echo '<br/>index init';
	}
	
	public function indexAction()
	{
		Desire_Config::get('app.logger');
		/* 队列的例子，注意，这个和dao一样，仅对service提供服务 */
		$queue = Blaze_Models_Engine_Queue_Factory::getInstance()->createDemoQueue();/* @var $queue Blaze_Models_Engine_Queue_Demo */
		// 默认
		var_dump($queue->getQueue()->putJson(Blaze_Time::micro()));
		var_dump($queue->getQueue()->getJson());
		// 也可以用下面的 魔术方法
		var_dump($queue->putJson(Blaze_Time::micro()));
		var_dump($queue->getJson());
		
		/* 缓存的例子，注意，现在已经提到全局缓存了，所以，确保业务的缓存key冲突问题 */
		Blaze_Factory::getInstance()->getCache()->memcache()->set('test', 'this is memcached:' . Blaze_Time::now());
		var_dump(Blaze_Factory::getInstance()->getCache()->memcache()->get('test'));
		// 也可以用下面的 魔术方法
		Blaze_Factory::getInstance()->getCache()->memcache()->set('test', 'this is memcached:' . Blaze_Time::now());
		var_dump(Blaze_Factory::getInstance()->getCache()->memcache()->get('test'));

		
		//$this->getResponse()->setHeader('text', 2324);
		//echo '<br/>';
		//echo $this->getIp();
		//echo '<br/>';
		//echo $ip = $this->getIp(true);
		//echo '<br/>';
		//echo long2ip($ip);
		//echo '<br/>';
		//Desire_Runtime::spent('fdsfdsfds');
		
		/* 设置session */
		$this->getResponse()->setSession('t', Blaze_Time::now());
		$this->getResponse()->setSession('ta', array('a' => Blaze_Time::now(), 'b' => rand()));
		print_r($this->getSessions());
		
		$this->forward('test');
		//$this->render('love');
		
		/* 设置layout */
		//$this->setLayout('demo');
		/*获取GET变量*/
		$text = $this->getPost('text');
		/*获取POST变量*/
		$messageId = $this->getQuery('id');

		/*不输出页面，截止*/
		$this->noRender();
		
		/* cookie */
		$this->setCookie('time', Blaze_Time::now()); //设置cookie
		$this->getCookie('time'); //获取cookie

		//Blaze_Logger::getInstance()->err('hahahha'); //设置错误日志
		//Blaze_Logger::getInstance()->debug(array('ttt' => time(), 'tdsfds' => date('yyy')));
		//echo Desire_Math::random(12);
	}
	
	public function testAction()
	{
		$this->setLayout('demo');
		$this->forward('test', 'test');//跳到某个controller的action

		//用 指定的模板文件渲染
		$this->setRender('love');
		//echo '<br/>index test action';
	}
	
	public function urlAction()
	{
		$this->redirect("http://www.google.com");
	}
}