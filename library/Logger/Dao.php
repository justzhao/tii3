<?php
/**
 * logging with dao mode
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
 * @version $Id: Dao.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Logger_Dao extends Tii_Logger_Abstract
{
    private $dao;
    private $table;
    private $fields = [
        'id' => 'id', //bigint,AUTO_INCREMENT
        'priority' => 'priority', //tinyint
        'message' => 'message', //varchar(255)
        'extras' => 'extras', //LONGTEXT
        'gmt_created' => 'gmt_created', //datetime Y-m-d H:i:s
    ];

    public function __construct($dao = NULL, $table = 'logger', $fields = [])
    {
        parent::__construct();
        $this->dao = ($dao instanceof Tii_Dao) ? $dao : Tii::object('Tii_Dao', $dao);
        $this->table = $table;
        $this->fields = array_merge($this->fields, $fields);
    }

    public function doLog($message, $priority = Tii_Logger_Constant::ERR, $extras = NULL)
    {
        return $this->dao->insert($this->table, [
            $this->fields['priority'] => $priority,
            $this->fields['message'] => strval($message),
            $this->fields['extras'] => json_encode($extras),
            $this->fields['gmt_created'] => Tii_Time::format(),
        ]);
    }
}