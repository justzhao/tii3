<?php
/**
 * Database final class
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
 * Usage: Tii::object("Tii_Dao"[, $yourSelfConfig]);
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Dao.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Dao
{
    private $connection;

    /**
     * If $config is string it will get the config at tii.database.*, otherwise it just itself.
     *
     * @param NULL $config
     */
    public function __construct($config = NULL)
    {
        is_array($config) || $config = Tii::get("tii.database." . Tii::value($config, 'default'));
        $this->connection = new Tii_Dao_Connection($config);
    }

    /**
     * @param bool $reuse
     * @return Tii_Dao_Common_PropelPDO
     */
    public function getConnection($reuse = true)
    {
        $reuse || $this->connection->closeConnection();
        return $this->connection->getConnection();
    }

    /**
     * @return Tii_Dao_Common_QueryHelper
     */
    public function getQueryHelper()
    {
        return Tii::object('Tii_Dao_Common_QueryHelper#' . $this->connection->getSchema(), $this->getConnection(), $this->connection->getSchema());
    }

    /**
     * Magic methods
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->getQueryHelper(), $name])) {
            return call_user_func_array([$this->getQueryHelper(), $name], $arguments);
        }
    }
}