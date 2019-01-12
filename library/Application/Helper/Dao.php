<?php
/**
 * Dao Query Helper
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

class Tii_Application_Helper_Dao extends Tii_Application_Abstract
{
    /**
     * @param Tii_Dao $dao
     * @param $table
     * @param array $params
     * @return array
     */
    public function queryService(Tii_Dao $dao, $table, array $params = [])
    {
        if ($selection = Tii::valueInArray($params, 'selection')) {
            $selectionArgs = Tii::valueInArray($params, 'selection_args');
        } else {
            $selectionArgs = NULL;
        }

        $page = Tii::valueInArray($params, 'page', 1);
        $perpage = Tii::valueInArray($params, 'perpage', 200);

        $total = Tii::valueInArray($params, 'total');
        is_numeric($total) || $total = $this->getSimpleTotalNumber($dao, $table, [
            'columns' => Tii::valueInArray($params, 'total_columns', "count(*) as c"),
            'selection' => Tii::valueInArray($params, 'selection'),
            'selection_args' => $selectionArgs,
            'group_by' => Tii::valueInArray($params, 'group_by'),
            'having' => Tii::valueInArray($params, 'having'),
        ]);

        $output = [
            'page' => $page,
            'perpage' => $perpage,
            'total' => $total,
            'offset' => Tii_Dao_Common_QueryHelper::getPageOffset($page, $perpage, $total),
        ];

        $option = [
            'columns' => Tii::valueInArray($params, 'columns', "*"),
            'selection' => Tii::valueInArray($params, 'selection'),
            'selection_args' => $selectionArgs,
            'group_by' => Tii::valueInArray($params, 'group_by'),
            'having' => Tii::valueInArray($params, 'having'),
            'order_by' => Tii::valueInArray($params, 'order_by'),
            'limit' => $output['perpage'],
            'offset' => $output['offset'],
        ];

        $output['items'] = $this->simpleQuery($dao, $table, $option);

        return $output;
    }

    /**
     * @param Tii_Dao $dao
     * @param $table
     * @return array
     */
    public function simpleQueryService(Tii_Dao $dao, $table)
    {
        return $this->queryService($dao, $table, $this->getRequests());
    }

    /**
     * 查询$table的数据
     *
     * @param Tii_Dao $dao
     * @param $table
     * @param array $option [
     * 'columns' => '',
     * 'selection' => 'xxx=?',
     * 'selection_args' => ['val'],
     * 'group_by' => 'some field',
     * 'having' => '',
     * 'order_by' => '',
     * 'limit' => 10,
     * 'offset' => '',//Tii_Dao_Common_QueryHelper::getPageOffset($page, $ppp, $totalnum)
     * ];
     * @return array|multitype
     */
    public function simpleQuery(Tii_Dao $dao, $table, array $option)
    {
        return $dao->getQueryHelper()->query(
            $table,
            $option
        );
    }

    /**
     * The amount of data query $table
     *
     * @param Tii_Dao $dao
     * @param $table
     * @param array $option [
     * 'selection' => 'xxx=?',
     * 'selection_args' => ['val'],
     * 'group_by' => 'some field',
     * 'having' => '',
     * ];
     * @return int
     */
    public function getSimpleTotalNumber(Tii_Dao $dao, $table, array $option)
    {
        unset($option['order_by'],$option['limit'],$option['offset'],$option['columns']);

        $option['columns'] = 'count(*)';

        isset($option['group_by']) && $option['is_group_by_count'] = true;

        return intval($dao->getQueryHelper()->getColumn(
            $table,
            $option
        ));
    }
} 