<?php
/**
 * Encapsulated with socket under the HTTP request
 * WARNING: It is forbidden to use the file_get_contents function access external links.
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
 * @version $Id: Http.php 677 2012-06-22 02:37:34Z alacner $
 */

final class Tii_Http
{
    /**
     * Have a body way to access the server
     * @static
     * @param $url
     * @param string $data Default as a string, or preparePostBody returns the result
     * @param array $headers
     * @param array $options
     * @param string $method
     * @return stdClass
     */
    public static function post($url, $data = '', array $headers = [], array $options = [], $method = 'POST')
    {
        $parseUrl = self::parseUrl($url);

        if (is_array($data)) {
            $preparePostBody = call_user_func_array(['self', 'preparePostBody'], $data);
            $headers = array_merge($headers, $preparePostBody['headers']);
            $data = $preparePostBody['data'];
        }

        return self::responser($url, $headers, $parseUrl, $options, $method, $data);
    }

    /**
     * No body access to the server
     * @static
     * @param $url
     * @param array $headers
     * @param array $options
     * @param string $method
     * @return stdClass
     */
    public static function get($url, array $headers = [], array $options = [], $method = 'GET')
    {
        $parseUrl = self::parseUrl($url);
        return self::responser($url, $headers, $parseUrl, $options, $method);
    }

    /**
     * Returns the HTTP structure
     *
     * @link http://php.net/manual/en/function.stream-socket-client.php
     *
     * @static
     * @param $stream The structure of the prepared to send the Http body
     * @param array $options
     * @return stdClass
     * @throws Exception
     */
    protected static function response($stream, array $options)
    {
        $response = new stdClass();
        $response->state = 0;
        $response->data = NULL;
        $response->headers = [];
        $response->runtime = ['timestamp' => [], 'time_consuming' => ['trace' => [], 'total' => 0]];
        $response->handler = NULL;

        $response->runtime['timestamp']['start'] = microtime(true);

        $parseRemoteUrl = parse_url($options['remote_socket']);

        if (function_exists('stream_socket_client')) {
            isset($options['flags']) || $options['flags'] = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;

            if (isset($options['contexts'][$options['parseUrl']['host']])) {
                $options['context'] = $options['contexts'][$options['parseUrl']['host']];
            }

            isset($options['context']) || $options['context'] = stream_context_create();

            $socket = @stream_socket_client(
                $options['remote_socket'],
                $errno, $errstr,
                $options['timeout'],
                $options['flags'],
                $options['context']
            );
            $response->handler = '@stream_socket_client';
        } else {
            $hostname = sprintf('%s://%s', $parseRemoteUrl['scheme'], $parseRemoteUrl['host']);
            if (function_exists('fsockopen')) {
                $socket = @fsockopen($hostname, $parseRemoteUrl['port'], $errno, $errstr, $options['timeout']);
                $response->handler = '@fsockopen';
            } elseif (function_exists('pfsockopen')) {
                $socket = @pfsockopen($hostname, $parseRemoteUrl['port'], $errno, $errstr, $options['timeout']);
                $response->handler = '@pfsockopen';
            } else {
                throw new Tii_Exception("stream_socket_client,*fsockopen function were forbidden");
            }
        }

        if (!is_resource($socket)) {
            throw new Exception(Tii::lang($errstr), $errno);
        }

        $response->options = $options;

        $response->runtime['timestamp']['connection'] = microtime(true);
        $response->runtime['time_consuming']['trace']['connection'] = self::timeConsumption(
            $response->runtime['timestamp']['connection'],
            $response->runtime['timestamp']['start']
        );

        list($sec, $usec) = explode('.', $response->options['timeout'] . '.');
        stream_set_timeout($socket, intval($sec), intval($usec));

        if ($options['is_proxy_mode'] && $options['parseUrl']['scheme'] == 'https') {
            if (!fwrite($socket, self::streamBuilder(
                "CONNECT {$options['parseUrl']['host']}:{$options['parseUrl']['port']} {$options['protocol']}",
                ['Host' => $parseRemoteUrl['host']]
            ))) {
                @fclose($socket);
                throw new Tii_Exception("write https connect error");
            }

            $header = trim(fgets($socket));
            if (!$header) {
                @fclose($socket);
                throw new Tii_Exception("response https connect header error");
            }

            list($proto, $state, $message) = explode(' ', $header, 3);
            if ($state != 200) {
                @fclose($socket);
                throw new Tii_Exception("connection unestablished");
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            $response->proxy = [
                'proto' => $proto,
                'state' => $state,
                'message' => $message,
                'headers' => [],
            ];

            while (($header = trim(fgets($socket))) != '') {
                if (strpos($header, ':') === false) continue;
                list ($key, $value) = explode(':', $header, 2);
                $key = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($key))));//like: content-type => Content-Type, location => Location
                if (isset($response->proxy['headers'][$key])) {
                    $response->proxy['headers'][$key] = $response->proxy['headers'][$key] . '; ' . trim($value);
                } else {
                    $response->proxy['headers'][$key] = trim($value);
                }
            }
        }

        @fwrite($socket, $stream);

        $status = stream_get_meta_data($socket);
        if ($status['timed_out']) {
            @fclose($socket);
            throw new Tii_Exception("connection timed out, defined %s, cost %s (unit:second)",
                $response->options['timeout'],
                $response->runtime['connection']
            );
        }

        $response->runtime['timestamp']['request'] = microtime(true);
        $response->runtime['time_consuming']['trace']['request'] = self::timeConsumption($response->runtime['timestamp']['request'], $response->runtime['timestamp']['connection']);


        $header = trim(fgets($socket));
        if (!$header) {
            @fclose($socket);
            throw new Tii_Exception("response header error");
        }

        list($proto, $state, $message) = explode(' ', $header, 3);

        while (($header = trim(fgets($socket))) != '') {
            if (strpos($header, ':') === false) continue;
            list ($key, $value) = explode(':', $header, 2);
            //like: content-type => Content-Type, location => Location
            $key = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($key))));
            if (isset($response->headers[$key])) {
                $response->headers[$key] = $response->headers[$key] . '; ' . trim($value);
            } else {
                $response->headers[$key] = trim($value);
            }
        }

        if (array_key_exists('Transfer-Encoding', $response->headers)) {
            $body = self::getSocketBodyByTransferEncoding($socket, $response->headers['Transfer-Encoding']);
        } else {
            $body = self::getSocketBodyByNormal($socket);
        }

        if (array_key_exists('Content-Encoding', $response->headers)) {
            self::contentDecoding($body, $response->headers['Content-Encoding']);
        }

        $response->runtime['timestamp']['response'] = microtime(true);
        $response->runtime['time_consuming']['trace']['response'] = self::timeConsumption($response->runtime['timestamp']['response'], $response->runtime['timestamp']['request']);

        @fclose($socket);

        $response->proto = $proto;
        $response->state = intval($state);
        $response->message = $message;
        $response->data = $body;

        $response->runtime['timestamp']['finish'] = microtime(true);
        $response->runtime['time_consuming']['trace']['finish'] = self::timeConsumption($response->runtime['timestamp']['finish'], $response->runtime['timestamp']['response']);
        $response->runtime['time_consuming']['total'] = self::timeConsumption($response->runtime['timestamp']['finish'], $response->runtime['timestamp']['start']);

        return $response;
    }

    protected static function responser($url, $headers, $parseUrl, $options, $method, $data = '')
    {
        array_key_exists('Host', $headers) || $headers['Host'] = $parseUrl['host'];

        $options = array_merge([
            'url' => $url,
            'method' => $method,
            'protocol' => 'HTTP/1.1',
            'headers' => $headers,
            'parseUrl' => $parseUrl,
            'is_proxy_mode' => false,
        ], $options);

        isset($options['timeout']) || $options['timeout'] = ini_get("default_socket_timeout");

        if (empty($options['remote_socket'])) {
            switch ($options['parseUrl']['scheme']) {
                case 'https'://If OpenSSL support is installed, you may prefix the hostname with either ssl://
                    $options['remote_socket'] = 'ssl';
                    break;
                default:
                    $options['remote_socket'] = 'tcp';
                    break;
            }
            $options['remote_socket'] .= sprintf("://%s:%d", $options['parseUrl']['host'], $options['parseUrl']['port']);
        }

        $options = Tii_Event::filter('tii.http.options', $options);

        $uri = $options['is_proxy_mode'] ? $url : $parseUrl['query_string'];

        return self::response(self::streamBuilder("{$options['method']} $uri {$options['protocol']}", $headers, $data), $options);
    }

    /**
     * @static
     * @param $before
     * @param $after
     * @param $decimals
     * @return string
     */
    protected static function timeConsumption($before, $after, $decimals = 4)
    {
        return number_format($before - $after, $decimals);
    }

    /**
     * @static
     * @param $socket
     * @param $transferEncoding
     * @return string
     */
    protected static function getSocketBodyByTransferEncoding(&$socket, $transferEncoding)
    {
        switch (strtolower($transferEncoding)) {
            case 'chunked' :
                return self::getSocketBodyByChunked($socket);
            default:
                return self::getSocketBodyByNormal($socket);
        }
    }

    /**
     * According to the Content - Content Encoding conversion
     *
     * @static
     * @param $content
     * @param $contentEncoding Response header中的 Content-Encoding值
     */
    protected static function contentDecoding(&$content, $contentEncoding)
    {
        switch (strtolower($contentEncoding)) {
            case 'gzip' :
                $content = self::gzdecode($content);
                break;
            default:
                break;
        }
    }

    /**
     * Decodes a gzip compressed string
     *
     * @static
     * @param $content
     * @return string
     */
    protected static function gzdecode(&$content)
    {
        if (function_exists('gzdecode')) return gzdecode($content);
        return file_get_contents('compress.zlib://data:who/cares;base64,' . base64_encode($content));
    }

    /**
     * Get the body body chunked format
     *
     * @static
     * @param $socket
     * @return string
     */
    protected static function getSocketBodyByChunked(&$socket)
    {
        $body = '';
        while (!feof($socket) && ($chunkSize = (int)hexdec(fgets($socket)))) {
            while ($chunkSize > 0) {
                $temp = fread($socket, $chunkSize);
                $body .= $temp;
                $chunkSize -= strlen($temp);
            }
            fread($socket, 2); // skip \r\n
        }
        return $body;
    }

    /**
     * For normal body
     *
     * @static
     * @param $socket
     * @return string
     */
    protected static function getSocketBodyByNormal(&$socket)
    {
        $stop = false;
        $limit = 0;
        $body = '';
        while (!feof($socket) && !$stop) {
            $data = fread($socket, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
            $body .= $data;
            if ($limit) {
                $limit -= strlen($data);
                $stop = $limit <= 0;
            }
        }
        return $body;
    }

    /**
     * Build the stream for http protocol
     *
     * @static
     * @param $protocol
     * @param array $headers
     * @param $data
     * @return string
     */
    public static function streamBuilder($protocol, $headers = [], $data = '')
    {
        $headers = array_merge([
            'User-Agent' => 'Tii/' . Tii_Version::VERSION,
            'Content-Length' => strlen($data),
            'Connection' => 'close',
        ], $headers);

        $stream = [];
        $stream[] = $protocol;

        foreach ($headers as $key => $value) {
            $stream[] = sprintf("%s: %s", $key, $value);
        }

        return implode("\r\n", $stream) . "\r\n\r\n" . $data;
    }

    /**
     * Parse URL
     *
     * @static
     * @param $url
     * @return array
     * @throws Exception
     */
    public static function parseUrl($url)
    {
        $parseUrl = parse_url($url);
        if (!$parseUrl || !is_array($parseUrl)) {
            throw new Tii_Exception("parse URL `%s' error", $url);
        }
        array_key_exists('port', $parseUrl) || $parseUrl['port'] = ($parseUrl['scheme'] === 'https') ? 443 : 80;
        $query = array_key_exists('path', $parseUrl) ? $parseUrl['path'] : '/';
        array_key_exists('query', $parseUrl) && $query .= '?' . $parseUrl['query'];
        array_key_exists('fragment', $parseUrl) && $query .= '#' . $parseUrl['fragment'];
        $parseUrl['query_string'] = $query;

        return $parseUrl;
    }

    /**
     * Prepare post body according to encoding type
     *
     * @param array $formvars Form parameters
     * @param array $formfiles Will need to send a local file, changes the content-type attributes
     * @return array ['data', 'headers']
     */
    public static function preparePostBody(array $formvars, array $formfiles = [])
    {
        if (count($formvars) == 0 && count($formfiles) == 0) return ['data' => '', 'headers' => []];

        $headers = [];

        if (count($formfiles) > 0) {
            $contentType = "multipart/form-data";
        } else {
            $contentType = "application/x-www-form-urlencoded";
        }

        switch ($contentType) {
            case "application/x-www-form-urlencoded":
                reset($formvars);
                $postdata = [];
                while (list($key, $val) = each($formvars)) {
                    if (is_array($val) || is_object($val)) {
                        while (list($cur_key, $cur_val) = each($val)) {
                            $postdata[] = urlencode($key) . "[$cur_key]=" . urlencode($cur_val);
                        }
                    } else {
                        $postdata[] = urlencode($key) . "=" . urlencode($val);
                    }
                }
                $headers['Content-Type'] = $contentType;
                $postdata = implode('&', $postdata);
                break;
            case "multipart/form-data":
                $mime_boundary = "Boundary" . md5(uniqid(microtime()));
                reset($formvars);
                $postdata = '';
                while (list($key, $val) = each($formvars)) {
                    if (is_array($val) || is_object($val)) {
                        while (list($cur_key, $cur_val) = each($val)) {
                            $postdata .= "--" . $mime_boundary . "\r\n";
                            $postdata .= "Content-Disposition: form-data; name=\"$key\[$cur_key\]\"\r\n";
                            $postdata .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
                            $postdata .= "$cur_val\r\n";
                        }
                    } else {
                        $postdata .= "--" . $mime_boundary . "\r\n";
                        $postdata .= "Content-Disposition: form-data; name=\"$key\"\r\n";
                        $postdata .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
                        $postdata .= "$val\r\n";
                    }
                }

                reset($formfiles);
                while (list($field_name, $file_names) = each($formfiles)) {
                    settype($file_names, "array");
                    while (list(, $file_name) = each($file_names)) {
                        if (!is_readable($file_name)) continue;

                        $file_content = file_get_contents($file_name);
                        $pathinfo = pathinfo($file_name);
                        $base_name = array_key_exists("basename", $pathinfo) ? $pathinfo["basename"] : basename($file_name);
                        $ext = array_key_exists("extension", $pathinfo) ? $pathinfo["extension"] : substr(strrchr($base_name, "."), 1);
                        $postdata .= "--" . $mime_boundary . "\r\n";
                        $postdata .= "Content-Disposition: form-data; name=\"$field_name\"; filename=\"$base_name\"\r\n";
                        $mimeType = self::getMimeType($ext);
                        $postdata .= "Content-Type: $mimeType; Content-Transfer-Encoding: binary\r\n\r\n";
                        $postdata .= "$file_content\r\n";
                    }
                }
                $postdata .= "--" . $mime_boundary . "--\r\n";

                $headers['Content-Type'] = "$contentType; boundary=" . $mime_boundary;
                break;
        }

        return ['data' => $postdata, 'headers' => $headers];
    }


    /**
     * HTTP protocol parser
     *
     * @param $raw
     * @param $remoteAddr
     * @param $remotePort
     * @return array
     */
    public static function parser($raw, $remoteAddr = '127.0.0.1', $remotePort = 0)
    {
        $http_raw_post_data = '';
        $GET = $POST = $COOKIE = $REQUEST = $FILES = [];
        $SERVER = array_merge($_SERVER, [
            'QUERY_STRING' => '',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'REQUEST_TIME_FLOAT' => Tii_Time::micro(),
            'REQUEST_TIME' => Tii_Time::now(),
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'Tii/' . Tii_Version::VERSION,
            'SERVER_NAME' => gethostname(),
            'HTTP_HOST' => '',
            'HTTP_USER_AGENT' => '',
            'HTTP_ACCEPT' => '',
            'HTTP_ACCEPT_LANGUAGE' => '',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_COOKIE' => '',
            'HTTP_CONNECTION' => '',
            'REMOTE_ADDR' => $remoteAddr,
            'REMOTE_PORT' => $remotePort,
        ]);

        // Parse headers.
        list($http_header, $http_body) = explode("\r\n\r\n", $raw, 2);
        $header_data = explode("\r\n", $http_header);

        list($method, $uri, $protocol) = explode(' ', array_shift($header_data));
        if ($method) $SERVER['REQUEST_METHOD'] = $method;
        if ($uri) $SERVER['REQUEST_URI'] = $uri;
        if ($protocol) $SERVER['SERVER_PROTOCOL'] = $protocol;

        $http_post_boundary = '';

        foreach ($header_data as $content) {
            if (empty($content)) continue;
            list($key, $value) = explode(':', $content, 2);
            $key = str_replace('-', '_', strtoupper($key));
            $value = trim($value);
            $SERVER['HTTP_' . $key] = $value;
            switch ($key) {
                case 'HOST':
                    $tmp = explode(':', $value);
                    $SERVER['SERVER_ADDR'] = $tmp[0];
                    if (isset($tmp[1])) $SERVER['SERVER_PORT'] = $tmp[1];
                    break;
                case 'COOKIE':
                    $SERVER['HTTP_COOKIE'] = $value;
                    $COOKIE = self::parseCookie($value);//$_COOKIE
                    break;
                case 'CONTENT_TYPE':
                    if (!preg_match('/boundary="?(\S+)"?/', $value, $match)) {
                        $SERVER['CONTENT_TYPE'] = $value;
                    } else {
                        $SERVER['CONTENT_TYPE'] = 'multipart/form-data';
                        $http_post_boundary = '--' . $match[1];
                    }
                    break;
            }
        }

        // parse $_POST
        if ($SERVER['REQUEST_METHOD'] === 'POST') {
            $upload_max_filesize = Tii_Filesystem::bytes(ini_get('upload_max_filesize'));

            if (isset($SERVER['CONTENT_TYPE']) && $SERVER['CONTENT_TYPE'] === 'multipart/form-data') {
                $http_body = substr($http_body, 0, strlen($http_body) - (strlen($http_post_boundary) + 4));
                $boundary_data_array = explode($http_post_boundary . "\r\n", $http_body);
                if ($boundary_data_array[0] === '') {
                    unset($boundary_data_array[0]);
                }
                foreach ($boundary_data_array as $boundary_data_buffer) {
                    list($boundary_header_buffer, $boundary_value) = explode("\r\n\r\n", $boundary_data_buffer, 2);
                    // rtrim \r\n
                    $boundary_value = substr($boundary_value, 0, -2);
                    foreach (explode("\r\n", $boundary_header_buffer) as $item) {
                        list($header_key, $header_value) = explode(": ", $item);
                        $header_key = strtolower($header_key);
                        switch ($header_key) {
                            case "content-disposition":
                                // is file?
                                if (preg_match('/name="([^"]*)"; filename="([^"]*)"$/', $header_value, $match)) {
                                    $FILE = [
                                        'name' => $match[2],
                                        'type' => self::getMimeType(Tii_Filesystem::getFilenameExt($match[2])),
                                        'data' => $boundary_value,
                                        'tmp_name' => Tii_Filesystem::tempnam(),
                                        'size' => strlen($boundary_value),
                                        'error' => UPLOAD_ERR_OK,
                                    ];

                                    if ($FILE['size'] <= 0) {
                                        $FILE['error'] = UPLOAD_ERR_NO_FILE;
                                    } else if ($FILE['size'] > $upload_max_filesize) {
                                        $FILE['error'] = UPLOAD_ERR_INI_SIZE;
                                    } else {
                                        if (!file_put_contents($FILE['tmp_name'], $boundary_value)) {
                                            $FILE['error'] = UPLOAD_ERR_CANT_WRITE;
                                        }
                                    }

                                    $FILES[$match[1]] = $FILE;
                                } else {//post field
                                    if (preg_match('/name="(.*?)"$/', $header_value, $match)) {
                                        $POST[$match[1]] = $boundary_value;
                                    }
                                }
                                break;
                        }
                    }
                }
            } else {
                parse_str($http_body, $POST);
                $http_raw_post_data = $http_body;
            }
        }

        // QUERY_STRING
        $SERVER['QUERY_STRING'] = parse_url($SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if ($SERVER['QUERY_STRING']) {
            parse_str($SERVER['QUERY_STRING'], $GET);
        } else {
            $SERVER['QUERY_STRING'] = '';
        }

        $REQUEST = array_merge($GET, $POST, $COOKIE);
        return [$SERVER, $GET, $POST, $COOKIE, $REQUEST, $FILES, $http_raw_post_data];
    }

    /**
     * Parser cookie string to cookie array
     *
     * @param $cookie
     * @return array
     */
    public static function parseCookie($cookie)
    {
        $cookieAry = [];
        foreach (explode("; ", $cookie) as $ps) {//parse_str(str_replace('; ', '&', $cookie), $cookieAry);
            list($k, $v) = explode("=", $ps, 2);
            $cookieAry[trim($k)] = trim($v);
        }
        return $cookieAry;
    }

    /**
     * Tidy cookies to new cookie
     *
     * @return string
     */
    public static function tidyCookie()
    {
        $cookie = implode('; ', func_get_args());
        $cookies = [];
        foreach (self::parseCookie($cookie) as $k => $v) {
            if (empty($k)) continue;
            if (preg_match('/^(domain|httponly|path|secure|expires)$/i', $k)) continue;
            $cookies[] = sprintf("%s=%s", $k, $v);
        }
        return implode("; ", $cookies);
    }

    /**
     * @param $url
     * @param null $append
     * @return string
     */
    public static function urlAppend($url, $append = NULL)
    {
        if (empty($append)) return $url;
        return $url
            . ((($pos = strpos($url, '?')) === false) ? '?' : ($pos < (strlen($url) - 1) ? '&' : ''))
            . (is_array($append) ? http_build_query($append) : $append);
    }

    /**
     * Concat url path
     *
     * @return string
     */
    public static function concat()
    {
        $pathsAry = [];
        foreach (func_get_args() as $path) {
            empty($path) || $pathsAry[] = rtrim($path, '\\/');
        }

        return implode('/', $pathsAry);
    }

    /**
     * According to the suffix returns the mime type type
     * @param $ext
     * @return string
     */
    public static function getMimeType($ext)
    {
        static $mimeTypes = NULL;//{suffix=>mimeType}

        if (is_null($mimeTypes)) {
            $mimeTypes = [];
            foreach (Tii::get('tii.http_mime_type_mapper', []) as $type => $types) {
                foreach ($types as $subtype => $suffixes) {
                    foreach ($suffixes as $suffix) {
                        $mimeTypes[$suffix] = $type . '/' . $subtype;
                    }
                }
            }
        }

        return Tii::valueInArray($mimeTypes, strtolower($ext), 'text/plain');
    }

    /**
     * Get Http status information
     *
     * @param int $code http code
     * @return string|null
     */
    public static function getHttpStatus($code = 200)
    {
        static $statuses = [
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',//This is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            507 => 'Insufficient Storage',
            509 => 'Bandwidth Limit Exceeded'
        ];

        return Tii::valueInArray($statuses, $code);
    }
}
