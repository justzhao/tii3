<?php
/**
 * A worker processor without pcntl extension.
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

class Tii_Worker_Processor_WithoutPcntl extends Tii_Worker_Processor_Abstract
{
    /**
     * Processes.
     *
     * @var array
     */
    protected $process_data = [];

    /**
     * The php command to execute
     *
     * @var string
     */
    protected $command = "php";

    /**
     * @var bool
     */
    protected $quietMode = false;

    /**
     * @var bool
     */
    protected $forkMode = false;

    /**
     * The file to be executed
     *
     * @var array
     */
    protected $startFiles = [];

    public function __construct()
    {
        if (false !== strpos(ini_get('disable_functions'), 'proc_open')) {
            exit("Warning: proc_open() has been disabled for security reasons. \r\n");
        }
    }

    public function run()
    {
        Tii_Worker::init(Tii_Worker::$events = Tii_Worker_Event::select());

        $this->parseCommand();
        $this->dashboard();
        $this->forkWorkers();
        $this->monitorWorkers();
    }


    /**
     * Parse command.
     * php start_file.php[ other_start_file.php] [-q]
     *
     * @return void
     */
    protected function parseCommand()
    {
        global $argv;

        $this->command = Tii::valueInArray($_SERVER, '_', 'php');
        $this->quietMode = in_array('-q', $argv); //no printing
        $this->forkMode = in_array('-fork', $argv);

        //Multiple start files open multiple processes
        foreach ($argv as $file) {
            if (is_file($file)) {
                $this->startFiles[$file] = $file;
            }
        }
    }

    /**
     * Display Dashboard
     *
     * @return void
     */
    protected function dashboard()
    {
        if ($this->quietMode || $this->forkMode) {
            return;
        }

        echo "----------------------- TII_WORKER -----------------------------\n";
        echo 'Tii version:' . Tii_Version::VERSION . "          PHP version:".PHP_VERSION." [$this->command]\n";
        echo "Press Ctrl+C to quit.\n";
        echo "------------------------ WORKERS -------------------------------\n";
        echo "worker               listen                              processes status\r\n";
    }

    /**
     * Fork some worker processes.
     *
     * @return void
     */
    public function forkWorkers()
    {
        if ($this->forkMode) {//child
            if (count(Tii_Worker::$workers) > 1) {
                echo "Error: multi workers init in one php file are not support\r\n";
            } elseif (count(Tii_Worker::$workers) <= 0) {
                exit("no worker inited\r\n\r\n");
            }

            reset(Tii_Worker::$workers);
            /** @var Worker $worker */
            $worker = current(Tii_Worker::$workers);

            // Display UI.
            if (!$this->quietMode) {
                echo str_pad($worker->name, 21) . str_pad($worker->socketName, 36) . str_pad($worker->count, 10) . "[ok]\n";
            }

            $worker->listen();
            $worker->run();
        } else {//master
            foreach ($this->startFiles as $start_file) {
                $this->fork($start_file);
            }
        }
    }

    /**
     * Fork one worker process.
     *
     * @param string $start_file
     */
    public function fork($start_file)
    {
        $start_file = realpath($start_file);
        $std_file = Tii_Filesystem::hashfile($start_file, 'worker', '.out.txt');

        //safe command
        $cmd = [];
        $cmd[] = $this->command;
        $cmd[] = escapeshellarg($start_file);
        $this->quietMode && $cmd[] = '-q';
        $cmd[] = '-fork';

        $pipes = [];
        $process = proc_open(implode(" ", $cmd), [
            ['pipe', 'a'], // stdin
            ['file', $std_file, 'w'], // stdout
            ['file', $std_file, 'w'] // stderr
        ], $pipes);

        $std_handler = fopen($std_file, 'a+');
        stream_set_blocking($std_handler, 0);

        if (empty(Tii_Worker::$events)) {
            Tii_Worker::$events = Tii_Worker_Event::select();
            Tii_Worker_Timer::init(Tii_Worker::$events);
        }

        $timer_id = Tii_Worker_Timer::add(1, function () use ($std_handler) {
            echo fread($std_handler, 65535);
        });

        $this->process_data[$start_file] = [$process, $start_file, $timer_id, $std_file];
    }

    /**
     * Monitor all child processes.
     *
     * @return void
     */
    protected function monitorWorkers()
    {
        Tii_Worker_Timer::add(0.5, [$this, 'checkWorkerStatus']);

        Tii_Worker::$events->loop();
    }

    /**
     * check worker status for windows.
     * @return void
     */
    public function checkWorkerStatus()
    {
        foreach ($this->process_data as $process_data) {
            list ($process, $start_file, $timer_id, $std_file) = $process_data;
            $status = proc_get_status($process);
            if (isset($status['running'])) {
                if (!$status['running']) {
                    echo "process $start_file terminated and try to restart\n";
                    Tii_Worker_Timer::delete($timer_id);
                    @proc_close($process);
                    $this->fork($start_file);
                }
            } else {
                echo "proc_get_status fail\n";
            }
        }
    }

}