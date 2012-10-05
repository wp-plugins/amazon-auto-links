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
	
	/* for getting external web pages */
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
	
	/* For detecting user's country */
	public $arrCountries = array(
		// since v1.0.7
		"AF" => "", 	// "Afghanistan",
		"AX" => "", 	// "Aland Islands",
		"AL" => "", 	// "Albania",
		"DZ" => "", 	// "Algeria",
		"AS" => "", 	// "American Samoa",
		"AD" => "", 	// "Andorra",
		"AO" => "", 	// "Angola",
		"AI" => "", 	// "Anguilla",
		"AQ" => "", 	// "Antarctica",
		"AG" => "", 	// "Antigua and Barbuda",
		"AR" => array( 'lang' => 'ES', 'name' => 'Argentina', 'needle' => 'es'),	// "Argentina",
		"AM" => "", 	// "Armenia",
		"AW" => "", 	// "Aruba",
		"AP" => "", 	// "Asia/Pacific Region",
		"AU" => array( 'lang' => 'US', 'name' => 'Australia', 'needle' => ''),  	// "Australia",
		"AT" => array( 'lang' => 'DE', 'name' => 'Austria', 'needle' => ''), 	// "Austria",
		"AZ" => "", 	// "Azerbaijan",
		"BS" => "", 	// "Bahamas",
		"BH" => "", 	// "Bahrain",
		"BD" => "", 	// "Bangladesh",
		"BB" => "", 	// "Barbados",
		"BY" => "", 	// "Belarus",
		"BE" => "", 	// "Belgium",
		"BZ" => "", 	// "Belize",
		"BJ" => "", 	// "Benin",
		"BM" => "", 	// "Bermuda",
		"BT" => "", 	// "Bhutan",
		"BO" => array( 'lang' => 'ES', 'name' => 'Bolivia', 'needle' => 'es'), 	// "Bolivia",
		"BA" => "", 	// "Bosnia and Herzegovina",
		"BW" => "", 	// "Botswana",
		"BV" => "", 	// "Bouvet Island",
		"BR" => array( 'lang' => 'ES', 'name' => 'Brazil', 'needle' => 'es'), 	// "Brazil",
		"IO" => "", 	// "British Indian Ocean Territory",
		"BN" => "", 	// "Brunei Darussalam",
		"BG" => "", 	// "Bulgaria",
		"BF" => "", 	// "Burkina Faso",
		"BI" => "", 	// "Burundi",
		"KH" => "", 	// "Cambodia",
		"CM" => "", 	// "Cameroon",
		"CA" => array( 'lang' => 'CA', 'name' => 'Canada', 'needle' => ''), 	// "Canada",
		"CV" => "", 	// "Cape Verde",
		"KY" => "", 	// "Cayman Islands",
		"CF" => "", 	// "Central African Republic",
		"TD" => "", 	// "Chad",
		"CL" => array( 'lang' => 'ES', 'name' => 'Chile', 'needle' => 'es'),	// "Chile",
		"CN" => array( 'lang' => 'CN', 'name' => 'China', 'needle' => 'zh'), 	// "China",
		"CX" => "", 	// "Christmas Island",
		"CC" => "", 	// "Cocos (Keeling) Islands",
		"CO" => array( 'lang' => 'ES', 'name' => 'Colombia', 'needle' => 'es'), 	// "Colombia",
		"KM" => "", 	// "Comoros",
		"CG" => "", 	// "Congo",
		"CD" => "", 	// "Congo  The Democratic Republic of the",
		"CK" => "", 	// "Cook Islands",
		"CR" => array( 'lang' => 'ES', 'name' => 'Costa Rica', 'needle' => 'es'), 	// "Costa Rica",
		"CI" => "", 	// "Cote d'Ivoire",
		"HR" => "", 	// "Croatia",
		"CU" => array( 'lang' => 'ES', 'name' => 'Cuba', 'needle' => 'es'), 	// "Cuba",
		"CY" => "", 	// "Cyprus",
		"CZ" => "", 	// "Czech Republic",
		"DK" => array( 'lang' => 'DE', 'name' => 'Denmark', 'needle' => 'de'),	 	// "Denmark",
		"DJ" => "", 	// "Djibouti",
		"DM" => array( 'lang' => 'ES', 'name' => 'Dominica', 'needle' => 'es'), 	// "Dominica",
		"DO" => array( 'lang' => 'ES', 'name' => 'Dominican Republic', 'needle' => 'es'), 	// "Dominican Republic",
		"EC" => array( 'lang' => 'ES', 'name' => 'Ecuador', 'needle' => 'es'), 	// "Ecuador",
		"EG" => "", 	// "Egypt",
		"SV" => array( 'lang' => 'ES', 'name' => 'El Salvador', 'needle' => 'es'), 	// "El Salvador",
		"GQ" => array( 'lang' => 'ES', 'name' => 'Equatorial Guinea', 'needle' => 'es'), 	// "Equatorial Guinea",
		"ER" => "", 	// "Eritrea",
		"EE" => "", 	// "Estonia",
		"ET" => "", 	// "Ethiopia",
		"EU" => array( 'lang' => 'UK', 'name' => 'Europe', 'needle' => ''),  	// "Europe",
		"FK" => "", 	// "Falkland Islands (Malvinas)",
		"FO" => "", 	// "Faroe Islands",
		"FJ" => "", 	// "Fiji",
		"FI" => array( 'lang' => 'UK', 'name' => 'Finland', 'needle' => ''),  	// "Finland",
		"FR" => array( 'lang' => 'FR', 'name' => 'France', 'needle' => 'fr'), 	// "France",
		"GF" => array( 'lang' => 'FR', 'name' => 'French Guiana', 'needle' => 'fr'), 	// "French Guiana",
		"PF" => array( 'lang' => 'FR', 'name' => 'French Polynesia', 'needle' => 'fr'), 	// "French Polynesia",
		"TF" => array( 'lang' => 'FR', 'name' => 'French Southern Territories', 'needle' => 'fr'),	// "French Southern Territories",
		"GA" => "", 	// "Gabon",
		"GM" => "", 	// "Gambia",
		"GE" => "", 	// "Georgia",
		"DE" => array( 'lang' => 'DE', 'name' => 'Germany', 'needle' => 'de'),	// "Germany",
		"GH" => "", 	// "Ghana",
		"GI" => "", 	// "Gibraltar",
		"GR" => array( 'lang' => 'UK', 'name' => 'Greece', 'needle' => ''), 	// "Greece",
		"GL" => "", 	// "Greenland",
		"GD" => "", 	// "Grenada",
		"GP" => "", 	// "Guadeloupe",
		"GU" => "", 	// "Guam",
		"GT" => array( 'lang' => 'ES', 'name' => 'Guatemala', 'needle' => 'es'), 	// "Guatemala",
		"GG" => "", 	// "Guernsey",
		"GN" => "", 	// "Guinea",
		"GW" => "", 	// "Guinea-Bissau",
		"GY" => "", 	// "Guyana",
		"HT" => "", 	// "Haiti",
		"HM" => "", 	// "Heard Island and McDonald Islands",
		"VA" => array( 'lang' => 'IT', 'name' => 'Holy See (Vatican City State)', 'needle' => 'it'), 	// "Holy See (Vatican City State)",
		"HN" => array( 'lang' => 'ES', 'name' => 'Honduras', 'needle' => 'es'),  	// "Honduras",
		"HK" => array( 'lang' => 'CN', 'name' => 'Hong Kong', 'needle' => 'zh'),	// "Hong Kong",
		"HU" => "", 	// "Hungary",
		"IS" => array( 'lang' => 'UK', 'name' => 'Iceland', 'needle' => ''),  	// "Iceland",
		"IN" => "", 	// "India",
		"ID" => "", 	// "Indonesia",
		"IR" => "", 	// "Iran Islamic Republic of",
		"IQ" => "", 	// "Iraq",
		"IE" => array( 'lang' => 'UK', 'name' => 'Ireland', 'needle' => ''), 	// "Ireland",
		"IM" => "", 	// "Isle of Man",
		"IL" => "", 	// "Israel",
		"IT" => array( 'lang' => 'IT', 'name' => 'Italy', 'needle' => 'it'), 	// "Italy",
		"JM" => "", 	// "Jamaica",
		"JP" => array( 'lang' => 'JP', 'name' => 'Japan', 'needle' => 'jp'),
		"JE" => "", 	// "Jersey",
		"JO" => "", 	// "Jordan",
		"KZ" => "", 	// "Kazakhstan",
		"KE" => "", 	// "Kenya",
		"KI" => "", 	// "Kiribati",
		"KP" => "", 	// "Korea Democratic People's Republic of",
		"KR" => "", 	// "Korea Republic of",
		"KW" => "", 	// "Kuwait",
		"KG" => "", 	// "Kyrgyzstan",
		"LA" => "", 	// "Lao People's Democratic Republic",
		"LV" => "", 	// "Latvia",
		"LB" => "", 	// "Lebanon",
		"LS" => "", 	// "Lesotho",
		"LR" => "", 	// "Liberia",
		"LY" => "", 	// "Libyan Arab Jamahiriya",
		"LI" => "", 	// "Liechtenstein",
		"LT" => "", 	// "Lithuania",
		"LU" => "", 	// "Luxembourg",
		"MO" => "", 	// "Macao",
		"MK" => "", 	// "Macedonia",
		"MG" => "", 	// "Madagascar",
		"MW" => "", 	// "Malawi",
		"MY" => "", 	// "Malaysia",
		"MV" => "", 	// "Maldives",
		"ML" => "", 	// "Mali",
		"MT" => "", 	// "Malta",
		"MH" => "", 	// "Marshall Islands",
		"MQ" => "", 	// "Martinique",
		"MR" => "", 	// "Mauritania",
		"MU" => "", 	// "Mauritius",
		"YT" => "", 	// "Mayotte",
		"MX" => array( 'lang' => 'ES', 'name' => 'Mexico', 'needle' => 'es'), 	// "Mexico",
		"FM" => "", 	// "Micronesia Federated States of",
		"MD" => "", 	// "Moldova  Republic of",
		"MC" => "", 	// "Monaco",
		"MN" => "", 	// "Mongolia",
		"ME" => "", 	// "Montenegro",
		"MS" => "", 	// "Montserrat",
		"MA" => "", 	// "Morocco",
		"MZ" => "", 	// "Mozambique",
		"MM" => "", 	// "Myanmar",
		"NA" => "", 	// "Namibia",
		"NR" => "", 	// "Nauru",
		"NP" => "", 	// "Nepal",
		"NL" => array( 'lang' => 'DE', 'name' => 'Netherlands', 'needle' => 'de'), 	// "Netherlands",
		"AN" => array( 'lang' => 'DE', 'name' => 'Netherlands Antilles', 'needle' => 'de'), 	// "Netherlands Antilles",
		"NC" => "", 	// "New Caledonia",
		"NZ" => array( 'lang' => 'US', 'name' => 'New Zealand', 'needle' => ''),  	// "New Zealand",
		"NI" => array( 'lang' => 'ES', 'name' => 'Nicaragua', 'needle' => 'es'), 	// "Nicaragua",
		"NE" => "", 	// "Niger",
		"NG" => "", 	// "Nigeria",
		"NU" => "", 	// "Niue",
		"NF" => "", 	// "Norfolk Island",
		"MP" => "", 	// "Northern Mariana Islands",
		"NO" => "", 	// "Norway",
		"OM" => "", 	// "Oman",
		"PK" => "", 	// "Pakistan",
		"PW" => "", 	// "Palau",
		"PS" => "", 	// "Palestinian Territory",
		"PA" => array( 'lang' => 'ES', 'name' => 'Panama', 'needle' => 'es'), 	// "Panama",
		"PG" => "", 	// "Papua New Guinea",
		"PY" => array( 'lang' => 'ES', 'name' => 'Paraguay', 'needle' => 'es'),  	// "Paraguay",
		"PE" => array( 'lang' => 'ES', 'name' => 'Peru', 'needle' => 'es'), 	// "Peru",
		"PH" => "", 	// "Philippines",
		"PN" => "", 	// "Pitcairn",
		"PL" => "", 	// "Poland",
		"PT" => "", 	// "Portugal",
		"PR" => array( 'lang' => 'ES', 'name' => 'Puerto Rico', 'needle' => 'es'), 	// "Puerto Rico",
		"QA" => "", 	// "Qatar",
		"RD" => "", 	// "Reserved",
		"RE" => "", 	// "Reunion",
		"RO" => "", 	// "Romania",
		"RU" => "", 	// "Russian Federation",
		"RW" => "", 	// "Rwanda",
		"SH" => "", 	// "Saint Helena",
		"KN" => "", 	// "Saint Kitts and Nevis",
		"LC" => "", 	// "Saint Lucia",
		"PM" => "", 	// "Saint Pierre and Miquelon",
		"VC" => "", 	// "Saint Vincent and the Grenadines",
		"WS" => "", 	// "Samoa",
		"SM" => "", 	// "San Marino",
		"ST" => "", 	// "Sao Tome and Principe",
		"SA" => "", 	// "Saudi Arabia",
		"SN" => "", 	// "Senegal",
		"RS" => "", 	// "Serbia",
		"SC" => "", 	// "Seychelles",
		"SL" => "", 	// "Sierra Leone",
		"SG" => "", 	// "Singapore",
		"SK" => "", 	// "Slovakia",
		"SI" => "", 	// "Slovenia",
		"SB" => "", 	// "Solomon Islands",
		"SO" => "", 	// "Somalia",
		"ZA" => "", 	// "South Africa",
		"GS" => "", 	// "South Georgia and the South Sandwich Islands",
		"ES" => array( 'lang' => 'ES', 'name' => 'Spain', 'needle' => 'es'),
		"LK" => "", 	// "Sri Lanka",
		"SD" => "", 	// "Sudan",
		"SR" => "", 	// "Suriname",
		"SJ" => "", 	// "Svalbard and Jan Mayen",
		"SZ" => "", 	// "Swaziland",
		"SE" => array( 'lang' => 'UK', 'name' => 'Sweden', 'needle' => ''),	// "Sweden",
		"CH" => array( 'lang' => 'UK', 'name' => 'Switzerland', 'needle' => ''),  	// "Switzerland",
		"SY" => "", 	// "Syrian Arab Republic",
		"TW" => array( 'lang' => 'CN', 'name' => 'Taiwan', 'needle' => 'zh'), 	// "Taiwan",
		"TJ" => "", 	// "Tajikistan",
		"TZ" => "", 	// "Tanzania United Republic of",
		"TH" => "", 	// "Thailand",
		"TL" => "", 	// "Timor-Leste",
		"TG" => "", 	// "Togo",
		"TK" => "", 	// "Tokelau",
		"TO" => "", 	// "Tonga",
		"TT" => "", 	// "Trinidad and Tobago",
		"TN" => "", 	// "Tunisia",
		"TR" => "", 	// "Turkey",
		"TM" => "", 	// "Turkmenistan",
		"TC" => "", 	// "Turks and Caicos Islands",
		"TV" => "", 	// "Tuvalu",
		"UG" => "", 	// "Uganda",
		"UA" => "", 	// "Ukraine",
		"AE" => "", 	// "United Arab Emirates",
		"GB" => array( 'lang' => 'UK', 'name' => 'United Kingdom', 'needle' => ''), 	// "United Kingdom",
		"US" => array( 'lang' => 'US', 'name' => 'United States', 'needle' => ''), 	// "United States",
		"UM" => array( 'lang' => 'US', 'name' => 'United States Minor Outlying Islands', 'needle' => ''), 	// "United States Minor Outlying Islands",
		"UY" => array( 'lang' => 'ES', 'name' => 'Uruguay', 'needle' => 'es'), 	// "Uruguay",
		"UZ" => "", 	// "Uzbekistan",
		"VU" => "", 	// "Vanuatu",
		"VE" => array( 'lang' => 'ES', 'name' => 'Venezuela', 'needle' => 'es'), 	// "Venezuela",
		"VN" => "", 	// "Vietnam",
		"VG" => array( 'lang' => 'UK', 'name' => 'Virgin Islands British', 'needle' => ''),	// "Virgin Islands British",
		"VI" => array( 'lang' => 'US', 'name' => 'Virgin Islands U.S.', 'needle' => ''), 	// "Virgin Islands U.S.",
		"WF" => "", 	// "Wallis and Futuna",
		"EH" => "", 	// "Western Sahara",
		"YE" => "", 	// "Yemen",
		"ZM" => "", 	// "Zambia",
		"ZW" => "", 	// "Zimbabwe"
	);	
	function get_countrycode_by_ip($ip) {
	
		// sinve v1.0.7
		$strXMLResult = $this->api_iptoc($ip);
		if (!$strXMLResult || $strXMLResult == -1) return;	
		$xmlDoc = new DOMDocument();
		@$xmlDoc->loadXML($strXMLResult);
		if (!$xmlDoc) return;
		$nodeCountryCode = $xmlDoc->getElementsByTagName('countryCode');
		if (!$nodeCountryCode) return;
		// print $xmlDoc->saveXML(); // output for debug
		$strCountryCode = $nodeCountryCode->item(0)->nodeValue;
		if (!$strCountryCode) return;
		if ($strCountryCode == '-') return;
		return $strCountryCode;
	} 
	function api_iptoc($ip, $format = 'xml') {
	
		// since v1.0.7
		// the API is at: http://ipinfodb.com/
		// $format : either xml, json, raw
		if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip)) return;		// $valid = filter_var($ip, FILTER_VALIDATE_IP); // php >= 5.2
		$key = 'db1ea057502a391cd14ee037d6883fa7d5a604e11061646592768f476f039e95';
		return $this->get_html("http://api.ipinfodb.com/v3/ip-city/?format=$format&key=$key&ip=$ip");
	}	
	function get_user_country_from_lang($strLocale='') {
	
		// since v1.0.7
		if (empty($strLocale)) $strLocale = get_locale();
		
		$arrCountryLang = array(	// the order 
			'JP'	=> 'ja',
			'ES'	=> 'es',
			'DE'	=> 'de',
			'FR'	=> 'fr',
			'IT'	=> 'it',
			'CN'	=> 'zh',
			// 'AT'	=> 'de',
			// 'CA'	=> 'en',
			// 'UK'	=> 'en',
			'US'	=> 'en',	
		);		
		foreach ($arrCountryLang as $key => $needle) 
			if ((stripos($strLocale, $needle)) !== false) return $key;
	}		
	
	/* for fixing $_POST $_GET invaid keys */
	function fix_request_array_key($strKey) {

		// added in v1.0.9
		// the issue is described here: http://stackoverflow.com/questions/68651/can-i-get-php-to-stop-replacing-characters-in-get-or-post-arrays
		$search = array(chr(32), chr(46), chr(91));
		for ($i=128; $i <= 159; $i++) {
			array_push($search, chr($i));
		}
		// print_r($search);
		return str_replace ( $search , '_', $strKey);
	}
}
?>