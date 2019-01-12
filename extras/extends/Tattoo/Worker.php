<?php
/**
 * Multi-Process Worker
 *
 * <code>
 * $container = new Tattoo_Worker;
 * $container->addWorker('time', 'time');
 * $container->addWorker('microtime', 'microtime', array(true));
 * $container->addWorker('runtime', array('Desire', 'usage'));
 * $container->addWorker('method1', array($this, 'method1'));
 * $container->start();
 * </code>
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Worker.php 7838 2016-10-11 01:56:40Z alacner $
 */

/**
 * Class _Tattoo_Worker
 */
class _Tattoo_Worker
{
	public $name;
	public $callback;
	public $args = array();

	/**
	 * @param $name
	 * @param callable $callback
	 * @param array $args
	 */
	public function __construct($name, callable $callback, array $args = array())
	{
		$this->name = $name;
		$this->callback = $callback;
		$this->args = $args;
	}
}

class Tattoo_Worker
{
	private $terminate = FALSE;

	private $workerNumber = 0;
	private $pids = array();//pid => _Tattoo_Worker
	private $workers = array();//_Tattoo_Worker

	private $signalDispatch = FALSE;
	private $gcEnabled = FALSE;

	private $signos = array();//eg. SIGTERM => 'SIGTERM'

	public function __construct()
	{
		if (PHP_SAPI != "cli"){
			throw new Desire_Exception('Only run in command line mode');
		}

		//Make sure PHP has support for pcntl
		if (!function_exists('pcntl_signal')) {
			throw new Desire_Exception('Require pcntl extension loaded');
		}

		if (!function_exists('pcntl_signal_dispatch')) {
			declare(ticks = 1);
			$this->signalDispatch = FALSE;
		} else {
			$this->signalDispatch = TURE;
		}

		// Enable PHP 5.3 garbage collection
		if (function_exists('gc_enable')) {
			gc_enable();
			$this->gcEnabled = gc_enabled();
		}

		foreach(get_defined_constants() as $k => $v) {
			if (preg_match('|^SIG[a-z0-9]+|i', $k)) {
				$this->signos[$k] = $v;
			}
		}

		$this->setSignalHandler(array($this, "signalHandler"), array(SIGTERM, SIGINT, SIGQUIT, SIGCHLD));
	}

	protected function setSignalHandler(callable $handler = NULL, $signo = NULL, $restartSyscalls = TRUE)
	{
		is_null($signo) && $signo = array_values($this->signos);
		is_string($signo) && $signo = array($this->signos[$signo]);
		is_numeric($signo) && $signo = array($signo);

		if (!is_array($signo)) {
			throw new Desire_Exception("signo error");
		}

		foreach ($signo as $sig) {
			pcntl_signal($sig, $handler, $restartSyscalls);
		}
	}

	//信号处理函数
	public function signalHandler($signo)
	{
		Desire_Logger::debug("Caught ".array_flip($this->signos)[$signo]);

		switch($signo){

			//User-defined signal
			case SIGUSR1: //busy
				break;
			//The child to the end of the signal
			case SIGCHLD:// if worker die, minus children num
				while(($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0){
					$this->workerNumber--;
					Desire_Logger::debug("Worker die, pid $pid, name ". $this->pids[$pid]->name);
				}
				break;
			//Interrupt the process
			case SIGINT:
				foreach ($this->workers as $pid => $worker) {
					posix_kill($pid, SIGTERM);
					unset($this->workers[$pid]);
				}
				sleep(2);
			case SIGTERM:
			case SIGHUP:
			case SIGQUIT:
				$this->terminate = TRUE;
				break;
			default:
				return FALSE;
		}
	}

	/**
	 * @param string $name
	 * @param callable $callback
	 * @param array $args
	 * @param int $num number of replication
	 */
	public function addWorker($name, callable $callback, array $args = array(), $num = 1)
	{
		for (;$num--;) {
			$this->workers[] = new _Tattoo_Worker($name, $callback, $args);
		}
	}

	/**
	 * Start process
	 */
	public function start()
	{
		foreach($this->workers as $worker) {
			$pid = pcntl_fork();
			if ($pid == -1) {
				Desire_Logger::warn('fork error');
			} else if ($pid) {//parent
				$this->pids[$pid] = $worker;
				$this->workerNumber++;
				Desire_Logger::debug("Worker forked, pid $pid, name ". $worker->name);
			} else {//child
				Desire_Logger::debug("Worker running, name ". $worker->name);
				if ($worker->args) {
					call_user_func_array($worker->callback, $worker->args);
				} else {
					call_user_func($worker->callback);
				}
				exit;
			}
		}

		while(!($this->terminate || (count($this->workers) ==0))) {
			$this->signalDispatch && pcntl_signal_dispatch();

			foreach($this->workers as $pid => $worker) {
				$res = pcntl_waitpid($pid, $status, WNOHANG);

				// If the process has already exited
				if($res == -1 || $res > 0) {
					Desire_Logger::debug("Process exited, pid $pid, name ". $worker->name);
					unset($this->workers[$pid]);
				}
			}
			sleep(1);
		}

		posix_kill(0, SIGKILL);
		Desire_Logger::debug("Main process exited");
		exit(0);
	}
}