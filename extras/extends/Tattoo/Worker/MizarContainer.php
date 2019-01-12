<?php
/**
 * Worker Container for Mizar
 *
 * @author Yametei
 * @version $Id: MizarContainer.php 7848 2016-10-11 06:22:00Z alacner $
 */

class Tattoo_Worker_MizarContainer extends Desire_Worker_Abstract
{
	protected $listen;
	protected $host;
	protected $port;
	protected $provider;
	protected $register;

	public function __construct($container = 'default')
	{
		$container = Desire::get('tattoo.mizar.container.'.$container, []);
		$this->name = Desire::valueInArray($container, 'name', 'default');
		$this->listen = Desire::valueInArray($container, 'listen', 'chunk.json://0.0.0.0:41903');
		$this->port = Desire_Network::getPort($this->listen);

		Desire_Worker::$startFile || Desire_Worker::$startFile = $this->name . '#' . $this->port;

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

			$method = 'do' . str_replace('_', '', ucwords($req['methodName'], '_'));//abc_defGhi_JKL => doAbcDefGhiJKL
			$method_exists = method_exists($this, $method);
			$method_exists || $method = 'do_' . ucwords($req['methodName']);//abc_defGhi_JKL => do_abc_defGhi_JKL
			$method_exists || $method_exists = method_exists($this, $method);

			if ($method_exists) {
				try {
					$connection->send(call_user_func_array([$this, $method], Desire::valueInArray($req, 'args', [])));
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

	public function doEcho()
	{
		return func_get_args();
	}
}