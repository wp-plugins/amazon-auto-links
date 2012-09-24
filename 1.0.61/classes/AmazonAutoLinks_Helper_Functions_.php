<?php
class AmazonAutoLinks_Helper_Functions_
{
	/* 
		This class is a set of functions(methods) that is called from all classes.
	*/
	
	public $classver = 'standard';
	protected $key = ''; 
	function __construct($pluginkey) {
		$this->pluginkey = $pluginkey;	// for translation textdomain
		$this->key = $pluginkey;	// for decrypt and encrypt strings
		$this->wp = & $GLOBALS["wp"];
	}
	public function selfURLwithoutQuery() {
		$arrURL = explode("?", $this->selfURL(), 2);
		return $arrURL[0];
	}
	public function selfURL() {	
		return 'http'
			. (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 's' : '')
			. '://'
			. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
	}
	public function RemoveLineFeeds($output) {
		$output = str_replace(array("\r\n", "\r"), "\n", $output);
		// return $output;
		$lines = explode("\n", $output);
		$new_lines = array();
		foreach ($lines as $i => $line) {
			if(!empty($line))
				$new_lines[] = trim($line, '\t\n\r\0\x0B');
		}
		return implode($new_lines);
	}
	
	/* for encrypting/decrupting string data */
	public function urlencrypt($urlstring) {
		return rawurlencode($this->encrypt($urlstring));
	}
	public function urldecrypt($encodedurlstring) {
		return $this->decrypt(rawurldecode($encodedurlstring));
	}
	public function encrypt($string) {
		// add [] because the ending character can contain = and it's not suitable for passing to a url such as a query value for the GET method.
		return '[' . base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->key), $string, MCRYPT_MODE_CBC, md5(md5($this->key)))) . ']';
	}
	public function decrypt($encryptedstring) {
		$encryptedstring = substr($encryptedstring, 1, -1);  	// remove the outer [].
		return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->key), base64_decode($encryptedstring), MCRYPT_MODE_CBC, md5(md5($this->key))), "\0");
	}	 
	function fixnum($numToFix, $numDefault, $numMin="", $numMax="") {
	
		// checks if the passed value is a number and set it to the default if not.
		// if it is a number and exceeds the set maximum number, it sets it to the max value.
		// if it is a number and is below the minimum number, it sets to the minimium value.
		// set a blank value for no limit
		if (!is_numeric(trim($numToFix))) 
			$numToFix = $numDefault;
		else if ($numMin != "" && $numToFix < $numMin) 
			$numToFix = $numMin;		
		else if ($numMax != "" && $numToFix > $numMax)
			$numToFix = $numMax;
		return $numToFix;
	}		
	function does_occur_in($numPercentage) {
		if (mt_rand(1, 100) <= $numPercentage)
			return true;
		else
			return false;
	}	
	
	function _e($translatingtext, $type='') {
		if ($type=='updated')
			echo '<div class="updated" style="padding: 10px; margin: 10px;">' . __($translatingtext, $this->textdomain) . '</div>';
		else if ($type=='error')
			echo '<div class="error" style="padding: 10px; margin: 10px;">' . __($translatingtext, $this->textdomain) . '</div>';
		else
			_e($translatingtext, $this->textdomain);
	}
	function __($translatingtext, $type='') {
		if ($type=='update')
			return '<div class="update">' . __($translatingtext, $this->textdomain) . '</div>';
		else if ($type=='error')
			return '<div class="error">' . __($translatingtext, $this->textdomain) . '</div>';
		else
			return __($translatingtext, $this->textdomain);
	}	
	
/*	if wp_remote_get() can be used, delete these: from here
	currently for an encoding issue, wp_remote_get() is not used.
*/	

	function iscurlon() {
		if  (in_array  ('curl', get_loaded_extensions()))
			return true;
		else
			return false;
	}	
	function get_html_wpapi($strURL) {
		$html = wp_remote_get($strURL); 
		if (is_wp_error( $html ))
			 return false;
		return $html['body'];
	}
	function get_html($strURL) {
		$html = $this->get_html_wpapi($strURL);
		if ($html)
			return $html;
		else if ($this->iscurlon()) 
			return $this->file_get_contents_curl($strURL);
		else
			return file_get_contents($strURL);
	}
	function file_get_contents_curl($strURL) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $strURL);
		if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);	// the maximum number of seconds to allow cURL functions to execute.
			
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data ? $data : file_get_contents($strURL);	// if failed to retrieve data for some reasons, use file_get_contents()
	}
/* Planning to delete to here */
	
	/* debugging methods */
	function print_r($arr, $strTitle="") {
		echo '<pre>';
		echo '<hr>';
		echo '<h4>' . $strTitle ? $strTitle : 'Debug: Showing the Array' . '</h4><br /><div>';
		htmlspecialchars(print_r($arr), TRUE);
		echo '</div><hr></pre>';
	}	
}
?>