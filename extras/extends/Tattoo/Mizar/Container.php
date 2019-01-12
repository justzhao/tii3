<?php
/**
 * Mizar Container
 *
 * @author Yametei
 * @version $Id: Container.php 7838 2016-10-11 01:56:40Z alacner $
 */

class _Tattoo_Mizar_Container
{
	public function test()
	{
		return func_get_args();
	}
}

class Tattoo_Mizar_Container extends Desire_Worker_Abstract
{
	protected $name;
	protected $listen;
	protected $host;
	protected $port;
	protected $provider;
	protected $register;

	public function __construct($name = NULL, $container = NULL, $handler = NULL)
	{
		$container || $container = Desire::get('tattoo.mizar.container', []);
		$this->name = $name ?: Desire::valueInArray($container, 'name', 'default');
		$this->handler = Desire::object($handler ?: Desire::valueInArray($container, 'handler', '_Tattoo_Mizar_Container'));
		$this->listen = Desire::valueInArray($container, 'listen', 'chunk.json://0.0.0.0:41903');
		$this->port = Desire_Network::getPort($this->listen);

		Desire_Worker::$startFile = $name . '#' . $this->port;

		parent::__construct(
			$this->listen,
			Desire::valueInArray($container, 'worker', []),
			Desire::valueInArray($container, 'options', []),
			Desire::valueInArray($container, 'host', Desire_Network::getIp())
		);

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
						'dsn' => $this->worker->dsn,
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

	/**
	 * onMessage
	 *
	 * @param Desire_Worker_Connection $connection
	 * @param $req
	 * @return mixed
	 */
	public function onMessage($connection, $req)
	{
		try {
			if (!is_array($req) || !isset($req['methodName'])) {
				throw new Desire_Exception('invalid request');
			}

			if (method_exists($this->handler, $req['methodName'])) {
				try {
					$connection->send(call_user_func_array(
						[$this->handler, $req['methodName']],
						Desire::valueInArray($req, 'args', [])
					));
				} catch (Exception $e) {
					$connection->send(["errcode" => $e->getCode(), "errmsg" => $e->getMessage()]);
				}
			} else {
				throw new Desire_Exception('invalid methodName %s', $req['methodName']);
			}
		} catch (Exception $e) {//invalid request
			$connection->close(["errcode" => $e->getCode(), "errmsg" => $e->getMessage()]);
		}
	}

	/**
	 * Worker runner
	 */
	public static function run()
	{
		$class = get_called_class();
		new $class;
		defined('TATTOO_WORKER_CONTAINER_START') || Desire_Worker::run();
	}
}