<?php
/**
 * Processor http
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
 * @version $Id: Http.php 8930 2017-11-23 14:20:25Z alacner $
 */

class Tii_Application_Processor_Http extends Tii_Application_Processor_Abstract
{
    private $requestPath;
    private $layout;
    private $render;
    private $viewFormat = NULL;

    public function __construct()
    {
        if (!$this->checkAccess()) {
            header("HTTP/1.0 403 Forbidden");
            header("X-Accessed-IP: " . $this->getIp());

            echo Tii::get('tii.application.server.access.message', 'Access to this resource on the server is denied!');
            exit;
        }

        parent::__construct();

        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])
            || !substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')
            || !is_callable("ob_gzhandler")
            || !ob_start("ob_gzhandler")) {
            ob_start();
        }

        ob_implicit_flush(0);

        $this->urlParser();

        //default support view format functions
        Tii_Event::register('tii.application.processor.http.view.formats', function($functions) {
            $functions['json'] = function($data){return json_encode($data);};
            $functions['serialize'] = function($data){return serialize($data);};
            $functions['txt'] = function($data){return $data;};
            return $functions;
        });
    }

    /**
     * Verify access
     *
     * @return bool
     */
    private function checkAccess()
    {
        $enabled = (bool)Tii::get('tii.application.server.access.enable', false);
        if (!$enabled) return true;
        return Tii_Network::ipInRanges(
            $this->getIp(),
            Tii::get('tii.application.server.access.rules', []),
            true
        );
    }

    protected function doBusyError($loadctrl, $load)
    {
        header("HTTP/1.0 503 Service Unavailable");
        echo Tii::get('tii.application.server.busy_error.message_html', 'Server too busy. Please try again later.');
        exit;
    }

    /**
     * [expired = 0, [$fragment1[, ...]]
     *
     * @return string cached key
     * @throws Tii_Application_IgnoreException
     */
    public function viewCached()
    {
        $args = func_get_args();
        if (empty($args)) return false;

        $expired = array_shift($args);

        $prefix = sprintf("tii.viewCached.%s.%s.%s.%s.",
            Tii_Config::getIdentifier(),
            $this->getModuleName(),
            $this->getControllerName(),
            $this->getActionName()
        );

        /*
        Data stored by memcached is identified with the help of a key. A key
        is a text string which should uniquely identify the data for clients
        that are interested in storing and retrieving it.  Currently the
        length limit of a key is set at 250 characters (of course, normally
        clients wouldn't need to use such long keys); the key must not include
        control characters or whitespace.
         */
        $key = implode('.', array_map(function($k){return is_scalar($k) ? strval($k) : serialize($k);}, $args));
        $key = preg_replace('/\s+/', '', $key);
        if ((($len = strlen($key)) >= 250) || ($len + strlen($prefix) >= 250)) {
            $key = md5($key);
        }
        $key = $prefix . $key;

        $this->setPair("expired", (int)$expired, 'view.cached');
        $this->setPair("key", $key, 'view.cached');

        if ($cached = Tii::object("Tii_Cache")->get($key)) {
            $this->setPair("cached", $cached, 'view.cached');
            throw new Tii_Application_IgnoreException("via cached: %s", $key);
        }

        return $key;
    }

    public function assign($key, $value = NULL)
    {
        return $this->setPair($key, $value, 'view');
    }

    public function assignAll($vars)
    {
        return $this->setPairs($vars, 'view');
    }

    public function get($key, $default = NULL)
    {
        return $this->getPair($key, $default, 'view');
    }

    public function getView($default = [])
    {
        return $this->getPairs($default, 'view');
    }

    public function getServerProtocol()
    {
        return $_SERVER['SERVER_PROTOCOL'];
    }

    public function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getHost()
    {
        return $_SERVER['HTTP_HOST'];
    }

    public function getReferer()
    {
        return $_SERVER['HTTP_REFERER'];
    }

    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Get request client IP
     * @return string
     */
    public function getIp()
    {
        static $ip = NULL;
        static $ipPattern = '/^([0-9]{1,3}\.){3}[0-9]{1,3}$/';

        if (isset($ip)) return $ip;

        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && array_key_exists('REMOTE_ADDR', $_SERVER)) {
            if (strstr($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                $x = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $_SERVER['HTTP_X_FORWARDED_FOR'] = trim(end($x));
            }
            if (preg_match($ipPattern, $_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } elseif (array_key_exists('HTTP_CLIENT_IP', $_SERVER) && preg_match($ipPattern, $_SERVER['HTTP_CLIENT_IP'])) {
            return $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (preg_match($ipPattern, $_SERVER['REMOTE_ADDR'], $m)) {
            return $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip = 'Unknown';
    }

    /**
     * Get The URI which was given in order to access this page; for instance, '/index.html'.
     * @return string
     */
    public function getRequestUri()
    {
        static $requestUri = NULL;
        if (isset($requestUri)) return $requestUri;

        $requestUri = $_SERVER['REQUEST_URI'];

        $tags = [// for iis rewrite
            'HTTP_X_ORIGINAL_URL',//IIS7
            'HTTP_X_REWRITE_URL',//IIS6
            'REDIRECT_URL'
        ];
        foreach ($tags as $tag) {
            if (isset($_SERVER[$tag])) {
                $requestUri = $_SERVER[$tag];
                break;
            }
        }

        //for rewrite
        $rewrite = Tii::get('tii.application.rewrite.http', []);
        if (count($rewrite) > 0) {
            if (isset($rewrite['*'])) {
                $requestUri = call_user_func($rewrite['*'], $requestUri);
                unset($rewrite['*']);
            }
            $requestUri = preg_replace(array_keys($rewrite), array_values($rewrite),  $requestUri);
        }

        return $requestUri;
    }

    /**
     * Parsed URL value
     *
     * @param string $uri
     * @return string
     */
    public function getRequestUrl($uri = NULL)
    {
        //HTTP_HOST = SERVER_ADDR:SERVER_PORT
        return sprintf('http%s://%s%s', $this->isHttps()?"s":"", $this->getHost(), $uri?:$this->getRequestUri());
    }

    /**
     * /f/d/d/d?xxx=fd return /f/d/d/d
     * @return mixed
     */
    public function getRequestPath()
    {
        return $this->requestPath;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        static $headers = NULL;

        if (isset($headers)) return $headers;

        if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $header['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
        } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $header['Authorization'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $header['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $header['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        foreach ($_SERVER as $key => $value) {
            if ('HTTP_' == substr($key, 0, 5)) {
                $headers[str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_-'))] = $value;
            }
        }

        return $headers;
    }

    public function getHeader($name, $default = NULL)
    {
        $headers = $this->getHeaders();
        $name = str_replace('_', '-', ucwords(strtolower($name), '_-'));
        return isset($headers[$name]) ? $headers[$name] : $default;
    }

    /**
     * Get response body
     *
     * @return string
     */
    public function getBody()
    {
        return @file_get_contents('php://input');// better than $GLOBALS['HTTP_RAW_POST_DATA']
    }

    /**
     * Fast setting cookies
     *
     * @see Tii_Application_Processor_Http_Response::setCookie
     * @param $name
     * @param $value
     * @param int $expire
     * @return mixed
     */
    public function setCookie($name, $value, $expire = 0)
    {
        $path = Tii::get('tii.controller.cookie.path', '/');
        $domain = Tii::get('tii.controller.cookie.domain', NULL);
        $secure = Tii::get('tii.controller.cookie.secure', false);
        $httponly = Tii::get('tii.controller.cookie.httponly', false);
        return $this->getResponse()->setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Get cookies
     * @return array
     */
    public function getCookies()
    {
        $cookies = [];
        foreach ($_COOKIE as $name => $value) {
            if ($name{0} === '_') {
                $cookies[$name] = Tii_Security_Encryption::decode($value);
            } else {
                $cookies[$name] = $value;
            }
        }
        return $cookies;
    }

    public function getCookie($name, $default = NULL, $decode = true)
    {
        $name = str_replace('.', '_', $name);
        return isset($_COOKIE[$name])
            ? (
            $decode
                ? (
            ($name{0} === '_')
                ? Tii_Security_Encryption::decode($_COOKIE[$name])
                : $_COOKIE[$name]
            )
                : $_COOKIE[$name])
            : $default;
    }

    public function getQueries()
    {
        return $_GET;
    }

    public function getQuery($name, $default = NULL)
    {
        return Tii::valueInArray($_GET, $name, $default);
    }

    public function getPosts()
    {
        return $_POST;
    }

    public function getPost($name, $default = NULL)
    {
        return Tii::valueInArray($_POST, $name, $default);
    }

    /**
     * GPC（GET,POST,Cookie,pairs）
     */
    public function getRequests()
    {
        return array_merge($_REQUEST, $this->getPairs());
    }

    /**
     * PGPC（PAIR, GET,POST,Cookie）
     */
    public function getRequest($name, $default = NULL)
    {
        return Tii::valueInArray($this->getRequests(), $name, $default);
    }

    public function getFiles()
    {
        return $_FILES;
    }

    public function getFile($name, $default = NULL)
    {
        return Tii::valueInArray($_FILES, $name, $default);
    }

    public function getSessions()
    {
        return $_SESSION;
    }

    public function getSession($name, $default = NULL)
    {
        return Tii::valueInArray($_SESSION, $name, $default);
    }

    /**
     * Returns true if the request is a XMLHttpRequest.
     *
     * It works if your JavaScript library set an X-Requested-With HTTP header.
     * Works with Prototype, Mootools, jQuery, and perhaps others.
     *
     * @return bool true if the request is an XMLHttpRequest, false otherwise
     */
    public function isXmlHttpRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public function isPost()
    {
        return $this->getRequestMethod() === 'POST';
    }

    public function isHttps()
    {
        return isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] === "on";
    }

    /**
     * Test if the current browser runs on a mobile device (smart phone, tablet, etc.)
     *
     * @staticvar bool $isMobile
     * @return bool
     */
    public function isMobile()
    {
        static $isMobile = NULL;

        if (isset($isMobile)) return $isMobile;

        $userAgent = $this->getUserAgent();
        if (empty($userAgent)) {
            $isMobile = false;
        } elseif (strpos($userAgent, 'Mobile') !== false // many mobile devices (all iPhone, iPad, etc.)
            || strpos($userAgent, 'Android') !== false
            || strpos($userAgent, 'Silk/') !== false
            || strpos($userAgent, 'Kindle') !== false
            || strpos($userAgent, 'BlackBerry') !== false
            || strpos($userAgent, 'Opera Mini') !== false
            || strpos($userAgent, 'Opera Mobi') !== false ) {
            $isMobile = true;
        } else {
            $isMobile = false;
        }

        return $isMobile;
    }

    /**
     * Test if the current request is robot (google, baidu, etc.)
     *
     * @staticvar bool $isRobot
     *
     * @return bool
     */
    public function isRobot()
    {
        static $isRobot = NULL;

        if (isset($isRobot)) return $isRobot;

        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            $isRobot = false;
        } elseif (preg_match('/Googlebot|msnbot|bingbot|Slurp|Yahoo|Baiduspider/i', $_SERVER['HTTP_USER_AGENT'])) {
            $isRobot = true;
        } elseif (preg_match('/robot|bot|spider|crawler/i', $_SERVER['HTTP_USER_AGENT'])) {
            $isRobot = true;
        } elseif (preg_match('/appie|Arachnoidea|W3C-checklink|Extractor|HTTrack/i', $_SERVER['HTTP_USER_AGENT'])) {
            $isRobot = true;
        } else {
            $isRobot = false;
        }

        return $isRobot;
    }

    public function setRender($render = NULL)
    {
        $this->render = $render;
        return $this;
    }

    public function noRender($viewFormat = NULL, $force = false)
    {
        if ($viewFormat) {
            $this->viewFormat = $this->viewFormat ? ($force ? $viewFormat : $this->viewFormat) : $viewFormat;
        }
        return $this->setRender(NULL);
    }

    public function getRender()
    {
        return $this->render;
    }

    public function getViewFormat()
    {
        return $this->viewFormat;
    }

    public function setLayout($layout = "default")
    {
        $this->layout = $layout;
        return $this;
    }

    public function noLayout()
    {
        $this->setLayout(NULL);
        return $this;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return Tii_Application_Processor_Http_Response
     */
    public function getResponse()
    {
        return Tii::object('Tii_Application_Processor_Http_Response');
    }

    /**
     * After done *Action
     */
    public function over()
    {
        //set header
        if ($this->getRender()) {
            if ($this->isXmlHttpRequest()) {
                $this->getResponse()->setHeader("Expires", "Thu, 01 Jan 1970 00:00:01 GMT");
                $this->getResponse()->setHeader('Cache-Control', "Cache-Control: no-store, no-cache, must-revalidate");
            } else {
                $this->getResponse()->setHeader('Content-Type', 'text/html; charset=utf-8');
            }
        } else {
            switch($this->viewFormat) {
                case 'json':
                    $this->getResponse()->setHeader('Content-Type', 'application/json; charset=utf-8');
                    break;
                default:
                    break;
            }
        }

        // Response cached
        if ($cached = $this->getPair('cached', NULL, 'view.cached')) {
            $this->getResponse()->setHeader('viaCached', 'true');
            if (Tii_Config::isDebugMode()) {
                $this->getResponse()->setHeader('viaCacheKey', $this->getPair('key', NULL, 'view.cached'));
            }
            $this->callResponseFunc(function($c){ echo $c;}, Tii_Event::filter(
                'tii.application.processor.http.response.cached', $cached
            ));
            return;
        }

        // render file
        $expired = (int)$this->getPair('expired', 0, 'view.cached');
        if ($this->getRender()) {
            $render = new Tii_Application_Processor_Http_Render();
            $this->callResponseFunc([$render, 'display'], $expired);
        } else {
            $func = Tii::valueInArray(
                Tii_Event::filter('tii.application.processor.http.view.formats', []), $this->viewFormat
            );
            $response = is_callable($func) ? call_user_func($func, Tii_Event::filter('tii.application.processor.http.getView', $this->getView())) : "";
            $this->callResponseFunc(function($c){ echo $c;}, $response);
            $this->cachingViewData($expired, $response);
        }
    }

    /**
     * Caching view data
     *
     * @param $expired
     * @param $response
     */
    public function cachingViewData($expired, $response)
    {
        if ($expired <= 0) return;//Don't need to cache
        Tii_Event::register('tii.shutdown.handler', function() use($expired, $response) {
            Tii::object("Tii_Cache")->set($this->getPair('key', NULL, 'view.cached'), $response, 0, $expired);
        }, 10);
    }

    /**
     * Response sth.
     * @see call_user_func
     */
    public function callResponseFunc()
    {
        $args = func_get_args();
        $function = array_shift($args);
        if (function_exists('fastcgi_finish_request')) {
            $this->getResponse()->setHeader('Tii-Flush-Type', 'fastcgi_finish_request', true);
            $this->getResponse()->done();
            call_user_func_array($function, $args);
            Tii_Config::isDebugMode() || fastcgi_finish_request();
        } else {
            $this->getResponse()->setHeader('Tii-Flush-Type', 'normal', true);
            $this->getResponse()->done();
            call_user_func_array($function, $args);
            ob_flush();
            flush();
        }
    }

    public function setActionName($name)
    {
        parent::setActionName($name);
        $this->setRender($name);
    }

    /**
     * @see parent::forward
     */
    public function forward($action = NULL, $controller = NULL, $module = NULL)
    {
        $this->noRender();
        return parent::forward($action, $controller, $module);
    }

    /**
     * Redirect one url to another
     * @param string $url target url
     * @param int $time delay seconds
     */
    public function redirect($url, $time = 0)
    {
        $this->noRender();
        $url = str_replace(["\n", "\r"], '', $url); //multiple line
        if (!$this->getResponse()->isResponsed()) {
            if ($time === 0) {
                $this->getResponse()->setHeader('Location', $url)->done();
            } else {
                $this->getResponse()->setHeader('Refresh', $time . ";url=" .$url)->done();
            }
        } else {
            print("<meta http-equiv='Refresh' content='" . $time . ";URL=" .$url. "'>");
        }
        exit;
    }

    public function url($mca = NULL, $gets = NULL, array $pairs = [], $prefix = '/', $suffix = '.html')
    {
        $mca || $mca = [];
        if (is_string($mca)) {
            if (($pos = strrpos($mca, '.')) !== false) {
                $suffix = substr($mca, $pos);
                $mca = substr($mca, 0, $pos);
            }
            $_mca = explode("/", $mca);
            $mca = [];
            $mca['module'] = Tii::valueInArray($_mca, 1, $this->getDefaultModuleName());
            $mca['controller'] = Tii::valueInArray($_mca, 2, $this->getDefaultControllerName());
            $mca['action'] = Tii::valueInArray($_mca, 3, $this->getDefaultActionName());
        }
        $mca = array_merge([
                'module' => $this->getModuleName(),
                'controller' => $this->getControllerName(),
                'action' => $this->getActionName(),
            ],
            $mca
        );

        $uri = [];
        if (empty($pairs)) {
            if ($mca['action'] != $this->getDefaultActionName()) {
                $uri[] = $mca['module'];
                $uri[] = $mca['controller'];
                $uri[] = $mca['action'];
            } else {
                if ($mca['controller'] != $this->getDefaultControllerName()) {
                    $uri[] = $mca['module'];
                    $uri[] = $mca['controller'];
                } else {
                    $uri[] = $mca['module'];
                }
            }
        } else {
            $uri[] = $mca['module'];
            $uri[] = $mca['controller'];
            $uri[] = $mca['action'];

            foreach($pairs as $k => $v) {
                $uri[] = $k;
                $uri[] = $v;
            }
        }

        if ($uri = implode('/', $uri)) $uri .= $suffix;

        $url = Tii_Event::filter('tii.application.processor.http.url', Tii_Http::urlAppend($prefix . $uri, $gets));

        $pseudo = Tii::get('tii.application.rewrite.pseudo.http', []);
        if (count($pseudo) > 0) {

            if (strpos($url, '?') === false) return $url;
            $parse_url = parse_url($url);

            if (isset($pseudo['*'])) {
                $url = call_user_func($pseudo['*'], $url, $parse_url);
                unset($pseudo['*']);
            }

            if (isset($pseudo[$parse_url['path']])) {
                parse_str($parse_url['query'], $output);
                return Tii::render($pseudo[$parse_url['path']], $output);
            }
        }

        return $url;
    }

    /**
     * Parese URI to params,like:
     * /module/controller/action/name1/value1/name2/value2[.html]?getname1=getvalue1
     */
    protected function urlParser()
    {
        $parseUrl = parse_url($this->getRequestUri());
        isset($parseUrl['path']) || $parseUrl['path'] = '/';

        if (preg_match('|(.*)\.(\w+)$|', $parseUrl['path'], $m)) {
            $parseUrl['path'] = $m[1];
            $this->viewFormat = $m[2];
        }

        $singles = explode('/', trim($parseUrl['path'], '/'));

        isset($singles[0]) && $this->setModuleName($singles[0]);
        isset($singles[1]) && $this->setControllerName($singles[1]);
        isset($singles[2]) && $this->setActionName($singles[2]);

        $pairs = [];
        for ($i = 3, $j = count($singles); $i < $j; $i = $i + 2) {
            $pairs[$singles[$i]] = (isset($singles[$i+1])) ? $singles[$i+1] : '';
        }
        $this->setPairs($pairs);

        $this->requestPath = $parseUrl['path'];

        if (isset($parseUrl['query'])) {
            $_SERVER['QUERY_STRING'] = $parseUrl['query'];
            parse_str($parseUrl['query'], $query);
            foreach ($query as $k => $v) {
                $_GET[$k] = $v;
                $_REQUEST[$k] = $v;
            }
        }
    }
}