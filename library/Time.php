<?php
/**
 * Package time class, prevent the server time appears to facilitate change of time cannot be modified
 * WARNING: direct use is strictly prohibited PHP comes with the time (), date () function
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
 * @version $Id: Time.php 8991 2018-04-18 14:29:54Z alacner $
 */

final class Tii_Time
{
    /**
     * Set timezone，UTC，RPC
     * @param string $timezone
     */
    public static function timezone($timezone = 'UTC')
    {
        @date_default_timezone_set($timezone);
    }

    /**
     * Return current Unix timestamp with microseconds
     * @return float
     */
    public static function micro()
    {
        return microtime(true);
    }

    /**
     * Return current Unix timestamp with milliseconds
     * @return float
     */
    public static function milli()
    {
        return ceil(self::micro() * 1000);
    }

    /**
     * Returns the current timestamp, defaults to return to the local time, such as the need to be accurate
     * @param bool $online
     * @return int
     */
    public static function now($online = false)
    {
        return $online ? self::online() : self::locale();
    }

    /**
     * This function returns the current timestamp, there might be the error of the second level
     * @see http://tf.nist.gov/tf-cgi/servers.cgi
     * @see http://tycho.usno.navy.mil/ntp.html
     *
     * @return int
     */
    public static function online()
    {
        $servers = Tii::get('tii.time.servers', ["time.nist.gov", "time-d.nist.gov"]);
        foreach ($servers as $server) {
            $fp = @fsockopen($server, 37, $errno, $errstr, 0.5);
            if (!$fp) continue;

            stream_set_timeout($fp, 0, 500);
            $status = stream_get_meta_data($fp);
            if ($status['timed_out']) {
                @fclose($fp);
                continue;
            }
            $data = NULL;
            while (!feof($fp)) {
                $data .= fgets($fp, 128);
            }
            fclose($fp);
            // we have a response...is it valid? (4 char string -> 32 bits)
            if (strlen($data) != 4) continue;
            // time server response is a string - convert to numeric
            $NTPtime = ord($data{0})*pow(256, 3) + ord($data{1})*pow(256, 2) + ord($data{2})*256 + ord($data{3});

            // convert the seconds to the present date & time
            // 2840140800 = Thu, 1 Jan 2060 00:00:00 UTC
            // 631152000  = Mon, 1 Jan 1990 00:00:00 UTC
            return $NTPtime - 2208988800;// - 2840140800 + 631152000 = -2208988800;
        }
        return 0;
    }

    /**
     * Return current Unix timestamp
     * @return int
     */
    public static function locale()
    {
        return time();
    }

    /**
     * Parse about any English textual datetime description into a Unix timestamp
     * @param mixed $time The string to parse.
     * @param mixed $now Is used as a base for the calculation of relative dates.
     * @return int
     */
    public static function totime($time = NULL, $now = NULL)
    {
        if (empty($time)) return self::now();
        if (is_numeric($time)) return $time;
        if (empty($now)) return strtotime($time);
        if (is_numeric($now)) return strtotime($time, $now);
        return strtotime($time, self::totime($now));
    }

    /**
     * Use the `date' format
     * @see totime
     *
     * @param string $format Default：Y-m-d H:i:s
     * @param NULL $time
     * @param NULL $now
     *
     * @example
     * ::format('Y-m-d');//The current timestamp
     * ::format('Y-m-d', 1234567890);//A time stamp
     * ::format('Y-m-d', '+5 days');//Five days later timestamp
     * ::format('Y-m-d', '+5 days', 1234567890); // From 1234567890 seconds to augment the timestamp of 5 days
     * ::format('Y-m-d', '+5 days', '+5 days'); // total +10 days timestamp
     * ::format('t', 'Y-m');//days in month
     *
     * @return bool|string
     */
    public static function format($format = 'Y-m-d H:i:s', $time = NULL, $now = NULL)
    {
        return date($format, self::totime($time, $now));
    }

    /**
     * Returns the formatted timestamp
     * @example
     * ::formatted('Y-m-d'); // 2014-09-17 13:14:00 => 1410930840
     *
     * @param $format
     * @param NULL $time
     * @param NULL $now
     * @return int
     */
    public static function formatted($format, $time = NULL, $now = NULL)
    {
        return self::totime(self::format($format, $time, $now));
    }

    /**
     * Two time intervals, time2 - time1
     *
     * @param $time1
     * @param NULL $time2, default was now
     * @return int
     */
    public static function interval($time1, $time2 = NULL)
    {
        return self::totime($time2) - self::totime($time1);
    }

    /**
     * Check Two time intervals whether in seconds range(include), time2 - time1
     *
     * @param $time1
     * @param $forward
     * @param int $afterward
     * @param NULL $time2, default was now
     * @return bool
     */
    public static function inSeconds($time1, $forward = -60, $afterward = 60, $time2 = NULL)
    {
        return ($interval = self::interval($time1, $time2)) >= $forward && $interval <= $afterward;
    }

    /**
     * Returns a human readable elapsed time
     * @param  float $microtime
     * @param  string  $format   The format to display (printf format)
     * @param int $round
     * @return string
     */
    public static function readable($microtime, $format = '%.3f%s', $round = 3)
    {
        if ($microtime >= 1) {
            $unit = 's';
            $time = round($microtime, $round);
        } else {
            $unit = 'ms';
            $time = round($microtime * 1000);
            $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
        }

        return sprintf($format, $time, Tii::lang($unit));
    }

    /**
     * Finds next execution timestamp parse in crontab syntax.
     * Either-or for specifying both a day-of-week and a day-of-month.
     *
     * 30 21 * * * => every 21:30
     * 45 4 1,10,22 * * => every 4:45 at 1,10,22 days a month
     * 10 1 * * 6,0 => every saturday and sunday 1:10
     * 0,30 18-23 * * * => every 18:00 to 23:00 between every 30 minutes
     * 0 23 * * 6 => every saturday 11:00 pm
     * * *\/1 * * * => every hour
     * * 23-7/1 * * * => at 11 pm to 7 am, between every two hours
     *
     * @param string $cron:
     *
     *      0     1    2    3    4
     *      *     *    *    *    *
     *      -     -    -    -    -
     *      |     |    |    |    |
     *      |     |    |    |    +----- day of week (0 - 6) (Sunday=0)
     *      |     |    |    +------- month (1 - 12)
     *      |     |    +--------- day of month (1 - 31)
     *      |     +----------- hour (0 - 23)
     *      +------------- min (0 - 59)
     * @param mixed $time
     * @param mixed $now
     * @return int|null
     */
    public static function nexttime($cron = '* * * * *', $time = NULL, $now = NULL)
    {
        $cron = preg_split("/[\s]+/i", trim($cron));

        switch(count($cron)) {
            case 5:
                break;
            case 6:
            case 7:
                array_shift($cron);
                break;
            default:
                return NULL;
        }

        $caliper = self::formatted('Y-m-d H:i', $time, $now);
        $deadline = 60*60*24*366;// limited to time()+366 - no need to check more than 1year ahead

        $jArr = [];
        $ranges = [
            'i' =>self::_parseCronNumbers($cron[0], 0, 59),// min
            'G' =>self::_parseCronNumbers($cron[1], 0, 23),// hour
            //'j' =>self::_parseCronNumbers($cron[2], 1, 31),// day
            'n' =>self::_parseCronNumbers($cron[3], 1, 12),// month
            'w' =>self::_parseCronNumbers($cron[4], 0, 6),// week
        ];

        while($caliper > $deadline) {
            $caliper = $caliper + 60;
            //Y[4 digits],n[1-12],j[1-31],G[0-23],i[0-59],s[0-59],w[0-6],z[0-365],W[week number of year]
            list($Y, $n, $j, $G, $i, $w) = array_map(function($i){return intval($i);}, explode(',', date("Y,n,j,G,i,w", $caliper)));
            $Yn = $Y.'-'.$n;
            isset($jArr[$Yn]) || $jArr[$Yn] = self::_parseCronNumbers($cron[2], 1, self::format('t', $Yn));
            $ranges['j'] = $jArr[$Yn];
            if (isset($ranges['i'][$i], $ranges['G'][$G], $ranges['n'][$n])) {
                if (isset($ranges['w'][$w]) || isset($ranges['j'][$j]))
                return $caliper;
            }
        }
        return NULL;
    }

    /**
     * get a single cron style notation and parse it into numeric value
     *
     * @param string $s cron string element
     * @param int $min minimum possible value
     * @param int $max maximum possible value
     * @return array parsed number
     */
    protected static function _parseCronNumbers($s, $min, $max)
    {
        if ($s == '?') return [];

        $result = [];

        $v = explode(',', $s);
        foreach($v as $vv) {
            $vvv  = explode('/', $vv);
            $step = empty($vvv[1]) ? 1 : $vvv[1];
            $vvvv = explode('-', $vvv[0]);
            $_min = intval(count($vvvv) == 2 ? $vvvv[0] : ($vvv[0] == '*' ? $min : $vvv[0]));
            $_max = intval(count($vvvv) == 2 ? $vvvv[1] : ($vvv[0] == '*' ? $max : $vvv[0]));
            if ($_min > $_max) {
                for ($i = $min; $i <= $_max; $i += $step) {
                    $result[$i] = intval($i);
                }
                for ($i = $_min; $i <= $max; $i += $step) {
                    $result[$i] = intval($i);
                }
            } else {
                for ($i = $_min; $i <= $_max; $i += $step) {
                    $result[$i] = intval($i);
                }
            }
        }
        ksort($result);
        return $result;
    }
}