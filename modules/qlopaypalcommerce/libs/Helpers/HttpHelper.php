<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
*/

class HttpHelper {

	public $_curl = null;
	public $_headers = array();

	public function __construct() {
		$this->_initCurl();
	}

	public function __destruct() {
		curl_close($this->_curl);
	}

	private function _initCurl() {
		if(!function_exists('curl_version')) {
			trigger_error("Curl not available", E_USER_ERROR);
		}
		else {
			$this->_curl = curl_init();
			$this->_setDefaults();
		}
	}

	private function _setDefaults() {
		curl_setopt($this->_curl, CURLOPT_VERBOSE, 1);
		curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($this->_curl, CURLOPT_SSLVERSION , 'CURL_SSLVERSION_TLSv1_2');
		curl_setopt($this->_curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->_curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($this->_curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($this->_curl, CURLOPT_HEADER, 1);
		curl_setopt($this->_curl, CURLINFO_HEADER_OUT, 1);
	}

	private function _setHeaders() {
		curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $this->_headers);
	}

	private function _sendRequest() {
		$this->_setHeaders();
		$result = curl_exec($this->_curl);
		if(curl_errno($this->_curl)){
			trigger_error("Request Error:" . curl_error($this->_curl), E_USER_WARNING);
		}
		$headerSize = curl_getinfo($this->_curl, CURLINFO_HEADER_SIZE);
		$body = substr($result, $headerSize);

		return Tools::jsonDecode($body, true);
	}

	public function resetHelper() {
		$this->_curl = null;
		$this->_initCurl();
		$this->_headers = array();
	}

	public function setUrl($url) {
		curl_setopt($this->_curl, CURLOPT_URL, $url);
	}

	public function setBody($postData) {
		if(is_array($postData)) {
			$postData = Tools::jsonEncode($postData);
		}
		curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($this->_curl, CURLOPT_POST, true);
		$this->setRequestType('POST');
	}

    public function setRequestType($type) {
        curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $type);
    }

	public function setAuthentication($authData) {
		curl_setopt($this->_curl, CURLOPT_USERPWD, $authData);
	}

	public function addHeader($header) {
		$this->_headers[] = $header;
	}

	public function sendRequest() {
		return $this->_sendRequest();
	}
}
