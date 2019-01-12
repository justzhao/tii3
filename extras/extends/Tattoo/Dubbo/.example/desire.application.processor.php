<?php
/**
 * Filter: desire.application.processor
 *
 * dubbo协议转换器: 将模块名为"dubbo"的访问请求进行转换后调用目标执行器的过程
 *
 * 例子：
 * 访问路径：http://host:port/dubbo/service.name
 * 头部数据: Method-Name 方法名: module_controller_action
 * 最终调用: module/controllers/controller.php的action方法
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: desire.application.processor.php 4091 2016-03-24 05:38:01Z alacner $
 */

Desire_Event::register(basename(__FILE__, ".php"), function($processor)
{
	if (!in_array($processor->getModuleName(), array('dubbo'))) {
		return $processor;
	}

	if ($methodName = $processor->getHeader('Method-Name')) {
		//$serviceVersion = $processor->getHeader('Service-Version', '');
		//$serviceGroup = $processor->getHeader('Service-Group', '');
		//$parameterTypes = $processor->getHeader('Parameter-Types', '');
		$args = $processor->getBody();
	} else {
		$methodName = $processor->getRequest('Method-Name', 'index');
		//$serviceVersion = $processor->getRequest('Service-Version', '');
		//$serviceGroup = $processor->getRequest('Service-Group', '');
		//$parameterTypes = $processor->getRequest('Parameter-Types', '');
		$args = $processor->getRequest('args', '[]');
	}

	$processor->noRender('json', true);//强制JSON

	$args = json_decode($args, true);

	if ($args && $methodName === '$invoke' && isset($args[0], $args[2])) {//genericService
		$methodName = $args[0];
		if (isset($args[2])) {
			$args = $args[2];
		}
	}

	if (isset($args[0])){
		foreach($args[0] as $k => $v) {
			$processor->setPair($k, $v);
		}
	}

	list($module, $controller, $action) = explode("_", $methodName, 3);

	$module || $module = 'default';
	$controller || $controller = 'index';
	$action || $action = 'index';
	$action = lcfirst(str_replace('_', '', ucwords($action, '_')));

	$processor->setModuleName($module);
	$processor->setControllerName($controller);
	$processor->setActionName($action);

	return $processor;
});