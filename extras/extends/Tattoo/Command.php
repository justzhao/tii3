<?php
/**
 * Command class
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Command.php 5223 2016-05-16 05:48:07Z alacner $
 */

final class Tattoo_Command
{
	/**
	 * 执行本地宿主机命令
	 *
	 * @see Desire::exec
	 * @return object
	 */
	public static function runLocalCommand()
	{
		$ret = call_user_func_array('Desire::exec', func_get_args());
		Desire_Logger::debug('---- Executing: $ ' . $ret->command);
		Desire_Logger::debug('---- Succeed: ' . ($ret->succeed ? 'yes' : 'no'));
		Desire_Logger::debug('---- Status: ' . $ret->status);
		Desire_Logger::debug('---- Output: ' . $ret->output);
		Desire_Logger::debug('---------------------------------');

		$ret->traces['local'] = $ret;

		return $ret;
	}

	/**
	 * 解析host为host和port
	 *
	 * @param $host
	 * @return array [host,port]
	 */
	public static function parseHost($host)
	{
		return array_pad(explode(':', $host), 2, 22);
	}

	/**
	 * 执行单台远程机器命令
	 *
	 * @param $command
	 * @param string $remoteHost 127.0.0.1,10.0.0.1:1234
	 * @param string $user
	 * @param string $repeater like $remoteHost
	 * @return object
	 */
	public static function runRemoteCommand($command, $remoteHost = '127.0.0.1', $user = 'root', $repeater = NULL)
	{
		$needTTY = '-T';

		Desire_Logger::debug('---- Remote: ' . $remoteHost);
		list($host, $port) = static::parseHost($remoteHost);

		if ($repeater) {
			Desire_Logger::debug('---- Repeater: ' . $repeater);
			$command = sprintf(
				'ssh %s -p %d -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o CheckHostIP=false %s@%s %s',
				$needTTY,
				$port,
				escapeshellarg($user),
				escapeshellarg($host),
				escapeshellarg($command)
			);
			list($host, $port) = static::parseHost($repeater);
		}

		return static::runLocalCommand(
			'ssh %s -p %d -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o CheckHostIP=false %s@%s %s',
			$needTTY,
			$port,
			escapeshellarg($user),
			escapeshellarg($host),
			escapeshellarg($command)
		);
	}

	/**
	 * 执行所有远程目标机器命令
	 *
	 * @param array $hosts
	 * @param string  $command
	 * @param string $user
	 * @param integer $delay 每台机器延迟执行post_release任务间隔, 不推荐使用, 仅当业务无法平滑重启时使用
	 * @param $repeater
	 * @return array
	 */
	public static function runRemotesCommand($command, $hosts = [], $user = 'root', $delay = 0, $repeater = NULL)
	{
		$ret = new stdClass();
		$ret->succeed = true;
		$traces = [];

		foreach ($hosts as $remoteHost) {

			$traces[$remoteHost] = $ret = static::runRemoteCommand($command, $remoteHost, $user, $repeater);

			if ($delay > 0) {
				// 每台机器延迟执行post_release任务间隔, 不推荐使用, 仅当业务无法平滑重启时使用
				Desire_Logger::debug(sprintf('---- Sleep: %d s', $delay));
				sleep($delay);
			}

			if (!$ret->succeed) {
				break;
			}
		}

		$ret->traces = $traces;
		return $ret;
	}

	/**
	 * rsync时，要排除的文件
	 *
	 * @param array $excludes
	 * @return string
	 */
	public static function excludes($excludes) {

		$excludesRsync = '';
		foreach ($excludes as $exclude) {
			$excludesRsync .= sprintf("--exclude=%s ", escapeshellarg(trim($exclude)));
		}

		return trim($excludesRsync);
	}

	/**
	 * rsync 同步文件远程目标机器
	 *
	 * @param $src
	 * @param $target
	 * @param $excludes
	 * @param $remoteHost
	 * @param $user
	 * @param $repeater
	 * @return object
	 */
	public static function syncRemoteFiles($src, $target, $excludes = [], $remoteHost = '127.0.0.1', $user = 'root', $repeater = NULL)
	{
		Desire_Logger::debug('---- Remote: ' . $remoteHost);
		Desire_Logger::debug('---- Exclude files: ' . implode(';', $excludes));

		list($host, $port) = static::parseHost($remoteHost);

		$command = sprintf('rsync -avzq --rsh="ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o CheckHostIP=false -p %d" %s %s %s@%s:%s',
			$port,
			static::excludes($excludes),
			escapeshellarg(rtrim($src, '/') . '/'),
			escapeshellarg($user),
			escapeshellarg($host),
			escapeshellarg($target)
		);

		return $repeater ? static::runRemoteCommand($command, $repeater, $user) : static::runLocalCommand($command);
	}


	/**
	 * rsync 同步文件到所有远程目标机器
	 *
	 * @param $src
	 * @param $target
	 * @param array $excludes
	 * @param array $hosts
	 * @param string $user
	 * @param int $delay
	 * @param $repeater
	 * @return object|stdClass
	 */
	public static function syncRemotesFiles($src, $target, $excludes = [], $hosts = [], $user = 'root', $delay = 0, $repeater = NULL)
	{
		$ret = new stdClass();
		$ret->succeed = true;
		$traces = [];

		foreach ($hosts as $remoteHost) {

			$traces[$remoteHost] = $ret = static::syncRemoteFiles($src, $target, $excludes, $remoteHost, $user, $repeater);

			if ($delay > 0) {
				// 每台机器延迟执行post_release任务间隔, 不推荐使用, 仅当业务无法平滑重启时使用
				Desire_Logger::debug(sprintf('---- Sleep: %d s', $delay));
				sleep($delay);
			}

			if (!$ret->succeed) {
				break;
			}
		}

		$ret->traces = $traces;
		return $ret;
	}

	/**
	 * command集合
	 *
	 * @param $cmd
	 * @return string
	 */
	public static function implode($cmd)
	{
		return implode(' 2>&1 && ', is_array($cmd) ? $cmd : [$cmd]);
	}
}