<?php
/**
 * A worker processor with pcntl extension.
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2005 - 2017, Fitz Zhang <alacner@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Worker.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Worker_Processor_Pcntl extends Tii_Worker_Processor_Abstract
{
    /**
     * All worker processes waiting for restart.
     * The format is like this [pid=>pid, pid=>pid].
     *
     * @var array
     */
    protected $pidsToRestart = [];

    /**
     * The file to store master process PID.
     *
     * @var string
     */
    protected $pidFile = '';

    /**
     * The file to store status info of current worker process.
     *
     * @var
     */
    protected $tempfile;

    /**
     * If the process does not exit after $delayKillingTime seconds try to kill it.
     *
     * @var int
     */
    protected $delayKillingTime = 2;

    /**
     * before event loop
     */
    public function starting()
    {
        //Reinstall signal handler
        pcntl_signal(SIGINT, SIG_IGN, false);// uninstall stop signal handler
        pcntl_signal(SIGTERM, SIG_IGN, false);// uninstall graceful stop signal handler
        pcntl_signal(SIGQUIT, SIG_IGN, false);// uninstall graceful reload signal handler
        pcntl_signal(SIGUSR1, SIG_IGN, false);// uninstall reload signal handler
        pcntl_signal(SIGUSR2, SIG_IGN, false);// uninstall status signal handler
        Tii_Worker::$events->add(SIGINT, Tii_Worker_Event::EV_SIGNAL, [$this, 'signalHandler']);// reinstall stop signal handler
        Tii_Worker::$events->add(SIGTERM, Tii_Worker_Event::EV_SIGNAL, [$this, 'signalHandler']);// reinstall graceful stop signal handler
        Tii_Worker::$events->add(SIGQUIT, Tii_Worker_Event::EV_SIGNAL, [$this, 'signalHandler']);// reinstall graceful reload signal handler
        Tii_Worker::$events->add(SIGUSR1, Tii_Worker_Event::EV_SIGNAL, [$this, 'signalHandler']);// reinstall reload signal handler
        Tii_Worker::$events->add(SIGUSR2, Tii_Worker_Event::EV_SIGNAL, [$this, 'signalHandler']);// reinstall status signal handler
        Tii_Worker::$events->add(SIGIO, Tii_Worker_Event::EV_SIGNAL, [$this, 'signalHandler']);// reinstall connection status signal handler
    }

    public function run()
    {
        Tii_Worker::init();

        $this->pidFile || $this->pidFile = Tii_Filesystem::hashfile(//pid file.
            Tii_Worker::$startFile,
            'worker',
            ".pid",
            Tii_Filesystem::getDataDir()
        );

        $this->tempfile || $this->tempfile = Tii_Filesystem::hashfile(Tii_Worker::$startFile, 'worker');

        $this->parseCommand();
        $this->daemonize();
        $this->initWorkers();
        $this->installSignal();
        $this->saveMasterPid();
        $this->forks();
        $this->dashboard();
        $this->resetStd();
        $this->monitorWorkers();
    }

    /**
     * Parse command.
     * php yourfile.php start | stop | restart | reload | status
     *
     * @return void
     */
    protected function parseCommand()
    {
        global $argv;
        // Check argv;
        $start_file = $argv[0];
        $available_commands = [
            'start',
            'stop',
            'restart',
            'reload',
            'status',
            'connections',
        ];
        $usage = "Usage: php yourfile.php {" . implode('|', $available_commands) . "} [-d]\n";

        if (!isset($argv[1]) || !in_array($argv[1], $available_commands)) {
            exit($usage);
        }

        // Get command.
        $command = trim($argv[1]);
        $command2 = isset($argv[2]) ? $argv[2] : '';

        // Start command.
        $mode = '';
        if ($command === 'start') {
            if ($command2 === '-d' || Tii_Worker::$daemonize) {
                $mode = 'in DAEMON mode';
            } else {
                $mode = 'in DEBUG mode';
            }
        }
        Tii_Logger::debug("Worker[$start_file] $command $mode");

        // Get master process PID.
        $master_pid = is_file($this->pidFile) ? file_get_contents($this->pidFile) : 0;
        $master_is_alive = $master_pid && @posix_kill($master_pid, 0) && posix_getpid() != $master_pid;
        // Master is still alive?
        if ($master_is_alive) {
            if ($command === 'start') {
                Tii_Logger::debug("Worker[$start_file] already running");
                exit;
            }
        } elseif ($command !== 'start' && $command !== 'restart') {
            Tii_Logger::debug("Worker[$start_file] not run");
            exit;
        }

        // execute command.
        switch ($command) {
            case 'start':
                if ($command2 === '-d') {
                    Tii_Worker::$daemonize = true;
                }
                break;
            case 'status':
                while (1) {
                    if (is_file($this->tempfile)) {
                        @unlink($this->tempfile);
                    }
                    // Master process will send SIGUSR2 signal to all child processes.
                    posix_kill($master_pid, SIGUSR2);
                    // Sleep 1 second.
                    sleep(1);
                    // Clear terminal.
                    if ($command2 === '-d') {
                        echo "\33[H\33[2J\33(B\33[m";
                    }
                    // Echo status data.
                    echo $this->formatStatusData();
                    if ($command2 !== '-d') {
                        exit(0);
                    }
                    echo "\nPress Ctrl+C to quit.\n\n";
                }
                exit(0);
            case 'connections':
                if (is_file($this->tempfile)) {
                    @unlink($this->tempfile);
                }
                // Master process will send SIGIO signal to all child processes.
                posix_kill($master_pid, SIGIO);
                // Waiting amoment.
                usleep(500000);
                // Display statistics data from a disk file.
                @readfile($this->tempfile);
                exit(0);
            case 'restart':
            case 'stop':
                if ($command2 === '-g') {
                    Tii_Worker::$init['graceful_stop'] = true;
                    $signo = SIGTERM;
                    Tii_Logger::debug("Worker[$start_file] is gracefully stopping ...");
                } else {
                    Tii_Worker::$init['graceful_stop'] = false;
                    $signo = SIGINT;
                    Tii_Logger::debug("Worker[$start_file] is stopping ...");
                }

                // Send stop signal to master process.
                $master_pid && posix_kill($master_pid, $signo);
                // Timeout.
                $timeout = 5;
                $start_time = time();
                // Check master process is still alive?
                while (1) {
                    $master_is_alive = $master_pid && posix_kill($master_pid, 0);
                    if ($master_is_alive) {
                        // Timeout?
                        if (!Tii_Worker::$init['graceful_stop'] && time() - $start_time >= $timeout) {
                            Tii_Logger::debug("Worker[$start_file] stop fail");
                            exit;
                        }
                        // Waiting amoment.
                        usleep(10000);
                        continue;
                    }
                    // Stop success.
                    Tii_Logger::debug("Worker[$start_file] stop success");
                    if ($command === 'stop') {
                        exit(0);
                    }
                    if ($command2 === '-d') {
                        Tii_Worker::$daemonize = true;
                    }
                    break;
                }
                break;
            case 'reload':
                if($command2 === '-g'){
                    $signo = SIGQUIT;
                    Tii_Logger::debug("Worker[$start_file] gracefully reload");
                }else{
                    $signo = SIGUSR1;
                    Tii_Logger::debug("Worker[$start_file] reload");
                }
                posix_kill($master_pid, $signo);
                exit;
            default :
                exit($usage);
        }
    }

    /**
     * Run as deamon mode
     *
     * @throws Exception
     */
    protected function daemonize()
    {
        if (!Tii_Worker::$daemonize) {
            Tii_Logger::$print = true;
            return;
        }

        umask(0);
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new Exception('fork failed');
        } elseif ($pid > 0) {
            exit(0);
        }
        if (-1 === posix_setsid()) {
            throw new Exception("setsid failed");
        }
        // Fork again avoid SVR4 system regain the control of terminal.
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new Exception("fork failed");
        } elseif (0 !== $pid) {
            exit(0);
        }
    }

    /**
     * Install signal
     */
    protected function installSignal()
    {
        //Install signal handler
        pcntl_signal(SIGINT, [$this, 'signalHandler'], false);// stop
        pcntl_signal(SIGTERM, [$this, 'signalHandler'], false);// graceful stop
        pcntl_signal(SIGQUIT, [$this, 'signalHandler'], false);// graceful reload
        pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);// reload
        pcntl_signal(SIGUSR2, [$this, 'signalHandler'], false);// status
        pcntl_signal(SIGIO, [$this, 'signalHandler'], false);// connection status
        pcntl_signal(SIGPIPE, SIG_IGN, false);// ignore
    }

    /**
     * Signal handler
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {
        switch ($signal) {
            // Stop
            case SIGINT:
            case SIGTERM:// Graceful stop.
                Tii_Worker::$init['graceful_stop'] = $signal === SIGTERM;
                $this->quit();
                break;
            // Reload
            case SIGQUIT:
            case SIGUSR1:
                Tii_Worker::$init['graceful_stop'] = $signal === SIGQUIT;
                $this->pidsToRestart = $this->getAllPids();
                $this->reload();
                break;
            // Show status
            case SIGUSR2:
                $this->writeStatisticsToStatusFile();
                break;
            // Show connection status
            case SIGIO:
                $this->writeConnectionsStatisticsToStatusFile();
                break;
        }
    }

    /**
     * Save pid.
     *
     * @throws Exception
     */
    protected function saveMasterPid()
    {
        Tii_Worker::$pid = posix_getpid();
        if (false === @file_put_contents($this->pidFile, Tii_Worker::$pid)) {
            throw new Tii_Worker_Exception('can not save pid to ' . $this->pidFile);
        }
    }

    /**
     * Fork some worker processes.
     *
     * @return void
     */
    protected function forks()
    {
        /** @var $worker Tii_Worker */
        foreach (Tii_Worker::$workers as $worker) {
            if (Tii_Worker::$status === Tii_Worker::STATUS_STARTING) {
                if (empty($worker->name)) {
                    $worker->name = $worker->socketName;
                }
            }

            while (count(Tii_Worker::$pids[$worker->id]) < $worker->count) {
                $this->fork($worker);
            }
        }
    }

    /**
     * Get all pids of worker processes.
     *
     * @return array
     */
    protected function getAllPids()
    {
        $pids = [];
        foreach (Tii_Worker::$pids as $_pids) {
            foreach ($_pids as $pid) {
                $pids[$pid] = $pid;
            }
        }
        return $pids;
    }

    /**
     * Stop.
     *
     * @return void
     */
    public function quit()
    {
        Tii_Worker::$status = Tii_Worker::STATUS_SHUTDOWN;

        if (Tii_Worker::$pid === posix_getpid()) {// For master process.
            Tii_Logger::debug("Worker[" . basename(Tii_Worker::$startFile) . "] Stopping ...");

            $signo = $this->graceful_stop ? SIGTERM : SIGINT;//graceful stop signal

            foreach ($this->getAllPids() as $pid) {// Send stop signal to all child processes.
                posix_kill($pid, $signo);
                if (!$this->graceful_stop) {
                    Tii_Worker_Timer::add($this->delayKillingTime, 'posix_kill', [$pid, SIGKILL], false);
                }
            }

            // Remove statistics file.
            if (is_file($this->tempfile)) {
                @unlink($this->tempfile);
            }
        } else { // For child processes.
            // Execute exit.
            foreach (Tii_Worker::$workers as $worker) {
                $worker->stop();
            }
            if (!$this->graceful_stop || Tii_Worker_Connection::$statistics['connection_count'] <= 0) {
                Tii_Worker::$events->destroy();
                exit(0);
            }
        }
    }

    /**
     * Execute reload.
     *
     * @return void
     */
    protected function reload()
    {
        // For master process.
        if (Tii_Worker::$pid === posix_getpid()) {

            if (!in_array(Tii_Worker::$status, [Tii_Worker::STATUS_RELOADING, Tii_Worker::STATUS_SHUTDOWN])) {// Set reloading state.
                Tii_Logger::debug("Worker[" . basename(Tii_Worker::$startFile) . "] reloading");
                Tii_Worker::$status = Tii_Worker::STATUS_RELOADING;

                if (Tii_Worker::$onMasterReload) {
                    try {
                        call_user_func(Tii_Worker::$onMasterReload);
                    } catch (Exception $e) {
                        Tii_Logger::debug($e);
                    }
                    Tii_Worker::initIds();
                }
            }

            $signo = $this->graceful_stop ? SIGQUIT : SIGUSR1;//graceful stop signal

            // Send reload signal to all child processes.
            $reloadablePids = [];
            foreach (Tii_Worker::$pids as $id => $pids) {
                $worker = Tii_Worker::$workers[$id];
                if ($worker->reloadable) {
                    foreach ($pids as $pid) {
                        $reloadablePids[$pid] = $pid;
                    }
                } else {
                    foreach ($pids as $pid) {
                        // Send reload signal to a worker process which reloadable is false.
                        posix_kill($pid, $signo);
                    }
                }
            }

            // Get all pids that are waiting reload.
            $this->pidsToRestart = array_intersect($this->pidsToRestart, $reloadablePids);

            // Reload complete.
            if (empty($this->pidsToRestart)) {
                if (Tii_Worker::$status !== Tii_Worker::STATUS_SHUTDOWN) {
                    Tii_Worker::$status = Tii_Worker::STATUS_RUNNING;
                }
                return;
            }
            // Continue reload.
            $pid = current($this->pidsToRestart);
            // Send reload signal to a worker process.
            posix_kill($pid, $signo);
            // If the process does not exit after $delayKillingTime seconds try to kill it.
            if (!$this->graceful_stop) {
                Tii_Worker_Timer::add($this->delayKillingTime, 'posix_kill', [$pid, SIGKILL], false);
            }
        } // For child processes.
        else {
            reset(Tii_Worker::$workers);
            $worker = current(Tii_Worker::$workers);
            // Try to emit onWorkerReload callback.
            $worker->onWorkerReload();

            if ($worker->reloadable) {
                $this->quit();
            }
        }
    }

    /**
     * Monitor all child processes.
     *
     * @return void
     */
    protected function monitorWorkers()
    {
        Tii_Worker::$status = Tii_Worker::STATUS_RUNNING;

        while (1) {

            pcntl_signal_dispatch();//calls signal handlers for pending signals
            //Suspends execution of the current process until a child has exited, or until a signal is delivered
            $status = 0;
            $pid = pcntl_wait($status, WUNTRACED);

            pcntl_signal_dispatch();//calls signal handlers for pending signals again.
            //if a child has already exited
            if ($pid > 0) {
                // Find out witch worker process exited.
                foreach (Tii_Worker::$pids as $worker_id => $worker_pid_array) {
                    if (isset($worker_pid_array[$pid])) {
                        $worker = Tii_Worker::$workers[$worker_id];
                        // Exit status.
                        if ($status !== 0) {
                            Tii_Logger::debug("worker[" . $worker->name . ":$pid] exit with status $status");
                        }

                        // For Statistics.
                        if (!isset(Tii_Worker::$statistics['worker_exit_info'][$worker_id][$status])) {
                            Tii_Worker::$statistics['worker_exit_info'][$worker_id][$status] = 0;
                        }
                        Tii_Worker::$statistics['worker_exit_info'][$worker_id][$status]++;

                        // Clear process data.
                        unset(Tii_Worker::$pids[$worker_id][$pid]);

                        // Mark id is available.
                        $id = Tii_Worker::getId($worker_id, $pid);
                        Tii_Worker::$ids[$worker_id][$id] = 0;

                        break;
                    }
                }
                // Is still running state then fork a new worker process.
                if (Tii_Worker::$status !== Tii_Worker::STATUS_SHUTDOWN) {
                    Tii_Worker::forks();
                    // If reloading continue.
                    if (isset($this->pidsToRestart[$pid])) {
                        unset($this->pidsToRestart[$pid]);
                        $this->reload();
                    }
                } else {
                    // If shutdown state and all child processes exited then master process exit.
                    if (!$this->getAllPids()) {
                        $this->exitAndClearAll();
                    }
                }
            } else {
                // If shutdown state and all child processes exited then master process exit.
                if (Tii_Worker::$status === Tii_Worker::STATUS_SHUTDOWN && !$this->getAllPids()) {
                    $this->exitAndClearAll();
                }
            }
        }
    }

    /**
     * Exit current process.
     *
     * @return void
     */
    protected function exitAndClearAll()
    {
        foreach (Tii_Worker::$workers as $worker) {
            $socket_name = $worker->socketName;
            if ($worker->transport === 'unix' && $socket_name) {
                list(, $address) = explode(':', $socket_name, 2);
                @unlink($address);
            }
        }
        @unlink($this->pidFile);
        Tii_Logger::debug("Worker[" . basename(Tii_Worker::$startFile) . "] has been stopped");

        if (Tii_Worker::$onMasterStop) {
            call_user_func(Tii_Worker::$onMasterStop);
        }
        exit(0);
    }

    /**
     * dashboard
     *
     * @return void
     */
    protected function dashboard()
    {
        //found max length
        //strlen of [user,worker,listen,processes,status]
        $lengths = ['user' => 4, 'name' => 6, 'socketName' => 6, 'count' => 9, 'status' => 6];
        $workers = Tii_Worker::$workers;
        array_walk($workers, function ($worker) use (&$lengths) {
            foreach ($lengths as $k => $l) {
                $_l = strlen($worker->{$k});
                $lengths[$k] = $_l > $l ? $_l : $l;
            }
        });
        //total length
        $_lengths = 0;
        array_walk($lengths, function (&$length, $key) use (&$_lengths) {
            if ($key != 'status') $length = $length + 2;
            $_lengths += $length;
        });

        //UI
        $h = ' TII WORKER ';
        $l = floor(($_lengths - strlen($h)) / 2);
        printf("\033[1A\n\033[K" . str_pad("", $l, '-') . "\033[47;30m$h\033[0m" . str_pad("", $l, '-') . "\n\033[0m");
        echo 'Tii version:', Tii_Version::VERSION, "   PHP version:", PHP_VERSION, '   Event:', Tii_Worker_Event::getDriverName(), "\n";

        $h = ' WORKERS ';
        $l = floor(($_lengths - strlen($h)) / 2);
        echo str_pad("", $l, '-') . "\033[47;30m$h\033[0m" . str_pad("", $l, '-') . "\n";

        $format = sprintf("%%-%ds%%-%ds%%-%ds%%-%ds%%-%ds\n",
            $lengths['user'], $lengths['name'], $lengths['socketName'], $lengths['count'], $lengths['status']
        );

        echo preg_replace('|\[([^ ]+)( *)\]|U', "\033[47;30m$1\033[0m$2", sprintf(preg_replace('|(%-\d+s)|U', "[$1]", $format), 'user', 'worker', 'listen', 'processes', 'status'));

        foreach ($workers as $worker) {
            echo sprintf($format, $worker->user, $worker->name, $worker->socketName, $worker->count, "\033[32;40m [OK] \033[0m");
        }

        echo str_pad("\n", $_lengths, '-', STR_PAD_LEFT);

        if (Tii_Worker::$daemonize) {
            global $argv;
            $start_file = $argv[0];
            echo "Input \"php $start_file stop\" to quit. Start success.\n";
        } else {
            echo "Press Ctrl-C to quit. Start success.\n";
        }
    }

    /**
     * Init All worker instances.
     *
     * @return void
     */
    protected function initWorkers()
    {
        /** @var $worker Tii_Worker */
        foreach (Tii_Worker::$workers as $worker) {
            // Worker name.
            if (empty($worker->name)) {
                $worker->name = 'none';
            }

            // Get unix user of the worker process.
            if (empty($worker->user)) {
                $worker->user = $this->getCurrentUser();
            } else {
                if (posix_getuid() !== 0 && $worker->user != $this->getCurrentUser()) {
                    Tii_Logger::debug('Warning: You must have the root privileges to change uid and gid.');
                }
            }

            // Listen.
            if (!$worker->reusePort) {
                $worker->listen();
            }
        }
    }

    /**
     * Fork one worker process.
     *
     * @param Tii_Worker $worker
     * @throws Exception
     */
    protected function fork($worker)
    {
        $pid = pcntl_fork();
        // Get available worker id.
        $id = $this->getId($worker->id, 0);
        // For master process.
        if ($pid > 0) {
            Tii_Worker::$pids[$worker->id][$pid] = $pid;
            Tii_Worker::$ids[$worker->id][$id] = $pid;
        } // For child processes.
        elseif (0 === $pid) {
            if ($worker->reusePort) {
                $worker->listen();
            }
            if (Tii_Worker::$status === Tii_Worker::STATUS_STARTING) {
                $this->resetStd();
            }
            Tii_Worker::$pids = [];
            Tii_Worker::$workers = [$worker->id => $worker];
            Tii_Worker_Timer::delAll();
            Tii_Worker::setProcessTitle('Worker: worker process  ' . $worker->name . ' ' . $worker->socketName);
            $worker->setUserAndGroup();
            $worker->id = $id;
            $worker->start();
            exit(250);
        } else {
            throw new Exception("fork failed");
        }
    }

    /**
     * Redirect standard input and output.
     *
     * @throws Exception
     */
    protected function resetStd()
    {
        if (!Tii_Worker::$daemonize) {
            return;
        }
        global $STDOUT, $STDERR;
        $handle = fopen(Tii_Worker::$stdoutFile, "a");
        if ($handle) {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen(Tii_Worker::$stdoutFile, "a");
            $STDERR = fopen(Tii_Worker::$stdoutFile, "a");
        } else {
            throw new Exception('can not open stdoutFile ' . Tii_Worker::$stdoutFile);
        }
    }

    /**
     * Get worker id.
     *
     * @param int $worker_id
     * @param int $pid
     * @return int
     */
    public function getId($worker_id, $pid)
    {
        $id = array_search($pid, Tii_Worker::$ids[$worker_id]);
        if ($id === false) {
            echo "getId fail\n";
        }
        return $id;
    }

    /**
     * Set unix user and group for current process.
     *
     * @return void
     */
    public function setUserAndGroup()
    {
        // Get uid.
        $user_info = posix_getpwnam($this->user);
        if (!$user_info) {
            Tii_Logger::debug("Warning: User {$this->user} not exsits");
            return;
        }
        $uid = $user_info['uid'];
        // Get gid.
        if ($this->group) {
            $group_info = posix_getgrnam($this->group);
            if (!$group_info) {
                Tii_Logger::debug("Warning: Group {$this->group} not exsits");
                return;
            }
            $gid = $group_info['gid'];
        } else {
            $gid = $user_info['gid'];
        }

        // Set uid and gid.
        if ($uid != posix_getuid() || $gid != posix_getgid()) {
            if (!posix_setgid($gid) || !posix_initgroups($user_info['name'], $gid) || !posix_setuid($uid)) {
                Tii_Logger::debug("Warning: change gid or uid fail.");
            }
        }
    }

    /**
     * Get unix user of current porcess.
     *
     * @return string
     */
    protected function getCurrentUser()
    {
        $user_info = posix_getpwuid(posix_getuid());
        return $user_info['name'];
    }

    /**
     * Format status data.
     *
     * @return string
     */
    protected function formatStatusData()
    {
        static $total_request_cache = [];
        $info = @file($this->tempfile, FILE_IGNORE_NEW_LINES);
        if (!$info) {
            return '';
        }
        $status_str = '';
        $current_total_request = [];
        $worker_info = json_decode($info[0], true);
        ksort($worker_info, SORT_NUMERIC);
        unset($info[0]);
        $data_waiting_sort = [];
        $read_process_status = false;
        foreach ($info as $key => $value) {
            if (!$read_process_status) {
                $status_str .= $value . "\n";
                if (preg_match('/^pid.*?memory.*?listening/', $value)) {
                    $read_process_status = true;
                }
                continue;
            }
            if (preg_match('/^[0-9]+/', $value, $pid_math)) {
                $pid = $pid_math[0];
                $data_waiting_sort[$pid] = $value;
                if (preg_match('/^\S+?\s+?\S+?\s+?\S+?\s+?\S+?\s+?\S+?\s+?\S+?\s+?\S+?\s+?(\S+?)\s+?/', $value, $match)) {
                    $current_total_request[$pid] = $match[1];
                }
            }
        }
        foreach ($worker_info as $pid => $info) {
            if (!isset($data_waiting_sort[$pid])) {
                $status_str .= "$pid\t" . str_pad('N/A', 7) . " "
                    . str_pad($info['listen'], 12) . " "
                    . str_pad($info['name'], 12) . " "
                    . str_pad('N/A', 11) . " " . str_pad('N/A', 9) . " "
                    . str_pad('N/A', 7) . " " . str_pad('N/A', 13) . " N/A    [busy] \n";
                continue;
            }
            //$qps = isset($total_request_cache[$pid]) ? $current_total_request[$pid]
            if (!isset($total_request_cache[$pid]) || !isset($current_total_request[$pid])) {
                $qps = 0;
            } else {
                $qps = $current_total_request[$pid] - $total_request_cache[$pid];
            }
            $status_str .= $data_waiting_sort[$pid] . " " . str_pad($qps, 6) . " [idle]\n";
        }
        $total_request_cache = $current_total_request;
        return $status_str;
    }

    /**
     * Write statistics/status data to file
     *
     * @return void
     */
    protected function writeStatisticsToStatusFile()
    {
        //strlen of [worker_name,listening]
        $lengths = ['name' => 11, 'socketName' => 9];
        $workers = Tii_Worker::$workers;
        array_walk($workers, function ($worker) use (&$lengths) {
            foreach ($lengths as $k => $l) {
                $_l = strlen($worker->{$k});
                $lengths[$k] = $_l > $l ? $_l : $l;
            }
        });

        array_walk($lengths, function (&$length) {
            $length = $length + 2;
        });

        //for master process.
        if (Tii_Worker::$pid === posix_getpid()) {
            $all_worker_info = [];
            foreach (Tii_Worker::$pids as $worker_id => $pid_array) {
                /** @var Worker $worker */
                $worker = Tii_Worker::$workers[$worker_id];
                foreach ($pid_array as $pid) {
                    $all_worker_info[$pid] = ['name' => $worker->name, 'listen' => $worker->socketName];
                }
            }

            file_put_contents($this->tempfile, json_encode($all_worker_info) . "\n", FILE_APPEND);

            $usage = Tii::usage();
            file_put_contents($this->tempfile, str_pad('GLOBAL STATUS', 100, "-", STR_PAD_BOTH) . "\n");
            file_put_contents($this->tempfile,
                'Tii version:' . Tii_Version::VERSION . "          PHP version:" . PHP_VERSION . "\n", FILE_APPEND);
            file_put_contents($this->tempfile, 'start time:' . Tii_Time::format('Y-m-d H:i:s',
                    $usage->initialTime) . '   run ' . floor($usage->totalConsumedTime / (24 * 60 * 60)) . ' days ' . floor(($usage->totalConsumedTime % (24 * 60 * 60)) / (60 * 60)) . " hours   \n", FILE_APPEND);
            $load_str = 'load average: ' . implode(", ", $usage->loadavg);
            file_put_contents($this->tempfile,
                str_pad($load_str, 33) . ' event-loop:' . Tii_Worker_Event::getDriverName() . "\n", FILE_APPEND);
            file_put_contents($this->tempfile,
                count(Tii_Worker::$pids) . ' workers       ' . count($this->getAllPids()) . " processes\n",
                FILE_APPEND);
            file_put_contents($this->tempfile,
                str_pad('worker_name', $lengths['name']) . " exit_status     exit_count\n", FILE_APPEND);
            foreach (Tii_Worker::$pids as $worker_id => $worker_pid_array) {
                $worker = Tii_Worker::$workers[$worker_id];
                if (isset(Tii_Worker::$statistics['worker_exit_info'][$worker_id])) {
                    foreach (Tii_Worker::$statistics['worker_exit_info'][$worker_id] as $worker_exit_status => $worker_exit_count) {
                        file_put_contents($this->tempfile,
                            str_pad($worker->name, $lengths['name']) . " " . str_pad($worker_exit_status,
                                16) . " $worker_exit_count\n", FILE_APPEND);
                    }
                } else {
                    file_put_contents($this->tempfile,
                        str_pad($worker->name, $lengths['name']) . " " . str_pad(0, 16) . " 0\n",
                        FILE_APPEND);
                }
            }
            file_put_contents($this->tempfile, str_pad('PROCESS STATUS', 100, "-", STR_PAD_BOTH) . "\n", FILE_APPEND);
            file_put_contents($this->tempfile,
                "pid\tmemory  " . str_pad('listening', $lengths['socketName'])
                . " " . str_pad('worker_name', $lengths['name'])
                . " connections " . str_pad('total_request', 13)
                . " " . str_pad('send_fail', 9)
                . " " . str_pad('throw_exception', 15)
                . " " . str_pad('timers', 8)
                . " " . str_pad('total_request', 13)
                . " qps    status\n", FILE_APPEND);

            chmod($this->tempfile, 0722);

            foreach ($this->getAllPids() as $worker_pid) {
                posix_kill($worker_pid, SIGUSR2);
            }
            return;
        }

        //for child processes.
        /** @var Worker $worker */
        $worker = current(Tii_Worker::$workers);
        $worker_status_str = posix_getpid() . "\t" . str_pad(Tii_Filesystem::format(memory_get_usage(true)),
                7) . " " . str_pad($worker->socketName,
                $lengths['socketName']) . "" . str_pad(($worker->name === $worker->socketName ? 'none' : $worker->name),
                $lengths['name']) . "";
        $worker_status_str .= str_pad(Tii_Worker_Connection::$statistics['connection_count'], 11)
            . " " . str_pad(Tii_Worker_Connection::$statistics['total_request'], 14)
            . " " . str_pad(Tii_Worker_Connection::$statistics['send_fail'], 9)
            . " " . str_pad(Tii_Worker_Connection::$statistics['throw_exception'], 15)
            . " " . str_pad(Tii_Worker::$events->getTimerCount(), 7)
            . " " . str_pad(Tii_Worker_Connection::$statistics['total_request'], 13) . "\n";
        file_put_contents($this->tempfile, $worker_status_str, FILE_APPEND);
    }

    /**
     * Write statistics data to disk.
     *
     * @return void
     */
    protected function writeConnectionsStatisticsToStatusFile()
    {
        $pid = posix_getpid();

        // For master process.
        if (Tii_Worker::$pid === $pid) {
            file_put_contents($this->tempfile, str_pad(" WORKER CONNECTION STATUS ", 180, '-', STR_PAD_BOTH) . "\n", FILE_APPEND);
            file_put_contents($this->tempfile, "PID      Worker          CID       Trans   Protocol        ipv4   ipv6   Recv-Q       Send-Q       Bytes-R      Bytes-W       Status         Local Address          Foreign Address\n", FILE_APPEND);
            chmod($this->tempfile, 0722);
            foreach ($this->getAllPids() as $worker_pid) {
                posix_kill($worker_pid, SIGIO);
            }
            return;
        }

        // For child processes.
        $str = '';
        reset(Tii_Worker::$workers);
        $current_worker = current(Tii_Worker::$workers);
        $default_worker_name = $current_worker->name;

        /** @var Worker $worker */
        foreach (Tii_Worker_Connection_Tcp::$connections as $connection) {
            /** @var Tii_Worker_Connection_Abstract $connection */
            $transport = $connection->transport;
            $ipv4 = $connection->isIpV4() ? ' 1' : ' 0';
            $ipv6 = $connection->isIpV6() ? ' 1' : ' 0';
            $recv_q = Tii_Filesystem::format($connection->getRecvBufferQueueSize());
            $send_q = Tii_Filesystem::format($connection->getSendBufferQueueSize());
            $local_address = trim($connection->getLocalAddress());
            $remote_address = trim($connection->getRemoteAddress());
            $state = $connection->getStatus(false);
            $bytes_read = Tii_Filesystem::format($connection->bytesRead);
            $bytes_written = Tii_Filesystem::format($connection->bytesWritten);
            $id = $connection->id;
            $protocol = $connection->protocol ? $connection->protocol : $connection->transport;
            $pos = strrpos($protocol, '\\');
            if ($pos) {
                $protocol = substr($protocol, $pos + 1);
            }
            if (strlen($protocol) > 15) {
                $protocol = substr($protocol, 0, 13) . '..';
            }
            $worker_name = isset($connection->worker) ? $connection->worker->name : $default_worker_name;
            if (strlen($worker_name) > 14) {
                $worker_name = substr($worker_name, 0, 12) . '..';
            }
            $str .= str_pad($pid, 9) . str_pad($worker_name, 16) . str_pad($id, 10) . str_pad($transport, 8)
                . str_pad($protocol, 16) . str_pad($ipv4, 7) . str_pad($ipv6, 7) . str_pad($recv_q, 13)
                . str_pad($send_q, 13) . str_pad($bytes_read, 13) . str_pad($bytes_written, 13) . ' '
                . str_pad($state, 14) . ' ' . str_pad($local_address, 22) . ' ' . str_pad($remote_address, 22) . "\n";
        }
        if ($str) {
            file_put_contents($this->tempfile, $str, FILE_APPEND);
        }
    }

}