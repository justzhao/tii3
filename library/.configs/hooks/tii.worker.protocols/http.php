<?php
/**
 * Filter: tii.worker.protocols
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
 * @version $Id: http.php 8915 2017-11-05 03:38:45Z alacner $
 */
Tii_Event::register('tii.worker.protocols', function($protocols)
{
	$protocols['http'] = [
		'input' =>  function($buffer, $connection)
			{
				if (!strpos($buffer, "\r\n\r\n")) {
					// Judge whether the package length exceeds the limit.
					if (strlen($buffer) >= $connection->maxPackageSize) {
						$connection->close();
						return 0;
					}
					return 0;
				}

				list($header,) = explode("\r\n\r\n", $buffer, 2);
				if (0 === strpos($buffer, "POST")) {
					// find Content-Length
					$match = [];
					if (preg_match("/\r\nContent-Length: ?(\d+)/i", $header, $match)) {
						$content_length = $match[1];
						return $content_length + strlen($header) + 4;
					} else {
						return 0;
					}
				} elseif (0 === strpos($buffer, "GET")) {
					return strlen($header) + 4;
				} else {
					$connection->send("HTTP/1.1 400 Bad Request\r\n\r\n", true);
					return 0;
				}
			},
		'encode' => function ($stream, $connection)
			{
				list($state, $data, $headers) = $stream;
				$headers || $headers = [];
				$data || $data = '';
				$state || $state = 500;

				$headers['Server'] = "Tii/" . Tii_Version::VERSION;
				$headers['Date'] = Tii_Time::format("D, d M Y H:i:s")." GMT";
				$headers['Content-Type'] = "application/json; charset=utf-8";//text/html;charset=utf-8
				$headers['Expires'] = "Thu, 01 Jan 1970 00:00:01 GMT";
				$headers['Cache-Control'] = "Cache-Control: no-store, no-cache, must-revalidate";

				if ($status = Tii_Http::getHttpStatus($state)) {
					$protocol = 'HTTP/1.1 ' . $state . ' ' . $status;
					$headers['Status'] = $state . ' ' . $status;//FastCGI模式下正常
					$headers['Connection'] = "keep-alive";
					$headers['Content-Length'] = strlen($data);
				} else {
					$headers['Connection'] = "close";
					$protocol = 'HTTP/1.1 ' . 500 . ' ' . Tii_Http::getHttpStatus(500);
				}

				return Tii_Http::streamBuilder($protocol, $headers, $data);
			},
		'decode' => function ($buffer, $connection)
			{
				return Tii_Http::parser($buffer, $connection->getRemoteIp(), $connection->getRemotePort());
			},
	];

	return $protocols;
});