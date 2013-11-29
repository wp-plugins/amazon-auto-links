<?php
/**
	Event handler.
	
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0
 * @action		aal_action_setup_transients
 * @action		aal_action_simplepie_renew_cache
 * @action		aal_action_api_transient_renewal	
 */
abstract class AmazonAutoLinks_Event_ {

	public function __construct() {
		
		// if WP Cron is the one which loaded the page,
		if ( isset( $_GET['doing_wp_cron'] ) )	{
					
			// For SimplePie cache renewal events 
			add_action( 'aal_action_simplepie_renew_cache', array( $this, 'renewSimplePieCaches' ) );
		
			// For API transient (cache) renewal events
			add_action( 'aal_action_api_transient_renewal', array( $this, 'renewAPITransients' ) );
		
		}
				
		// User ads redirects
		if ( isset( $_GET['amazon_auto_links_link'] ) && $_GET['amazon_auto_links_link'] ) {			
			$oRedirect = new AmazonAutoLinks_Redirects;
			$oRedirect->go( $_GET['amazon_auto_links_link'] );	// will exit there.
		}
			
		// Draw cached image.
		if ( isset( $_GET['amazon_auto_links_image'] ) && $_GET['amazon_auto_links_image'] ) {
			
			$oImageLoader = new AmazonAutoLinks_ImageHandler( AmazonAutoLinks_Commons::TransientPrefix );
			$oImageLoader->draw( $_GET['amazon_auto_links_image'] );
			exit;
			
		}			
		
		// For the activation hook
		add_action( 'aal_action_setup_transients', array( $this, 'setUpTransients' ) );
		
		// Load styles of templates
		if ( isset( $_GET['amazon_auto_links_style'] ) )
			$GLOBALS['oAmazonAutoLinks_Templates']->loadStyle( $_GET['amazon_auto_links_style'] );
			
		// URL Cloak
		$strQueryKey = $GLOBALS['oAmazonAutoLinks_Option']->arrOptions['aal_settings']['query']['cloak'];
		if ( isset( $_GET[ $strQueryKey ] ) ) 			
			$this->goToStore( $_GET[ $strQueryKey ], $_GET );	

	}

	public function renewAPITransients( $arrRequestInfo ) {
		
// AmazonAutoLinks_Debug::logArray( $arrRequestInfo, dirname( __FILE__ ) . '/cache_renewals.txt' );			
		
		$strLocale = $arrRequestInfo['locale'];
		$arrParams = $arrRequestInfo['parameters'];
		$oAmazonAPI = new AmazonAutoLinks_ProductAdvertisingAPI( 
			$strLocale, 
			$GLOBALS['oAmazonAutoLinks_Option']->getAccessPublicKey(), 
			$GLOBALS['oAmazonAutoLinks_Option']->getAccessPrivateKey()
		);
		$oAmazonAPI->request( $arrParams, $strLocale, null );	// passing null will fetch the data right away and sets the cache.
		
		
	}
	
	public function setUpTransients() {
		
		$oUA = new AmazonAutoLinks_UserAds();
		$oUA->setupTransients();		
		
	}
	
	public function renewSimplePieCaches( $vURLs ) {
		
		// Setup Caches
		$oFeed = new AmazonAutoLinks_SimplePie();

		// Set urls
		$oFeed->set_feed_url( $vURLs );	

		// this should be set after defining $vURLs
		$oFeed->set_cache_duration( 0 );	// 0 seconds, means renew the cache right away.
	
		// Set the background flag to True so that it won't trigger the event action recursively.
		$oFeed->setBackground( true );
		$oFeed->init();	

// AmazonAutoLinks_Debug::logArray( $vURLs, dirname( __FILE__ ) . '/cache_renewals.txt' );	
		
	}
	
	/**
	 * 
	 * For URL cloaking redirects.
	 */
	protected function goToStore( $strASIN, $arrArgs ) {
		
		$arrArgs = $arrArgs + array(
			'locale' => null,
			'tag' => null,
			'ref' => null,
		);
		
		// http://www.amazon.[domain-suffix]/dp/ASIN/[asin]/ref=[...]?tag=[associate-id]
		$strURL = isset( AmazonAutoLinks_Properties::$arrCategoryRootURLs[ strtoupper( $arrArgs['locale'] ) ] )
			? AmazonAutoLinks_Properties::$arrCategoryRootURLs[ strtoupper( $arrArgs['locale'] ) ]
			: AmazonAutoLinks_Properties::$arrCategoryRootURLs['US'];
		
		$arrURLelem = parse_url( $strURL );
		$strStoreURL = $arrURLelem['scheme'] . '://' . $arrURLelem['host'] 
			. '/dp/ASIN/' . $strASIN . '/' 
			. ( empty( $arrArgs['ref'] ) ? '' : 'ref=nosim' )
			. "?tag={$arrArgs['tag']}";
		
		die( wp_redirect( $strStoreURL ) );
				
	}
	
	
}