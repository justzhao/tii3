<?php
/**
 * Configuration Class [*.config.php]
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
 * Reserve: tii.config.php and lang-*.config.php
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Config.php 9139 2019-01-12 03:51:43Z alacner $
 */

final class Tii_Config
{
    private static $dirs = [];
    public static $lang = 'default';
    private static $configs = [];

    /**
     * Set the path of the setting load configuration file
     *
     * @param $dir
     * @param string $env
     * @return bool
     */
    public static function setDir($dir, $env = 'local')
    {
        if (!is_dir($dir)) {
            trigger_error("invalid path `$dir'!!!", E_USER_ERROR);
            return false;
        }

        $dir = rtrim($dir, '\\/');

        self::$dirs = [
            $dir . '/' . $env,
            $dir,
            $dir . '/..',
        ];

        //set include path
        $includeDirectory = self::get('tii.library.include', []);
        if ($includeDirectory) {
            is_array($includeDirectory) && $includeDirectory = implode(PATH_SEPARATOR, $includeDirectory);
            set_include_path($includeDirectory . PATH_SEPARATOR . get_include_path());
        }

        return true;
    }

    /**
     * Get the path of the setting load configuration file
     *
     * @param $index
     * @return mixed
     */
    protected static function getDirs($index = NULL)
    {
        //set default config dir
        if (empty(self::$dirs)) {
            trigger_error("Configured first, Usage: Tii_Config::setDir('/path/to/config/folder')", E_USER_WARNING);
            self::setDir(TII_DIRECTORY . '/.configs');
        }
        return is_null($index) ? self::$dirs : self::$dirs[$index];
    }

    /**
     * Automatic loading configuration files
     *
     * @param string $namespace
     * @return bool
     */
    protected static function loader($namespace = 'tii')
    {
        if ($file = self::getFile($namespace . '.config.php')) {
            self::set($namespace, include $file);
            return true;
        }
        return false;
    }

    /**
     * Automatic loading configuration files
     *
     * @param string $name
     * @return bool
     */
    public static function getFile($name)
    {
        foreach (self::getDirs() as $dir) {
            $file = Tii_Filesystem::concat($dir, $name);
            if (is_file($file)) return $file;
        }
        return false;
    }

    /**
     * According to different applications
     *
     * @return string
     */
    public static function getIdentifier()
    {
        static $identifier;
        isset($identifier) || $identifier = Tii_Math::hashStr(self::getDirs(0));
        return $identifier;
    }

    /**
     * Set one or more configs
     *
     * @param string|array $key
     * @param mixed $value
     * @return mixed
     */
    public static function set($key, $value = NULL)
    {
        return Tii::setter(self::$configs, $key, $value);
    }

    /**
     * Have value, return value, otherwise returns the default value
     *
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = NULL)
    {
        $val = Tii::getter(self::$configs, $key);
        if (!is_null($val)) return $val;

        if (strpos($key, '.') !== false) {
            list($namespace) = explode('.', $key, 2);
        } else {
            $namespace = $key;
        }

        self::loader($namespace);

        return Tii::getter(self::$configs, $key, $default);
    }

    /**
     * I18N namespace builder
     *
     * @param $text
     * @return string
     */
    public static function _lang($text)
    {
        return 'lang-' . self::$lang . '.' . $text;
    }

    /**
     * Multiple language support, I18N
     *
     * @static
     * @see sprintf
     * @return string
     */
    public static function lang()
    {
        $args = func_get_args();
        if (isset($args[0])) {
            $args[0] = self::get(self::_lang($args[0]), $args[0]);
        }
        return call_user_func_array('sprintf', $args);
    }

    /**
     * Is Debug mode?
     *
     * @return boolean
     */
    public static function isDebugMode()
    {
        return (bool)self::get('tii.debug_mode', false);
    }
}