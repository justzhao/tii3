<?php
/**
 * Dao protocol session of abstract classes
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

class Tii_Application_SessionHandler_Dao implements SessionHandlerInterface
{
    private $maxlifetime = 0;

    private $dao;
    private $table;
    private $fields = [
        'session_id' => 'id', //varchar(32+), unique
        'session_data' => 'data', //blob
        'session_expired' => 'expired', //int
    ];

    public function __construct($dao = NULL, $table = 'session', $fields = [])
    {
        $this->dao = ($dao instanceof Tii_Dao) ? $dao : Tii::object('Tii_Dao', $dao);
        $this->table = $table;
        $this->fields = array_merge($this->fields, $fields);
        $this->maxlifetime = ini_get('session.gc_maxlifetime');
    }

    public function open($save_path, $session_id)
    {
        return $this->dao->isTableExist($this->table);
    }

    public function close()
    {
        return true;
    }

    public function read($session_id)
    {
        $data = $this->dao->get($this->table, [
            'columns' => $this->fields['session_data'],
            'selection' => sprintf('%s=? and %s>?', $this->fields['session_id'], $this->fields['session_expired']),
            'selection_args' => [$session_id, time()],
        ]);

        if ($data) return $data[$this->fields['session_data']];
        else return false;
    }

    public function write($session_id, $session_data)
    {
        $data = [
            $this->fields['session_id'] => $session_id,
            $this->fields['session_data'] => $session_data,
            $this->fields['session_expired'] => time() + $this->maxlifetime,
        ];
        return $this->dao->duplicate($this->table, $data, $data);
    }

    public function destroy($session_id)
    {
        return $this->dao->delete($this->table, sprintf('%s=?', $this->fields['session_id']), [$session_id]);
    }

    public function gc($maxlifetime)
    {
        return $this->dao->delete($this->table, sprintf('%s<?', $this->fields['session_expired']), [time()]);
    }
}