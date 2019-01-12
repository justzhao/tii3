<?php
/**
 * 阿里云云存储的封装
 * @author Alacner Zhang <alacner@gmail.com> 2011-04-23
 * @version $Id: AliyunOss.php 736 2012-07-20 00:35:03Z alacner $
 */

class AliyunOssApi {
	protected $accessID;
	protected $accessKey;
	protected $headers = array();
	protected $xOss = array();
	protected $restUrl = 'http://storage.aliyun-inc.com'; //SERVER IP : '10.249.30.8','10.249.30.7','10.249.30.5','10.249.13.20','10.249.13.19','10.249.12.21'
	//protected $restUrl = 'http://10.249.30.8:8080';
	protected $bucket = '/';
	
	/**
	 * Enter description here ...
	 * @param string $accessID
	 * @param string $accessKey
	 * @return Desire_Api_AliyunOss
	 */
	public function setAccessKey($accessID, $accessKey) {
		$this->accessID = $accessID;
		$this->accessKey = $accessKey;
		return $this;
	}
	
	public function setHeader($key, $value) {
		$this->headers[$key] = $value;
		return $this;
	}
	
	/**
	 * Enter description here ...
	 * @param unknown_type $bucket
	 * @return Desire_Api_AliyunOss
	 */
	public function setBucketPath($bucket = '/') {
		$this->bucket = $this->getBucketPath($bucket);
		return $this;
	}
	
	public function getBucketPath($bucket = null) {
		is_null($bucket) && $bucket = $this->bucket;
		return '/' . trim($bucket, '/');
	}
	
	public function getBucketUrl($bucket = null) {
		return $this->restUrl . '/oss' . $this->getBucketPath($bucket);
	}
	
	public function getObjectPath($object, $bucket = null) {
		return $this->getBucketPath($bucket) . '/' . trim($object, '/');
	}
	
	public function getObjectUrl($object, $bucket = null) {
		return $this->getBucketUrl($bucket) . '/' . trim($object, '/');
	}
	
	/**
	 * From object to array.
	 */
	public function toArray($data) {
		if (is_object($data)) $data = get_object_vars($data);
		return is_array($data) ? array_map(array($this, 'toArray'), $data) : $data;
	}
	
	public function result($response, $literal = false) {
		if ($response->state != 200) {
			throw new Exception($response->data);
		}
		
		if ($literal) return $response->data;
		
		$respObject = @simplexml_load_string($response->data);
		return $this->toArray($respObject);
	}
	
	public function setAuthorization($method, $resource = '/') {
		$this->headers['Date'] = date('D, d M Y H:i:s \G\M\T', time() - date('Z'));
		isset($this->headers['Content-Type']) || $this->headers['Content-Type'] = '';
		
		$signStr = sprintf("%s\n%s\n%s\n%s\n%s",
			$method,
			'',//$this->headers['Content-Md5'],
			$this->headers['Content-Type'],
			$this->headers['Date'],
			$resource
		);
		//var_dump($signStr);
		$sign = hash_hmac('sha1', $signStr, $this->accessKey, true);
		
		$this->headers['Authorization'] = sprintf('OSS %s:%s',
			$this->accessID,
			base64_encode($sign)
		);
	}
	
	public function getService() {
		$this->setAuthorization('GET');
		$response = Desire_Http::get($this->restUrl, $this->headers);
		return $this->result($response);
	}
	
	public function putBucket($bucket) {
		$this->setAuthorization('PUT', $this->getBucketPath($bucket));
		$response = Desire_Http::get($this->getBucketUrl($bucket), $this->headers, 5, 'PUT');
		return $this->result($response);
	}
	
	public function getBucket($bucket) {
		$this->setAuthorization('GET', $this->getBucketPath($bucket));
		$response = Desire_Http::get($this->getBucketUrl($bucket), $this->headers);
		return $this->result($response);
	}
	
	public function getBucketACL($bucket) {
		$this->setAuthorization('GET', $this->getBucketPath($bucket));
		$response = Desire_Http::get($this->getBucketUrl($bucket).'?acl', $this->headers);
		return $this->result($response);
	}
	
	public function deleteBucket($bucket) {
		$this->setAuthorization('DELETE', $this->getBucketPath($bucket));
		$response = Desire_Http::get($this->getBucketUrl($bucket), $this->headers, 5, 'DELETE');
		return $this->result($response);
	}
	
	public function putObject($object, $data, $bucket = null, $type = null) {
		if ($type) {
			$this->setHeader("Content-Type", $type);
		} else {
			$type = Desire_Http::getMimeType(substr(strrchr($object, "."), 1));
			$this->setHeader("Content-Type", $type);
		}
		$this->setHeader("Expect", "100-Continue");
		$this->setAuthorization('PUT', $this->getObjectPath($object, $bucket));
		$response = Desire_Http::post($this->getObjectUrl($object, $bucket), $data, $this->headers, 5, 'PUT');
		if (!in_array($response->state, array(100, 200))) return false;
		if (preg_match('/x-oss-request-id/i', $response->data)) return true;
		return false;
	}
	
	public function getObject($object, $bucket = null) {
		$this->setAuthorization('GET', $this->getObjectPath($object, $bucket));
		$response = Desire_Http::get($this->getObjectUrl($object, $bucket), $this->headers);
		return $this->result($response, true);
	}
	
	public function headObject($object, $bucket = null) {
		$this->setAuthorization('HEAD', $this->getObjectPath($object, $bucket));
		$response = Desire_Http::get($this->getObjectUrl($object, $bucket), $this->headers, 5, 'HEAD');
		return $response->headers;
	}
	
	public function deleteObject($object, $bucket = null) {
		$this->setAuthorization('DELETE', $this->getObjectPath($object, $bucket));
		$response = Desire_Http::get($this->getObjectUrl($object, $bucket), $this->headers, 5, 'DELETE');
		return $this->result($response);
	}
}