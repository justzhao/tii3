<?php
/**
 * The database connection final class
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
 * Usage: Tii::object("Tii_Dao_Connection", $yourSelfConfig)
 * Recommended to use Tii_Dao which has Tii_Dao_Common_QueryHelper class.
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Connection.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Dao_Connection
{
    private $connection;
    private $config = [//default config
        'dsn' => [//@link http://php.net/manual/en/pdo.drivers.php
            'host' => 'localhost',
            'port' => 3306,
            'dbname'=> 'test',
        ],
        'attr' => [
            PDO::ATTR_PERSISTENT => true,
        ],
        'charset' => 'UTF8',
        'username' => 'root',
        'passwd' => '',
        'heartbeat_time' => 120,
    ];

    private $lastHeartbeatTime = 0;

    public function __construct($config = NULL)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    public function getConnection()
    {
        $this->lastHeartbeatTime || $this->lastHeartbeatTime = time();
        if ($this->connection && (time() - $this->lastHeartbeatTime > $this->config['heartbeat_time'])) {
            $this->lastHeartbeatTime = time();
            $this->connection  = NULL;
        }

        if (!$this->connection) {
            $dsn = [];
            foreach ($this->config['dsn'] as $key => $value) {
                $dsn[] = $key . '=' . $value;
            }
            $dsn = 'mysql:' . implode(';', $dsn);

            $this->connection = new Tii_Dao_Common_PropelPDO($dsn, $this->config['username'], $this->config['passwd']);

            $this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach($this->config['attr'] as $attribute => $value) {
                $this->connection->setAttribute($attribute, $value);
            }

            if (isset($this->config['charset'])) {
                $this->connection->exec('SET NAMES ' . $this->config['charset']);
            }
        }
        return $this->connection;
    }

    public function getSchema()
    {
        return $this->config['dsn']['dbname'];
    }

    public function closeConnection()
    {
        $this->connection = NULL;
    }

    protected function __clone()
    {
        //Clone is not allowed
    }
}