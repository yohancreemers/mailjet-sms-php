<?php
/**
 * Mailjet SMS API
 * https://dev.mailjet.com/sms/guides/
 *
 * List of status codes
 * https://dev.mailjet.com/sms/guides/sms-stats/#list-of-status-codes
**/

class Mailjetsms{

	const API_BASE_URL      = 'https://api.mailjet.com/v4/';
	const METHOD_SEND_SMS   = 'sms-send';

	protected $sApikey;
	protected $aOptions = [
		'from' => null,
		'countrycode' => null,
	];

	/**
	 * Create a new instance
	 *
	 * @param string  sApikey     Your API Key
	 * @param array   options     Optional parameters for sending SMS
	 * @return instance
	 */
	public function __construct($sApikey, array $aOptions=array()){
		if( !extension_loaded('curl') ){
			throw new Exception('Mailjet requires cURL support');
		}

		if( !$this->_isValidApikey($sApikey) ){
			throw new MailjetsmsException('A valid API Key is required');
		}

		$this->sApikey = $sApikey;

		foreach($aOptions as $sKey=>$value){
			$this->$sKey = $value;
		}

		return $this;
	}

	/**
	 * Get the value of a protected option
	 * @return mixed
	 */
	public function __get($sKey){
		if( array_key_exists($sKey, $this->aOptions) ){
			return $this->aOptions[$sKey];
		}
	}

	/**
	 * Determine if a protected option is declared and is different than NULL
	 * @return boolean
	 */
	public function __isset($sKey){
		return isset($this->aOptions[$sKey]);
	}

	/**
	 * Set the value of a protected option
	 * @return instance
	 */
	public function __set($sKey, $value){
		if( array_key_exists($sKey, $this->aOptions) ){
			switch($sKey){
				case 'from':
					//from: max 11 characters or 12 numbers
					$value = substr($value, 0, 1 === preg_match('/^\d+$/', $value) ? 12 : 11);
					break;
			}
			$this->aOptions[$sKey] = $value;
		}
		return $this;
	}

	/**
	 * Check if an array is associative
	 *
	 * @param array $a Array to check
	 * @return  bool
	 */
	protected function is_assoc(array $a) {
		return 0 < count(array_filter(array_keys($a), 'is_string'));
	}

	/**
	 * Send an sms
	 * @param array $aData   valid keys: to, message|text, [from]
	 * @return object        Response returned by server
	 */
	public function send(array $aData) {
		if( !$this->is_assoc($aData) ){
			throw new MailjetsmsException('This class only supports sending one message at a time');
		}

		if( empty($aData['to'])  ){
			throw new MailjetsmsException('No recipient provided');
		}
		$aData['to'] = $this->_normlizeNumber($aData['to']);

		if( !$this->_isValidNumber($aData['to']) ){
			throw new MailjetsmsException('Invalid recipient number');
		}

		if( empty($aData['text']) && !empty($aData['message']) ){
			$aData['text'] = $aData['message'];
			unset($aData['message']);
		}

		if( empty($aData['text']) ){
			throw new MailjetsmsException('No message provided');
		}

		if( empty($aData['from']) ){
			$aData['from'] = $this->from;
		}
		if( empty($aData['from']) ){
			throw new MailjetsmsException('No originator provided');
		}


 		return $this->_post(self::METHOD_SEND_SMS, $aData);
	}

	private static function _isValidApikey($value){
		$sPattern = '/[[:xdigit:]]{32}/';
		return is_scalar($value) && 1 === preg_match($sPattern, $value);
	}

	/**
	 * Check if a string looks like a valid phone number
	 *
	 * @param string $value Value to check
	 * @return bool
	 */
	private static function _isValidNumber($value){
		$sPattern = '/^\+[1-9][0-9]{7,12}$/';
		return is_scalar($value) && 1 === preg_match($sPattern, $value);
	}

	/**
	 * Normalize valid phone number
	 *
	 * @param string $value Value to normalize
	 * @return string
	 */
	private function _normlizeNumber($value){
		//remove any parentheses and the numbers they contain
		$value = preg_replace('/\(\d+\)/', '', $value);
		//strip anything but numbers and + sign
		$value = preg_replace('/[^+\d]/', '', $value);
		//replace leading 00 by +
		$value = preg_replace('/^00/', '+', $value);

		if( substr($value, 0, 1) === '0' && !empty($this->countrycode) ){
			//prefix with default country code
			$value = '+' . $this->countrycode . substr($value, 1);
		}elseif( substr($value, 0, 1) !== '+' ){
			$value = '+' . $value;
		}
		return $value;
	}

	/**
	 * POST data to API
	 *
	 * @requires cURL
	 * @param string $sMethod  API endpoint
	 * @param array $aData     Data to POST
	 * @return object          Response returned by server
	 */
	private function _post($sMethod, array $aData){
		$sApiUrl = self::API_BASE_URL . $sMethod;
		$sData = json_encode($aData);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $sApiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $this->sApikey,
			'Content-Type: application/json'
		]);

		$oResponse = null;
		$sError = null;
		if( $sJson = curl_exec($ch) ){
			//received a response
			$oResponse = json_decode($sJson);
			if( is_null($oResponse) ){
				$sError = 'Received an invalid response';
			}elseif( !empty($oResponse->ErrorCode) ){
				//message rejected by API
				/*{
					"ErrorIdentifier": "01234567-89ab-cdef-0123-456789abcdef"
					"ErrorCode": "sms-0001"
					"StatusCode": "400"
					"ErrorMessage": "Insufficient funds."
				}*/
				$oResponse->success = false;
			}elseif( !empty($oResponse->Status) ){
				//message accepted by API
				/*{
					"From": "",
					"To": "+31600000000",
					"Text": "",
					"MessageId": "2012345678901234567",
					"SmsCount": 1,
					"CreationTS": 1521626400,
					"SentTS": 1521626402,
					"Cost": {"Value": 0.0012, "Currency": "EUR"},
					"Status": {"Code": 2, "Name": "sent", "Description": "Message sent"}
				}*/
				$oResponse->success = $oResponse->Status->Code < 4;
				if( !$oResponse->success ){
					$oResponse->ErrorCode = $oResponse->Status->Code;
					$oResponse->ErrorMessage = $oResponse->Status->Description;
				}

			}
		}else{
			$sError = sprintf('[%d] %s', curl_error($ch), curl_error($ch));
		}
		curl_close($ch);

		if( !empty($sError) ){
			throw new MailjetsmsException('HTTP/cURL error calling API: ' . $sError);
		}

		return $oResponse;
	}

}

class MailjetsmsException extends Exception {
	public function __construct($message, $code = 0) {
		parent::__construct('Mailjet SMS/' . $message, $code);
	}
}
