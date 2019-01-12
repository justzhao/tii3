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
 * @see http://en.wikipedia.org/wiki/WebSocket
 * @see http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-10
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: websocket.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Worker_Protocols_WebSocket_Abstract
{
	const MINIMUM_HEAD_LENGTH = 2;//Minimum head length of websocket protocol
	const BINARY_TYPE_BLOB = "\x81";//Websocket blob type.
	const BINARY_TYPE_ARRAYBUFFER = "\x82";//Websocket arraybuffer type.
}

class Tii_Worker_Protocols_WebSocket extends Tii_Worker_Protocols_WebSocket_Abstract
{
	/**
	 * Websocket handshake.
	 *
	 * @param string $buffer
	 * @param $connection
	 * @return int
	 */
	protected static function handshake($buffer, $connection)
	{
		if (0 === strpos($buffer, 'GET')) {// HTTP protocol
			$heder_end_pos = strpos($buffer, "\r\n\r\n");// Find \r\n\r\n.
			if (!$heder_end_pos) return 0;
			$header_length = $heder_end_pos + 4;

			if (preg_match("/Sec-WebSocket-Key: *(.*?)\r\n/i", $buffer, $match)) {//Get Sec-WebSocket-Key.
				$Sec_WebSocket_Key = $match[1];
			} else {
				$connection->send("HTTP/1.1 400 Bad Request\r\n\r\n<b>400 Bad Request</b><br>Sec-WebSocket-Key not found.<br>This is a WebSocket service and can not be accessed via HTTP.<br>See <a href='https://alacner.github.io/tii/#error-websocket-bad-request'>https://alacner.github.io/tii/#error-websocket-bad-request</a>",
					true);
				$connection->close();
				return 0;
			}

			$handshake_message = Tii_Http::streamBuilder("HTTP/1.1 101 Switching Protocols", [
				'Upgrade' => 'websocket',
				'Connection' => 'Upgrade',
				'Sec-WebSocket-Accept' => base64_encode(sha1($Sec_WebSocket_Key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true)),
				//'Sec-WebSocket-Protocol' => 'chat',
			]);
			// Mark handshake complete..
			$connection->websocketHandshake = true;
			// Websocket data buffer.
			$connection->websocketDataBuffer = '';
			// Current websocket frame length.
			$connection->websocketCurrentChunkLength = 0;
			// Current websocket frame data.
			$connection->websocketCurrentFrameBuffer = '';
			// Consume handshake data.
			$connection->consumeRecvBuffer($header_length);
			// Send handshake response.
			$connection->send($handshake_message, true);

			// There are data waiting to be sent.
			if (!empty($connection->tmpWebsocketData)) {
				$connection->send($connection->tmpWebsocketData, true);
				$connection->tmpWebsocketData = '';
			}
			// blob or arraybuffer
			if (empty($connection->websocketType)) {
				$connection->websocketType = self::BINARY_TYPE_BLOB;
			}
			// Try to emit onWebSocketConnect callback.
			if (isset($connection->onWebSocketConnect)) {
				$connection->onWebSocketConnect($buffer);
			}
			if (strlen($buffer) > $header_length) {
				return self::input(substr($buffer, $header_length), $connection);
			}
			return 0;
		} elseif (0 == strpos($buffer, '<policy-file-request/>')) {// Is flash policy-file-request
			$connection->send('<?xml version="1.0"?><cross-domain-policy><site-control permitted-cross-domain-policies="all"/><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>' . "\0",
				true);
			$connection->consumeRecvBuffer(strlen($buffer));
			return 0;
		}
		// Bad websocket handshake request.
		$connection->send("HTTP/1.1 400 Bad Request\r\n\r\n<b>400 Bad Request</b><br>Invalid handshake data for websocket. ",
			true);
		$connection->close();
		return 0;
	}

	/**
	 * Check the integrity of the package.
	 *
	 * @param string $buffer
	 * @param $connection
	 * @return int
	 */
	public static function input($buffer, $connection)
	{
		// Receive length.
		$recv_len = strlen($buffer);
		// We need more data.
		if ($recv_len < self::MINIMUM_HEAD_LENGTH) {
			return 0;
		}

		// Has not yet completed the handshake.
		if (empty($connection->websocketHandshake)) {
			return self::handshake($buffer, $connection);
		}

		// Buffer websocket frame data.
		if ($connection->websocketCurrentChunkLength) {
			// We need more frame data.
			if ($connection->websocketCurrentChunkLength > $recv_len) {
				// Return 0, because it is not clear the full packet length, waiting for the frame of fin=1.
				return 0;
			}
		} else {
			$data_len = ord($buffer[1]) & 127;
			$firstbyte = ord($buffer[0]);
			$is_fin_frame = $firstbyte >> 7;
			$opcode = $firstbyte & 0xf;
			switch ($opcode) {
				case 0x0:
					break;
				// Blob type.
				case 0x1:
					break;
				// Arraybuffer type.
				case 0x2:
					break;
				// Close package.
				case 0x8:
					// Try to emit onWebSocketClose callback.
					if (isset($connection->onWebSocketClose)) {
						$connection->onWebSocketClose();
					} // Close connection.
					else {
						$connection->close();
					}
					return 0;
				// Ping package.
				case 0x9:
					// Try to emit onWebSocketPing callback.
					if (isset($connection->onWebSocketPing)) {
						$connection->onWebSocketPing();
					} // Send pong package to client.
					else {
						$connection->send(pack('H*', '8a00'), true);
					}

					// Consume data from receive buffer.
					if (!$data_len) {
						$connection->consumeRecvBuffer(self::MINIMUM_HEAD_LENGTH);
						if ($recv_len > self::MINIMUM_HEAD_LENGTH) {
							return self::input(substr($buffer, self::MINIMUM_HEAD_LENGTH), $connection);
						}
						return 0;
					}
					break;
				// Pong package.
				case 0xa:
					// Try to emit onWebSocketPong callback.
					if (isset($connection->onWebSocketPong)) {
						$connection->onWebSocketPong();
					}
					// Consume data from receive buffer.
					if (!$data_len) {
						$connection->consumeRecvBuffer(self::MINIMUM_HEAD_LENGTH);
						if ($recv_len > self::MINIMUM_HEAD_LENGTH) {
							return self::input(substr($buffer, self::MINIMUM_HEAD_LENGTH), $connection);
						}
						return 0;
					}
					break;
				// Wrong opcode.
				default :
					Tii_Logger::debug("error opcode %s and close websocket connection. Buffer: %s",
						$opcode, bin2hex($buffer)
					);
					$connection->close();
					return 0;
			}

			// Calculate packet length.
			$head_len = 6;
			if ($data_len === 126) {
				$head_len = 8;
				if ($head_len > $recv_len) {
					return 0;
				}
				$pack = unpack('nn/ntotal_len', $buffer);
				$data_len = $pack['total_len'];
			} else {
				if ($data_len === 127) {
					$head_len = 14;
					if ($head_len > $recv_len) {
						return 0;
					}
					$arr = unpack('n/N2c', $buffer);
					$data_len = $arr['c1']*4294967296 + $arr['c2'];
				}
			}
			$current_frame_length = $head_len + $data_len;
			if ($is_fin_frame) {
				return $current_frame_length;
			} else {
				$connection->websocketCurrentChunkLength = $current_frame_length;
			}
		}

		// Received just a frame length data.
		if ($connection->websocketCurrentChunkLength === $recv_len) {
			self::decode($buffer, $connection);
			$connection->consumeRecvBuffer($connection->websocketCurrentChunkLength);
			$connection->websocketCurrentChunkLength = 0;
			return 0;
		} // The length of the received data is greater than the length of a frame.
		elseif ($connection->websocketCurrentChunkLength < $recv_len) {
			self::decode(substr($buffer, 0, $connection->websocketCurrentChunkLength), $connection);
			$connection->consumeRecvBuffer($connection->websocketCurrentChunkLength);
			$current_frame_length = $connection->websocketCurrentChunkLength;
			$connection->websocketCurrentChunkLength = 0;
			// Continue to read next frame.
			return self::input(substr($buffer, $current_frame_length), $connection);
		} // The length of the received data is less than the length of a frame.
		else {
			return 0;
		}
	}

	/**
	 * Websocket encode.
	 *
	 * @param string $buffer
	 * @param $connection
	 * @return string
	 * @throws Tii_Exception
	 */
	public static function encode($buffer, $connection)
	{
		if (!is_scalar($buffer)) {
			throw new Tii_Exception("You can't send(%s) to client, you need to convert it to a string. ", gettype($buffer));
		}
		$len = strlen($buffer);
		if (empty($connection->websocketType)) {
			$connection->websocketType = self::BINARY_TYPE_BLOB;
		}

		$first_byte = $connection->websocketType;

		if ($len <= 125) {
			$encode_buffer = $first_byte . chr($len) . $buffer;
		} else {
			if ($len <= 65535) {
				$encode_buffer = $first_byte . chr(126) . pack("n", $len) . $buffer;
			} else {
				$encode_buffer = $first_byte . chr(127) . pack("xxxxN", $len) . $buffer;
			}
		}

		// Handshake not completed so temporary buffer websocket data waiting for send.
		if (empty($connection->websocketHandshake)) {
			if (empty($connection->tmpWebsocketData)) {
				$connection->tmpWebsocketData = '';
			}
			$connection->tmpWebsocketData .= $encode_buffer;
			// Return empty string.
			return '';
		}

		return $encode_buffer;
	}

	/**
	 * Websocket decode
	 *
	 * @param string $buffer
	 * @param $connection
	 * @return string
	 */
	public static function decode($buffer, $connection)
	{
		$decoded = null;
		$len = ord($buffer[1]) & 127;
		if ($len === 126) {
			$masks = substr($buffer, 4, 4);
			$data = substr($buffer, 8);
		} else {
			if ($len === 127) {
				$masks = substr($buffer, 10, 4);
				$data = substr($buffer, 14);
			} else {
				$masks = substr($buffer, 2, 4);
				$data = substr($buffer, 6);
			}
		}

		for ($index = 0; $index < strlen($data); $index++) {
			$decoded .= $data[$index] ^ $masks[$index % 4];
		}

		if ($connection->websocketCurrentChunkLength) {
			$connection->websocketDataBuffer .= $decoded;
			return $connection->websocketDataBuffer;
		} else {
			if ($connection->websocketDataBuffer !== '') {
				$decoded = $connection->websocketDataBuffer . $decoded;
				$connection->websocketDataBuffer = '';
			}
			return $decoded;
		}
	}
}

class Tii_Worker_Protocols_Ws extends Tii_Worker_Protocols_WebSocket_Abstract
{
	private static function handshake($connection)
	{
		if (!empty($connection->handshakeStep)) return;

		$port = $connection->getRemotePort();
		$header = Tii_Http::streamBuilder("GET {$connection->getRemoteURI()} HTTP/1.1", [//Handshake header
			'Host' => ($port === 80 ? $connection->getRemoteHost() : $connection->getRemoteHost() . ':' . $port),
			'Upgrade' => 'websocket',
			'Connection' => 'Upgrade',
			'Sec-WebSocket-Key' => base64_encode(sha1(uniqid(mt_rand(), true), true)),
			'Origin' => (isset($connection->websocketOrigin) ? $connection->websocketOrigin : '*'),
			//'Sec-WebSocket-Protocol' => 'chat, superchat',
			'Sec-WebSocket-Version' => 13,
		]);

		$connection->send($header, true);
		$connection->handshakeStep = 1;
		$connection->websocketCurrentChunkLength = 0;
		$connection->websocketDataBuffer = '';
	}

	private static function handshaking($buffer, $connection)
	{
		$pos = strpos($buffer, "\r\n\r\n");
		if ($pos) {
			// handshake complete
			$connection->handshakeStep = 2;
			$response_length = $pos + 4;
			// Try to emit onWebSocketConnect callback.
			$connection->onWebSocketConnect(substr($buffer, 0, $response_length));
			// Headbeat
			if (!empty($connection->websocketPingInterval)) {
				$connection->websocketPingTimer = Tii_Worker_Timer::add(
					$connection->websocketPingInterval,
					function() use ($connection)
					{
						if (false === $connection->send(pack('H*', '8900'), true)) {
							Tii_Worker_Timer::delete($connection->websocketPingTimer);
							$connection->websocketPingTimer = null;
						}
					});
			}

			$connection->consumeRecvBuffer($response_length);
			if (!empty($connection->tmpWebsocketData)) {
				$connection->send($connection->tmpWebsocketData, true);
				$connection->tmpWebsocketData = '';
			}
			if (strlen($buffer) > $response_length) {
				return self::input(substr($buffer, $response_length), $connection);
			}
		}
		return 0;
	}

	public static function input($buffer, $connection)
	{
		if (empty($connection->handshakeStep)) {
			Tii_Logger::debug("recv data before handshake. Buffer: %s", bin2hex($buffer));
			return false;
		}
		// Recv handshake response
		if ($connection->handshakeStep === 1) {
			return self::handshaking($buffer, $connection);
		}
		$recv_len = strlen($buffer);
		if ($recv_len < self::MINIMUM_HEAD_LENGTH) {
			return 0;
		}
		// Buffer websocket frame data.
		if ($connection->websocketCurrentChunkLength) {
			// We need more frame data.
			if ($connection->websocketCurrentChunkLength > $recv_len) {
				// Return 0, because it is not clear the full packet length, waiting for the frame of fin=1.
				return 0;
			}
		} else {
			$data_len = ord($buffer[1]) & 127;
			$firstbyte = ord($buffer[0]);
			$is_fin_frame = $firstbyte >> 7;
			$opcode = $firstbyte & 0xf;
			switch ($opcode) {
				case 0x0:
					break;
				// Blob type
				case 0x1:
					break;
				// Arraybuffer type
				case 0x2:
					break;
				// Close package
				case 0x8:
					// Try to emit onWebSocketClose callback
					if (isset($connection->onWebSocketClose)) {
						$connection->onWebSocketClose();
					} // Close connection.
					else {
						$connection->close();
					}
					return 0;
				// Ping package.
				case 0x9:
					// Try to emit onWebSocketPing callback.
					if (isset($connection->onWebSocketPing)) {
						$connection->onWebSocketPing();
					} // Send pong package to client.
					else {
						$connection->send(pack('H*', '8a00'), true);
					}
					// Consume data from receive buffer.
					if (!$data_len) {
						$connection->consumeRecvBuffer(self::MINIMUM_HEAD_LENGTH);
						if ($recv_len > self::MINIMUM_HEAD_LENGTH) {
							return self::input(substr($buffer, self::MINIMUM_HEAD_LENGTH), $connection);
						}
						return 0;
					}
					break;
				// Pong package.
				case 0xa:
					// Try to emit onWebSocketPong callback.
					if (isset($connection->onWebSocketPong)) {
						$connection->onWebSocketPong();
					}
					// Consume data from receive buffer.
					if (!$data_len) {
						$connection->consumeRecvBuffer(self::MINIMUM_HEAD_LENGTH);
						if ($recv_len > self::MINIMUM_HEAD_LENGTH) {
							return self::input(substr($buffer, self::MINIMUM_HEAD_LENGTH), $connection);
						}
						return 0;
					}
					break;
				// Wrong opcode.
				default :
					Tii_Logger::debug("error opcode %s and close websocket connection. Buffer: %s",
						$opcode, bin2hex($buffer)
					);
					$connection->close();
					return 0;
			}
			// Calculate packet length.
			if ($data_len === 126) {
				if (strlen($buffer) < 6) {
					return 0;
				}
				$pack = unpack('nn/ntotal_len', $buffer);
				$current_length = $pack['total_len'] + 4;
			} else if ($data_len === 127) {
				if (strlen($buffer) < 10) {
					return 0;
				}
				$arr = unpack('n/N2c', $buffer);
				$current_length = $arr['c1']*4294967296 + $arr['c2'] + 10;
			} else {
				$current_length = $data_len + 2;
			}
			if ($is_fin_frame) {
				return $current_length;
			} else {
				$connection->websocketCurrentChunkLength = $current_length;
			}
		}
		// Received just a chunk length data.
		if ($connection->websocketCurrentChunkLength === $recv_len) {
			self::decode($buffer, $connection);
			$connection->consumeRecvBuffer($connection->websocketCurrentChunkLength);
			$connection->websocketCurrentChunkLength = 0;
			return 0;
		} // The length of the received data is greater than the length of a chunk.
		elseif ($connection->websocketCurrentChunkLength < $recv_len) {
			self::decode(substr($buffer, 0, $connection->websocketCurrentChunkLength), $connection);
			$connection->consumeRecvBuffer($connection->websocketCurrentChunkLength);
			$current_length = $connection->websocketCurrentChunkLength;
			$connection->websocketCurrentChunkLength = 0;
			// Continue to read next chunk
			return self::input(substr($buffer, $current_length), $connection);
		} // The length of the received data is less than the length of a frame.
		else {
			return 0;
		}
	}

	/**
	 * Websocket encode.
	 *
	 * @param string $buffer
	 * @param $connection
	 * @return string
	 */
	public static function encode($buffer, $connection)
	{
		if (empty($connection->websocketType)) {
			$connection->websocketType = self::BINARY_TYPE_BLOB;
		}
		$buffer = (string)$buffer;
		if (empty($connection->handshakeStep)) {
			self::handshake($connection);
		}
		$mask = 1;
		$mask_key = "\x00\x00\x00\x00";

		$pack = '';
		$length = $length_flag = strlen($buffer);
		if (65535 < $length) {
			$pack = pack('NN', ($length & 0xFFFFFFFF00000000) >> 32, $length & 0x00000000FFFFFFFF);
			$length_flag = 127;
		} else if (125 < $length) {
			$pack = pack('n*', $length);
			$length_flag = 126;
		}

		$head = ($mask << 7) | $length_flag;
		$head = $connection->websocketType . chr($head) . $pack;

		$chunk = $head . $mask_key;
		
		for ($i = 0; $i < $length; $i++) {
			$chunk .= $buffer[$i] ^ $mask_key[$i % 4];
		}
		if ($connection->handshakeStep === 1) {
			$connection->tmpWebsocketData = isset($connection->tmpWebsocketData) ? $connection->tmpWebsocketData . $chunk : $chunk;
			return '';
		}
		return $chunk;
	}

	/**
	 * Websocket decode.
	 *
	 * @param string $buffer
	 * @param $connection
	 * @return string
	 */
	public static function decode($buffer, $connection)
	{
		$masked = $buffer[1] >> 7;
		$data_length = $masked ? ord($buffer[1]) & 127 : ord($buffer[1]);
		$decoded_data = '';
		if ($masked === true) {
			if ($data_length === 126) {
				$mask = substr($buffer, 4, 4);
				$coded_data = substr($buffer, 8);
			} else if ($data_length === 127) {
				$mask = substr($buffer, 10, 4);
				$coded_data = substr($buffer, 14);
			} else {
				$mask = substr($buffer, 2, 4);
				$coded_data = substr($buffer, 6);
			}
			for ($i = 0; $i < strlen($coded_data); $i++) {
				$decoded_data .= $coded_data[$i] ^ $mask[$i % 4];
			}
		} else {
			if ($data_length === 126) {
				$decoded_data = substr($buffer, 4);
			} else if ($data_length === 127) {
				$decoded_data = substr($buffer, 10);
			} else {
				$decoded_data = substr($buffer, 2);
			}
		}
		if ($connection->websocketCurrentChunkLength) {
			$connection->websocketDataBuffer .= $decoded_data;
			return $connection->websocketDataBuffer;
		} else {
			if ($connection->websocketDataBuffer !== '') {
				$decoded_data = $connection->websocketDataBuffer . $decoded_data;
				$connection->websocketDataBuffer = '';
			}
			return $decoded_data;
		}
	}

	/**
	 * Send websocket handshake data.
	 *
	 * @param $connection
	 */
	public static function onConnect($connection)
	{
		self::handshake($connection);
	}

	/**
	 * Clean
	 *
	 * @param $connection
	 */
	public static function onClose($connection)
	{
		$connection->handshakeStep = null;
		$connection->websocketCurrentChunkLength = 0;
		$connection->tmpWebsocketData = '';
		$connection->websocketDataBuffer = '';
		if (!empty($connection->websocketPingTimer)) {
			Tii_Worker_Timer::delete($connection->websocketPingTimer);
			$connection->websocketPingTimer = null;
		}
	}
}

Tii_Event::register('tii.worker.protocols', function($protocols)
{
	$protocols['websocket'] = [
		'input' => ['Tii_Worker_Protocols_WebSocket', 'input'],
		'encode' => ['Tii_Worker_Protocols_WebSocket', 'encode'],
		'decode' => ['Tii_Worker_Protocols_WebSocket', 'decode'],
	];

	$protocols['ws'] = [
		'input' => ['Tii_Worker_Protocols_Ws', 'input'],
		'encode' => ['Tii_Worker_Protocols_Ws', 'encode'],
		'decode' => ['Tii_Worker_Protocols_Ws', 'decode'],
		'onConnect' => ['Tii_Worker_Protocols_Ws', 'onConnect'],
		'onClose' => ['Tii_Worker_Protocols_Ws', 'onClose'],
	];

	return $protocols;
});