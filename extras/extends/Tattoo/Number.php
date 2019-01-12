<?php

/**
 * 汉字用零一二三四五六七八九作为基本计数，与阿拉伯数字靠数字偏移位置的权位不一样，中文数字是才有“数字+权位”的方式组成数字，比如百，千，万。
 * 中文数字每个数字后都会跟上一个权位，权位就是数字的量值，相当于阿拉伯数字的数位。
 * 中文计数以万为小节，万以下没有节权，万之上是亿为节权。
 * 中文还有一个特点是多变的“零”，大概总结为三个规则：
 * 1.以10000为小节，小节的结尾即使是0，也不使用“零”。
 * 2.小节内两个非0数字之间要使用“零”。
 * 3.当小节的“千”位是0时，若本小节的前一小节无其他数字，则不用“零”，否则就要用“零”。
 *
 * Class Tattoo_Number
 */
class Tattoo_Number
{
    /**
     * 数字转换为中文
     * @param  integer $num 目标数字
     * @return mixed|string
     */
    public static function number2chinese($num)
    {
        $chiNum = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $chiUni = array('', '十', '百', '千', '万', '亿', '十', '百', '千');

        $chiStr = '';

        $num_str = (string)$num;

        $count = strlen($num_str);
        $last_flag = true; //上一个 是否为0
        $zero_flag = true; //是否第一个
        $temp_num = null; //临时数字

        $chiStr = '';//拼接结果
        if ($count == 2) {//两位数
            $temp_num = $num_str[0];
            $chiStr = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num] . $chiUni[1];
            $temp_num = $num_str[1];
            $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
        } else if ($count > 2) {
            $index = 0;
            for ($i = $count - 1; $i >= 0; $i--) {
                $temp_num = $num_str[$i];
                if ($temp_num == 0) {
                    if (!$zero_flag && !$last_flag) {
                        $chiStr = $chiNum[$temp_num] . $chiStr;
                        $last_flag = true;
                    }
                } else {
                    $chiStr = $chiNum[$temp_num] . $chiUni[$index % 9] . $chiStr;

                    $zero_flag = false;
                    $last_flag = false;
                }
                $index++;
            }
        } else {
            $chiStr = $chiNum[$num_str[0]];
        }
        return $chiStr;
    }

    /**
     * 阿拉伯转中文
     *
     * @param $num
     * @param int $m
     * @return bool|string
     */
    public static function number2Chinese2($num, $m = 1)
    {
        switch ($m) {
            case 0:
                $CNum = array(
                    array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'),
                    array('', '拾', '佰', '仟'),
                    array('', '萬', '億', '萬億')
                );
                break;
            default:
                $CNum = array(
                    array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九'),
                    array('', '十', '百', '千'),
                    array('', '万', '亿', '万亿')
                );
                break;
        }

        if (!is_numeric($num)) {
            return false;
        }

        $flt = '';
        if (is_integer($num)) {
            $num = strval($num);
        } else if (is_numeric($num)) {
            $num = strval($num);
            $rs = explode('.', $num, 2);
            $num = $rs[0];
            $flt = $rs[1];
        }

        $len = strlen($num);
        $num = strrev($num);
        $chinese = '';

        for ($i = 0, $k = 0; $i < $len; $i += 4, $k++) {
            $tmp_str = '';
            $str = strrev(substr($num, $i, 4));
            $str = str_pad($str, 4, '0', STR_PAD_LEFT);
            for ($j = 0; $j < 4; $j++) {
                if ($str{$j} !== '0') {
                    $tmp_str .= $CNum[0][$str{$j}] . $CNum[1][4 - 1 - $j];
                }
            }
            $tmp_str .= $CNum[2][$k];
            $chinese = $tmp_str . $chinese;
            unset($str);
        }


        if ($flt) {
            $str = '';
            for ($i = 0; $i < strlen($flt); $i++) {
                $str .= $CNum[0][$flt{$i}];
            }
            $chinese .= "点{$str}";
        }
        return $chinese;
    }

    /**
     * 中文转阿拉伯
     *
     * @param $str
     * @return int|mixed
     */
    public static function chinese2Number($str)
    {
        $num = 0;
        $bins = array("零", "一", "二", "三", "四", "五", "六", "七", "八", "九", 'a' => "个", 'b' => "十", 'c' => "百", 'd' => "千", 'e' => "万");
        $bits = array('a' => 1, 'b' => 10, 'c' => 100, 'd' => 1000, 'e' => 10000);
        foreach ($bins as $key => $val) {
            if (strpos(" " . $str, $val)) $str = str_replace($val, $key, $str);
        }
        foreach (str_split($str, 2) as $val) {
            $temp = str_split($val, 1);
            if (count($temp) == 1) $temp[1] = "a";
            if (isset($bits[$temp[0]])) {
                $num = $bits[$temp[0]] + (int)$temp[1];
            } else {
                $num += (int)$temp[0] * $bits[$temp[1]];
            }
        }
        return $num;
    }
}