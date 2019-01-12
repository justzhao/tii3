<?php
/**
 * The program guide base entrance
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
 * WARNING: To prevent naming conflicts, please do not use `Tii' started, GOOD LUCK!!!
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Bootstrap.php 9139 2019-01-12 03:51:43Z alacner $
 */

error_reporting(0);

version_compare(PHP_VERSION, '5.4.0', '>=') || die('require PHP > 5.4.0 !');
ini_set('magic_quotes_runtime', 0);

define('TII_DIRECTORY', __DIR__);
defined('TII_SAPI') || define('TII_SAPI', PHP_SAPI);
defined('TII_PROCESSOR') || define('TII_PROCESSOR',
    'Tii_Application_Processor_' . ucfirst(in_array(TII_SAPI, ['cli', 'mock']) ? TII_SAPI : 'http')
);

// Reset opcache.
if (function_exists('opcache_reset')) {
    opcache_reset();
}

/**
 * Common function
 */
if (!function_exists('boolval')) {
    function boolval($val)
    {
        return (bool)$val;
    }
}//PHP 5 >= 5.5.0

/**
 * Class Tii
 */
final class Tii
{
    private static $initialTime = 0;
    private static $previousTime = 0;

    /**
     * Concat and filter class name
     *
     * @return string
     */
    public static function className()
    {
        return ucwords(implode('_', func_get_args()), '_');
    }

    /**
     * usage
     *
     * @param $less
     * @return object
     */
    public static function usage($less = false)
    {
        self::$initialTime || self::$initialTime = microtime(true);
        self::$previousTime || self::$previousTime = self::$initialTime;

        $presentTime = microtime(true);
        $consumedTime = $presentTime - self::$previousTime;
        self::$previousTime = $presentTime;

        $usage = new stdClass();
        $usage->initialTime = self::$initialTime;
        $usage->presentTime = $presentTime;
        $usage->consumedTime = $consumedTime;
        $usage->totalConsumedTime = ($presentTime - self::$initialTime);
        $usage->memory = memory_get_usage(true);

        if ($less) return $usage;

        $usage->memoryPeak = memory_get_peak_usage(true);
        $usage->loadavg = function_exists('sys_getloadavg') ? array_map('round', sys_getloadavg(), [2]) : ['-', '-', '-'];

        return $usage;
    }

    /**
     * Call any method ignore accessible, Try call_user_func* first.
     *
     * @see call_user_func_array
     *
     * @param callable $function
     * @param array $paramArr
     *
     * @return mixed
     */
    public static function call($function, $paramArr = [])
    {
        if (is_array($function)) {
            try {
                list($that, $nameName) = $function;
                $clazz = new ReflectionClass($that);
                $method = $clazz->getMethod($nameName);
                $method->setAccessible(true);
                return $method->invokeArgs($that, $paramArr);
            } catch (Exception $e) {
                //ignore
            }
        }
        return call_user_func_array($function, $paramArr);
    }

    /**
     * Create a singleton object
     * args:
     * 1) className[, arg1[,...]]
     * 2) [className[, arg1[,...]]] -- first arg is a array like function argument...
     * The className begin with @ to delegate class, and define custom token begin with #
     */
    public static function object()
    {
        static $objects = [];

        $args = func_get_args();
        $args || die(Tii::lang("%s: illegal arguments", __METHOD__));

        $token = $class = array_shift($args);
        if (is_array($class)) return call_user_func_array('static::object', $class);
        if (is_object($class)) return $class;

        $pos = strpos($class, '#');
        if ($pos === false) {
            $args && $token .= '#' . Tii_Math::toGuidString($args);
        } else {
            $class = substr($class, 0, $pos);
        }

        if (isset($objects[$token])) return $objects[$token];

        $isDelegateMode = false;
        if ($class{0} == '@') {
            $isDelegateMode = true;
            $class = substr($class, 1);
        }

        if ($args) {
            $ref = new ReflectionClass($class);
            $instance = $ref->newInstanceArgs($args);
        } else {
            $instance = new $class;
        }

        $objects[$token] = $isDelegateMode ? new Tii_Delegate($instance) : $instance;

        return $objects[$token];
    }

    /**
     * Create a singleton object
     *
     * className[, [arg1[,...]]]
     *
     * @see static::object
     * @param $className
     * @param $arguments
     * @return mixed|string
     */
    public static function objective($className, $arguments)
    {
        array_unshift($arguments, $className);
        return self::object($arguments);
    }

    /**
     * cache data like buffer
     * args: key, func[, arg1[, arg2[,...]]]
     *
     * @param $key
     * @param $func
     * @return mixed
     */
    public static function buffer($key, $func)
    {
        static $cached = [];

        $args = func_get_args();
        $key = array_shift($args);
        $func = array_shift($args);

        isset($cached[$key]) || $cached[$key] = call_user_func_array($func, $args);
        return $cached[$key];
    }

    /**
     * Get Require File
     * WARNING: Not verify whether access to the parent directory.
     *
     * @param string $type
     * @param string $module
     * @param string $filename
     * @param string $ext don't contain dot
     * @param string $partition
     * @param $directory
     * @return string
     */
    public static function filename($type, $module, $filename = '', $ext = '', $partition = '', $directory = NULL)
    {
        $directory || $directory = Tii::get('tii.application.directory');
        empty($ext) || $ext = "." . $ext;

        return Tii_Event::filter('tii.filename', is_array($directory) ? Tii_Filesystem::concat(
            self::valueInArray($directory, $type),
            $module,
            $partition,
            $filename . $ext
        ) : Tii_Filesystem::concat(
            $directory,
            $module,
            $type,
            $partition,
            $filename . $ext
        ), $type, $module, $filename, $ext, $partition);
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param $array
     * @param $key
     * @param $value
     * @param $delimiter
     * @return mixed
     */
    public static function setter(array &$array, $key, $value, $delimiter = '.')
    {
        if (is_null($key)) return $array = $value;
        $keys = explode($delimiter, $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array =& $array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param $array
     * @param $key
     * @param mixed $default
     * @param $delimiter
     * @return mixed
     */
    public static function getter($array, $key, $default = NULL, $delimiter = '.')
    {
        if (is_null($key)) return $array;
        if (isset($array[$key])) return $array[$key];
        list ($segment, $key) = array_pad(explode($delimiter, $key, 2), 2, NULL);
        if (!isset($array[$segment]) || !$key || !is_array($array[$segment])) return $default;
        if (isset($array[$segment][$key])) return $array[$segment][$key];
        return static::getter($array[$segment], $key, $default);
    }

    /**
     * Replace the variables in the string
     *
     * @param string $code like: "{0}_st_{2.id}d{1.2}{2.data.name}"
     * @param array $array like: [1, [2,3,4], ['id' => 2, 'data' => ['name' => 'name']]];
     * @return string like: 1_st_2d4name
     */
    public static function render($code, array $array = [])
    {
        preg_match_all("|\{([^{}]+)\}|iUs", $code, $vars);

        if (empty($vars)) return $code;

        $rs = [];
        foreach ($vars[1] as $v) {
            $r = self::getter($array, $v);
            $rs[] = is_scalar($r) ? $r : serialize($r);
        }

        return str_replace($vars[0], $rs, $code);
    }

    /**
     * Validator make
     *
     * @param array $data {key:value}
     * @param array $rules {key1:'rule1', key2:{'rule1', 'rule2'=>{arg1,arg2...}})}
     * @param array $alias {key1:'AliasName'}
     * @param array $messages {rule1:'{0} blah...', rule2:'{0}...{1.2}'}
     * @param bool $throw
     * @return array
     * @throws Tii_Exception
     */
    public static function validator($data = [], $rules = [], $alias = [], $messages = [], $throw = true)
    {
        static $validators = NULL;//{name:callable}

        if (is_null($validators)) {
            $validators = Tii_Event::filter('tii.validators', Tii::get("tii.validators", []));
            $validators = array_merge($validators, [
                'required' => function ($arr, $k) {
                    return isset($arr[$k]);
                },
                'not_empty' => function ($arr, $k) {
                    $value = self::valueInArray($arr, $k);
                    if (is_object($value) && $value instanceof ArrayObject) {
                        $value = $value->getArrayCopy();// Get the array from the ArrayObject
                    }
                    return !in_array($value, [NULL, false, '', []], true);
                },
                'regex' => function ($arr, $k, $expression, $message) {
                    return preg_match($expression, strval($arr[$k]));
                },
                'equals' => function ($arr, $k, $required) {
                    return ($arr[$k] === $required);
                },
                'date' => function ($arr, $k) {
                    return (strtotime($arr[$k]) !== false);
                },
            ]);
        }

        $failedMessages = [];

        if (is_array($data) && is_array($rules)) {
            foreach ($rules as $k => $rs) {
                is_array($rs) || $rs = [$rs];//Compatible with leaflets value only

                foreach ($rs as $r => $v) {
                    if (is_numeric($r)) {//Rules for digital, for the value
                        $r = $v;
                        $v = [];
                    }

                    if (!isset($validators[$r])) {//The rules of the validator does not exist
                        if (function_exists($r)) {
                            $validators[$r] = function ($data, $key) use ($r, $v) {//Sugar
                                array_unshift($v, $data[$key]);
                                return call_user_func_array($r, $v);
                            };
                        } else {
                            $failedMessages[] = Tii::lang("validator `%s' not found", $r);
                            continue;
                        }
                    }

                    array_unshift($v, $data, $k);

                    $isBreak = false;
                    try {
                        if (!call_user_func_array($validators[$r], $v)) {
                            $isBreak = true;
                            $render = $messages[$r] ?: Tii::get(
                                Tii_Config::_lang("tii.validator.messages.$r"),
                                Tii::lang("{0} didn't pass the validator `%s'", $r)
                            );
                        }
                    } catch (Exception $e) {
                        $isBreak = true;
                        $render = $e->getMessage();
                    }

                    if ($isBreak) {
                        $failedMessages[$k] = self::render(
                            $render,
                            [$alias[$k] ?: Tii::get(Tii_Config::_lang("tii.validator.alias.$k"), $k), $v]
                        );
                        break;
                    }
                }
            }
        } else {
            $failedMessages[] = Tii::lang("validation data,rules must be an array");
        }

        if ($throw && count($failedMessages)) {
            throw new Tii_Exception(implode(";", $failedMessages));
        }

        return $failedMessages;
    }

    /**
     * Get value
     *
     * @param $value
     * @param mixed $default
     * @return mixed
     */
    public static function value($value, $default = NULL)
    {
        $args = func_get_args();

        $value = array_shift($args);
        $default = array_shift($args);

        return $value ?: (is_callable($default) ? call_user_func_array($default, $args) : $default);
    }

    /**
     * According to the key for an array of values
     *
     * @param $array
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function valueInArray($array, $key, $default = NULL)
    {
        $args = func_get_args();

        $array = array_shift($args);
        $key = array_shift($args);
        $default = array_shift($args);

        return isset($array[$key]) ? $array[$key] : (is_callable($default) ? call_user_func_array($default, $args) : $default);
    }

    /**
     * Comparison whether $a and $b are equal
     *
     * @param $a
     * @param $b
     * @param bool $identical the same type
     * @return bool
     */
    public static function equals($a, $b, $identical = true)
    {
        return $identical ? $a === $b : $a == $b;
    }

    /**
     * Prepend something to the array without the keys being reindexed and/or need to prepend a key value pair.
     * @see array_unshift
     *
     * @param $array
     * @param $key
     * @param $value
     * @return int
     */
    public static function unshiftkv(array &$array, $key, $value)
    {
        $array = array_reverse($array, true);
        $array[$key] = $value;
        $array = array_reverse($array, true);
        return count($array);
    }

    /**
     * Pop array until got a value
     *
     * @param $arr
     * @param $shuffle
     * @return mixed
     */
    public static function pop($arr, $shuffle = false)
    {
        $pop = NULL;
        $shuffle && shuffle($arr);
        while (is_array($arr) && $arr && (!$pop = array_pop($arr))) ;
        return $pop;
    }

    /**
     * Combine a array from the key field and the value field in array
     * 1) $valueField = NULL, $keyField = NULL
     * ['key' => [val,val,...], 'key2' => [val2,val2,...],...] to [[key=>val,key2=>val2,...],[key=>val,key2=>val2,...]...]
     * 2) $valueField = valueField, $keyField = keyField
     * [{keyField:key,valueField:value,foo:bar},{keyField:key,valueField:value}...] to {key:value,key:value,...}
     * 3) $valueField = valueField, $keyField = NULL
     * [{valueField:value,foo:bar},{valueField:value}...] to [value,value,...]
     * 4) $valueField = NULL, $keyField = keyField
     * [{keyField:key,foo:bar},{keyField:key,...}...] to {key:{keyField:key,foo:bar},key:{keyField:key,...},...}
     *
     * @param array $array
     * @param $valueField
     * @param $keyField
     * @return array
     */
    public static function combine(array $array, $valueField = NULL, $keyField = NULL)
    {
        $newArray = [];

        if (empty($array)) return $newArray;

        if ($valueField || $keyField) {
            array_map($keyField ? function ($arr) use (&$newArray, $keyField, $valueField) {
                $arr = (array)$arr;
                isset($arr[$keyField]) && $newArray[$arr[$keyField]] = $valueField ? $arr[$valueField] : $arr;
            } : function ($arr) use (&$newArray, $valueField) {
                $arr = (array)$arr;
                $newArray[] = $arr[$valueField];
            }, $array);
        } else {
            $keys = array_keys($array);
            $key = array_pop($keys);
            foreach ($array[$key] as $k => $v) {
                $newArray[$k][$key] = $v;
                foreach ($keys as $_key) {
                    $newArray[$k][$_key] = $array[$_key][$k];
                }
            }
        }

        return $newArray;
    }

    /**
     * Can be used in the key array size or the value array size are not fixed.
     *
     * Arguments: [val1, val2, val...], key1, key2, key...
     * @return array [key1:val1, key2:val2, key:val, ...]
     */
    public static function combiner()
    {
        $keys = func_get_args();
        $values = array_shift($keys);

        $keySize = count($keys);
        $valueSize = count($values);

        if ($keySize > $valueSize) {
            $keys = array_slice($keys, 0, $valueSize);
        } else if ($keySize < $valueSize) {
            $values = array_slice($values, 0, $keySize);
        }

        return array_combine($keys, $values);
    }

    /**
     * Separate a array to the key field and the value field in array
     * {key:value,key:value,...} to [{keyField:key,valueField:value},{keyField:key,valueField:value}...]
     *
     * @param array $array
     * @param $keyField
     * @param $valueField
     * @return array
     */
    public static function separate(array $array, $keyField, $valueField)
    {
        $newArray = [];
        array_walk($array, function ($value, $key) use (&$newArray, $keyField, $valueField) {
            $newArray[] = [$keyField => $key, $valueField => $value];
        });
        return $newArray;
    }

    /**
     * Conversion constants
     *
     * class `ClassName' have some constant like:
     * const CONST_A = 0;
     * const CONST_B = 1;
     * const CONST_C = 2;
     *
     * Usage:
     * constants('ClassName', '|^CONST_|')
     * => [[0 => CONST_A, 1 => CONST_B, 2 => CONST_C], [CONST_A => 0, CONST_B => 1, CONST_C => 2]]
     *
     * @param $className if NULL then get constants form get_defined_constants()
     * @param string $pattern The pattern to search for, as a string. [see preg_match]
     * @return array
     */
    public static function _constants($className = NULL, $pattern = NULL)
    {
        static $map;

        if (!isset($map[$className][$pattern])) {

            if ($className) {
                $r = new ReflectionClass($className);
                $constants = $r->getConstants();
            } else {
                $constants = get_defined_constants();
            }

            if ($pattern) {
                $_constants = [];
                foreach ($constants as $k => $v) {//array_filter flag was unsupported under 5.6.0
                    if ($pattern && !preg_match($pattern, $k)) continue;
                    $_constants[$k] = $v;
                }
                $constants = $_constants;
            }
            $map[$className][$pattern] = [$constants, array_flip($constants)];
        }

        return $map[$className][$pattern];
    }

    /**
     * Conversion constants
     *
     * class `ClassName' have some constant like:
     * const CONST_A = 0;
     * const CONST_B = 1;
     * const CONST_C = 2;
     *
     * Usage:
     * constants('ClassName', '|^CONST_|') => [0 => CONST_A, 1 => CONST_B, 2 => CONST_C]
     * constants('ClassName', '|^CONST_|', false) => [CONST_A => 0, CONST_B => 1, CONST_C => 2]
     *
     * @param $className
     * @param string $pattern The pattern to search for, as a string. [see preg_match]
     * @param bool $flip
     * @return array
     */
    public static function constants($className = NULL, $pattern = NULL, $flip = true)
    {
        return Tii::valueInArray(self::_constants($className, $pattern) , intval($flip), []);
    }

    /**
     * Get properties from class
     *
     * @param $className
     * @param $pattern
     * @return array
     */
    public static function properties($className = NULL, $pattern = NULL)
    {
        static $map;

        if (empty($className)) return [];

        if (!isset($map[$className][$pattern])) {

            $r = new ReflectionClass($className);
            $properties = self::combine($r->getProperties(), 'name');

            if ($pattern) {
                $_properties = [];
                foreach ($properties as $property) {//array_filter flag was unsupported under 5.6.0
                    if ($pattern && !preg_match($pattern, $property)) continue;
                    $_properties[] = $property;
                }
                $properties = $_properties;
            }
            $map[$className][$pattern] = $properties;
        }

        return $map[$className][$pattern];
    }

    /**
     * To look for in the array is of value to a new array
     * 1) array,[field1,field2],field3...) -- isset
     * 2) filter_mode,array,[field1,field2],field3...)
     * 3) function($arr, $k){return [bool];},array,[field1,field2],field3...)
     * The first letter:
     * # => intval
     * ! => boolval
     * . => floatval
     * * => strval
     * > => json_encode
     * < => json_decode
     * + => serialize,
     * - => unserialize
     *
     * @return array
     */
    public static function filter()
    {
        static $funcs;

        $funcs || $funcs = [
            '#' => 'intval', '!' => 'boolval', '.' => 'floatval', '*' => 'strval',
            '>' => 'json_encode', '<' => function ($value) {
                return json_decode($value, true);
            },
            '+' => 'serialize', '-' => 'unserialize',
        ];

        $keys = func_get_args();
        $filter = array_shift($keys);

        if (is_array($filter)) {//mode (1)
            $array = $filter;
            $filter = "isset";
        } else {
            $array = array_shift($keys);
        }

        if (!is_array($array)) return [];//the target is not an array

        if (!is_callable($filter)) {//mode (2)
            switch ($filter) {
                case 'not_empty':
                    $filter = function ($array, $key) {
                        return !empty($array[$key]);
                    };
                    break;
                default:
                    $filter = function ($array, $key) {
                        return isset($array[$key]);
                    };
            }
        }// else mode (3)

        $_array = [];
        foreach ($keys as $_keys) {
            is_array($_keys) || $_keys = [$_keys];
            foreach ($_keys as $key) {
                if (isset($funcs[$key{0}])) {
                    $func = $funcs[$key{0}];
                    $key = substr($key, 1);

                    if (call_user_func($filter, $array, $key)) {
                        $_array[$key] = call_user_func($func, $array[$key]);
                    }
                } else {
                    if (call_user_func($filter, $array, $key)) {
                        $_array[$key] = $array[$key];
                    }
                }
            }
        }
        return $_array;
    }

    /**
     * Execute local machine command
     * WARNING: Use the exec function here
     *
     * @see sprintf
     * @return object [succeed,command,output,duration]
     */
    public static function exec()
    {
        $t = microtime(true);

        $ret = new stdClass();
        $ret->command = trim(call_user_func_array('sprintf', func_get_args()));
        preg_match('| 2[^>]*>[^>]*&1$|', $ret->command) || $ret->command .= ' 2>&1';
        $ret->status = 1;

        $output = [];

        exec($ret->command, $output, $ret->status);

        $ret->succeed = !$ret->status;
        $ret->output = implode(PHP_EOL, $output);
        $ret->duration = microtime(true) - $t;

        return $ret;
    }

    /**
     * parse cli $_SERVER['argv'] like:
     * php main start -d --opt1=val1 --opt2="val2 append" --opt3=val3 ..
     *
     * @return object[env, pairs]
     */
    public static function argvParser() {
        $ret = new stdClass();

        $ret->pairs = [];
        $ret->env = [];

        foreach (Tii::valueInArray($_SERVER, 'argv', []) as $k => $arg) {
            if ($k === 0) continue;
            if (substr($arg, 0, 2) == '--') {
                $arg = substr($arg, 2);
                if (strpos($arg, '=') === false) {
                    $ret->pairs[$arg] = true;
                } else {
                    list($m, $n) = explode('=', $arg, 2);
                    $ret->pairs[$m] = $n;
                }

            } else {
                $ret->env[] = $arg;
            }
        }

        return $ret;
    }

    /**
     * Explodes string into array, optionally trims values and skips empty ones
     *
     * @param string $delimiter Delimiter.
     * @param string $string String to be exploded.
     * @param mixed $trim Whether to trim each element. Can be:
     *   - boolean - to trim normally;
     *   - string - custom characters to trim. Will be passed as a second argument to `trim()` function.
     *   - callable - will be called for each value instead of trim. Takes the only argument - value.
     * @param boolean $skipEmpty Whether to skip empty strings between delimiters. Default is true.
     * @param boolean $unique Removes duplicate values from an array, Default is true.
     * @return array
     */
    public static function explode($delimiter, $string, $trim = true, $skipEmpty = true, $unique = true)
    {
        $result = explode($delimiter, $string);
        if ($trim) {
            if ($trim === true) {
                $trim = 'trim';
            } elseif (!is_callable($trim)) {
                $trim = function ($v) use ($trim) {
                    return trim($v, $trim);
                };
            }
            $result = array_map($trim, $result);
        }
        if ($skipEmpty) {
            // Wrapped with array_values to make array keys sequential after empty values removing
            $result = array_values(array_filter($result, function ($value) {
                return $value !== '';
            }));
        }
        return $unique ? array_unique($result) : $result;
    }

    /**
     * Magic methods
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        static $instances = [];
        if (isset($instances[$name])) return $instances[$name];

        if (preg_match('/get(.*)Helper/i', $name, $matches)) {//Call helper methods
            if (empty($matches[1])) {
                $instances[$name] = Tii::object('@Tii_Application_Helper');
            } else {
                $instances[$name] = Tii::object('@Tii_Application_Helper', $matches[1]);
            }
            return $instances[$name];
            /** @var Tii_Application_Helper */
        }

        foreach (['Tii_Config', 'Tii_Security_Encryption'] as $class) {//magic call sugar method
            if (method_exists($class, $name)) {
                return call_user_func_array([$class, $name], $arguments);
            }
        }
    }
}

/**
 * Use spl_autoload_register to automatic load class
 */
spl_autoload_register(function ($className) {
    $delimiter = strpos($className, '_');
    if ($delimiter !== false) {
        $namespace = substr($className, 0, $delimiter);
        switch ($namespace) {
            case 'Tii' :
                $includeDirectory = TII_DIRECTORY;
                break;
            default:
                $includeDirectory = Tii::get(sprintf('tii.library.%s', $namespace));
                if (empty($includeDirectory) && ($wildcardIncludeDirectory = Tii::get('tii.library.*'))) {//Unified path
                    $wildcardIncludeDirectory = $wildcardIncludeDirectory . DIRECTORY_SEPARATOR . ucfirst($namespace);
                    is_dir($wildcardIncludeDirectory) && $includeDirectory = $wildcardIncludeDirectory;
                }
                if (empty($includeDirectory) && !is_dir($includeDirectory = Tii::filename('library', lcfirst($namespace)))) {
                    throw new Tii_Exception("class `%s' not found", $className);
                }
                if (!is_dir($includeDirectory)) {
                    throw new Tii_Exception("include directory `%s' not exist", $includeDirectory);
                }
                break;
        }
        $autoloader = $includeDirectory . str_replace('_', DIRECTORY_SEPARATOR, strstr($className, '_')) . '.php';
        if (is_file($autoloader)) require_once $autoloader;
    } else {
        include_once str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    }
});

//Trigger handlers...
set_error_handler(function () {
    $args = func_get_args();
    array_unshift($args, 'tii.error.handler');
    return call_user_func_array('Tii_Event::action', $args);
});

set_exception_handler(function ($exception) {
    /** Will only to intercept the outermost anomalies */
    Tii_Event::action('tii.exception.handler', $exception);
});

register_shutdown_function(function () {
    Tii_Event::action('tii.shutdown.handler');
});

Tii::usage();

//Bind default handlers
Tii_Event::register('tii.error.handler', 'Tii_Exception::set_error_handler', 0);

Tii_Event::register('tii.exception.handler', function ($exception) {
    call_user_func('Tii_Exception::set_exception_handler', $exception);
    return true;
}, 0);

Tii_Event::register('tii.shutdown.handler', function () {
    $err = error_get_last();
    if (empty($err)) return true;
    Tii_Exception::set_error_handler($err['type'], $err['message'], $err['file'], $err['line']);
    restore_error_handler();
    return true;
}, 0);

