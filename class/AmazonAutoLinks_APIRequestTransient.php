<?php
/**
 * Performs requests to the Product Advertising API.
 * 
 * @package     	Amazon Auto Links
 * @copyright   	Copyright (c) 2013, Michael Uno
 * @license     	http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */


/**
 * Deals with Amazon Product Advertising API.
 * 
 * @action			aal_action_api_transient_renewal
 */ 
abstract class AmazonAutoLinks_APIRequestTransient {

	protected $arrParams = array();
	protected static $arrMandatoryParameters = array(
		'Service'			=> 'AWSECommerceService',
		'AssociateTag'		=> 'amazon-auto-links-20',		// the key must be provided; otherwise, API returns an error.
	);
	
	function __construct() {
		
		// Schedule the transient update task.
		add_action( 'shutdown', array( $this, 'scheduleUpdatingCaches' ) );
		
		$this->oEncrypt = new AmazonAutoLinks_Encrypt;
		
		
	}
	
	/**
	 * Performs the Twitter API request by the given URI.
	 * 
	 * This checks the existent caches and if it's not expired it uses the cache.
	 * 
	 * @since			2.0.0
	 * @access			protected
	 * @param			string			$strRequestURI				The GET request URI with the query.
	 * @param			integer			$intCacheDuration			The cache duration in seconds. 0 will use the stored cache. null will use a freshly fetched data.
	 * @return			array
	 */ 
	protected function requestWithCache( $strRequestURI, $arrHTTPArgs=array(), $arrParams=array(), $intCacheDuration=3600, $strLocale='US' ) {
	
		// Create an ID from the URI - it's better not use the ID from an Amazon API request URI because it is built upon a timestamp.
		$strTransientID = $this->generateIDFromRequestParameter( $arrParams );

		// Retrieve the cache, and if there is, use it.
		$arrTransient = $this->getTransient( $strTransientID );
		if ( 
			! is_null( $intCacheDuration )
			&& $arrTransient !== false 
			&& is_array( $arrTransient ) 
			&& isset( $arrTransient['mod'], $arrTransient['data'] )
		) {
			
			// Check the cache expiration.
			if ( ( $arrTransient['mod'] + ( ( int ) $intCacheDuration ) ) < time() ) 	// expired
				$GLOBALS['arrAmazonAutoLinks_APIRequestURIs'][ $strTransientID ] = array( 
					// these keys will be checked in the cache renewal events.
					'parameters' => $arrParams,
					'locale' => $strLocale,
				);
// AmazonAutoLinks_Debug::logArray( 'the cache IS used: ' . $strRequestURI, dirname( __FILE__ ) . '/cache.txt' );		
			return $this->oEncrypt->decode( $arrTransient['data'] );
			
		}
// AmazonAutoLinks_Debug::logArray( 'the cache is NOT used: ' . $strRequestURI, dirname( __FILE__ ) . '/cache.txt' );		
// AmazonAutoLinks_Debug::logArray( 
	// array( 
		// 'transient' => $strTransientID,
		// 'is_exist' => $arrTransient ? 'exists' : 'does not exist',
		// 'is_array' => is_array( $arrTransient ) ? 'array' : 'not array',
		// 'isset' => isset( $arrTransient['mod'], $arrTransient['data'] ) ? 'mod, data are set' : 'mod, data are not set',

	// ),
	// dirname( __FILE__ ) . '/cache.txt' 
 // );		
		return $this->setAPIRequestCache( $strRequestURI, $arrHTTPArgs, $strTransientID );
		
	}	
	
	/**
	 * Performs the API request and sets the cache.
	 * 
	 * @return			string			The response string of xml.
	 * @access			public
	 * @remark			The scope is public since the cache renewal event also uses it.
	 */
	public function setAPIRequestCache( $strRequestURI, $arrHTTPArgs, $strTransientID='' ) {
		
		// Perform the API request. - requestSigned() should be defined in the extended class.
		$strXMLResponse =  $this->requestSigned( $strRequestURI, $arrHTTPArgs );
		
		$arrResponse = AmazonAutoLinks_Utilities::convertXMLtoArray( $strXMLResponse );
			
// Debug
// AmazonAutoLinks_Debug::logArray( 'the data is fetched: ' . $strRequestURI, dirname( __FILE__ ) . '/cache.txt' );		
 
		// If empty, return an empty array.
		if ( empty( $arrResponse ) ) return array();
		
		// If the result is not an array, something went wrong.
		if ( ! is_array( $arrResponse ) ) return ( array ) $arrResponse;
		
		// If an error occurs, do not set the cache.
		if ( isset( $arrResponse['Error'] ) ) return $arrResponse;
			
		// Save the cache
		$strTransientID = empty( $strTransientID ) 
			? AmazonAutoLinks_Commons::TransientPrefix . "_" . md5( trim( $strRequestURI ) )
			: $strTransientID;
		$this->setTransient( $strTransientID, $strXMLResponse );

		return  $strXMLResponse;
		
	}
	
	/**
	 * A wrapper method for the set_transient() function.
	 * 
	 */
	public function setTransient( $strTransientKey, $vData, $intTime=null ) {
// AmazonAutoLinks_Debug::logArray( 'the transient is set: ' . $strTransientKey, dirname( __FILE__ ) . '/cache.txt' );		
		set_transient(
			$strTransientKey, 
			array( 
				'mod' => $intTime ? $intTime : time(), 
				'data' => $this->oEncrypt->encode( $vData ) 
			), 
			9999999999 // this barely expires by itself. $intCacheDuration 
		);
		
	}
	
	/**
	 * A wrapper method for the get_transient() function.
	 * 
	 * This method does retrieves the transient with the given transient key. In addition, it checks if it is an array; otherwise, it makes it an array.
	 * 
	 * @access			public
	 * @since			2.0.0
	 * @remark			The scope is public as the event method uses it.
	 */ 
	public function getTransient( $strTransientKey ) {
		
		$vData = get_transient( $strTransientKey );
		
		// if it's false, no transient is stored. Otherwise, some values are in there.
		if ( $vData === false ) return false;
					
		// If it's array, okay.
		if ( is_array( $vData ) ) return $vData;

		// Maybe it's encoded
		if ( is_string( $vData ) && is_serialized( $vData ) ) 
			return unserialize( $vData );
				
		// Maybe it's an object. In that case, convert it to an associative array.
		if ( is_object( $vData ) )
			return get_object_vars( $vData );
			
		// It's an unknown type. So cast array and return it.
		return ( array ) $vData;
			
	}
	
	/**
	 * Generates an ID from the passed parameter.
	 * 
	 * Signed request URI uses a timestamp so it is not suitable for transient ID.
	 * 
	 */
	public function generateIDFromRequestParameter( $arrParams ) {
		
		$arrParams = array_filter( $arrParams + $this->arrParams );		// Omits empty values.
		$arrParams = $arrParams + self::$arrMandatoryParameters;	// Append mandataory elements.
		ksort( $arrParams );		
		$strQuery = implode( '&', $arrParams );
		return AmazonAutoLinks_Commons::TransientPrefix . "_"  . md5( $strQuery );		
		
	}
	
	/*
	 * Callbacks
	 * */
	public function scheduleUpdatingCaches() {	// for the shutdown hook
		
		if ( empty( $GLOBALS['arrAmazonAutoLinks_APIRequestURIs'] ) ) return;
				
		foreach( $GLOBALS['arrAmazonAutoLinks_APIRequestURIs'] as $arrExpiredCacheRequest ) {
			
			/* the structure of $arrExpiredCacheRequest = array(
				'parameters' => the request parameter values
				'locale' => the locale 
			*/
			
			// Schedules the action to run in the background with WP Cron.
			// If already scheduled, skip.
			if ( wp_next_scheduled( 'aal_action_api_transient_renewal', array( $arrExpiredCacheRequest ) ) ) continue; 
			
			wp_schedule_single_event( 
				time(), 
				'aal_action_api_transient_renewal', 	// the AmazonAutoLinks_Event class will check this action hook and executes it with WP Cron.
				array( $arrExpiredCacheRequest )	// must be enclosed in an array.
			);	
			
		}
				
	}	
}