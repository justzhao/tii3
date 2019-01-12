<?php
/**
 * Mizar Provider Container
 *
 * @author Yametei
 * @version $Id: MizarContainer.php 7377 2016-09-30 03:53:44Z alacner $
 */

class Tattoo_MizarContainer
{
	protected $name;
	protected $listen;
	protected $host;
	protected $port;
	protected $dsn;
	protected $provider;
	protected $register;

	public function __construct($name = NULL, $container = NULL)
	{
		$container || $container = Desire::get('tattoo.mizar.container', []);
		$this->name = $name ?: Desire::valueInArray($container, 'name', 'default');
		$this->listen = Desire::valueInArray($container, 'listen', 'chunk.json://0.0.0.0:41903');
		$this->port = Desire_Network::getPort($this->listen);

		Desire_Worker::$startFile = $name . '#' . $this->port;

		$worker = new Desire_Worker($this->listen, Desire::valueInArray($container, 'worker', []));
		$worker->onWorkerStart = [$this, 'onWorkerStart'];
		$worker->onMessage = [$this, 'onMessage'];

		$this->dsn = str_replace('0.0.0.0', Desire::valueInArray($container, 'host', Desire_Network::getIp()), $this->listen);
		$this->provider = Desire::valueInArray($container, 'provider', []);
		$this->register = new Desire_Worker_Client(Desire::get('tattoo.mizar.registry', "text.json://127.0.0.1:9527"));
	}

	public function onWorkerStart($worker)
	{
		$register = function() {
			try {
				$this->register->request(array_merge(
					$this->provider,
					[
						'cmd' => 'register',
						'name' => $this->name,
						'dsn' => $this->dsn,
						'timestamp' => Desire_Time::format(),
					]
				));
			} catch(Exception $e) {
				Desire_Logger::debug("register failed, because {$e->getMessage()}, reload");
			}
		};

		call_user_func($register);
		Desire_Worker_Timer::add(15, $register);
	}

	public function onMessage($connection, $req)
	{
		$application = Desire_Application::getInstance();

		$application->init();

		list($module, $controller, $action) = explode("_", Desire::valueInArray($req, 'methodName'), 3);

		$module || $module = 'default';
		$controller || $controller = 'index';
		$action || $action = 'index';
		$action = lcfirst(str_replace('_', '', ucwords($action, '_')));

		$application->setModuleName($module);
		$application->setControllerName($controller);
		$application->setActionName($action);

		$application->setIp(Desire::valueInArray($req, 'host'));
		$application->setPairs(Desire::valueInArray(Desire::valueInArray($req, 'args', []), 0, []));

		$application->run();

		$connection->send($application->getResult());
	}

	public static function run($name = NULL, $container = NULL)
	{
		$ins =  new self($name, $container);
		Desire_Worker::run();
	}
}