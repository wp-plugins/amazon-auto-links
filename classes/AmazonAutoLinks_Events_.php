<?php
/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.5
 * @description	Loads saved events to hook actions with add_action() for WP Cron tasks.
*/
class AmazonAutoLinks_Events_ {
	/*
		Used hooks: 
			aal_setuptransients - setups transients used by the activation hook.
	*/
	public $arrFuncRef = array();	// store md5 hash strings associating with the unit label.
	function __construct( &$oOption ) {		

		// as of v1.1.3 $bIsManualLoad is removed
		$this->oOption = $oOption;
		
		// For the plugin activation hook
		add_action( 'aal_setuptransients', array( $this, 'SetupTransients' ) );	 // this must be loaded to execute the callback function		
		
	}
	
	function LoadEvents() {
		
		// sinvce v1.1.3, moved from the constructor since the behaviour changed to instantiate this class in the ininitial loadfile
		
		// check if it is called directly
		if (isset($_GET['amazonautolinks_cache'])) {
			if ($_GET['amazonautolinks_cache'] == 'category')
				$this->cache_category();
			else if ($_GET['amazonautolinks_cache'] == 'usercountrycode')
				$this->cache_usercountrycode($_GET['userid']);
			else if ($_GET['amazonautolinks_cache'] == 'userads')
				$this->setup_userad_unitoptions($_GET['country']);
			return;
		} 
		
		// load category cache events
		$this->load_category_cache_events();
		
		// load feed cache events
		$this->load_feed_cache_events();		
		
	}
	
	// Feed Caches
	function __call( $strMethodName, $arguments ) {
	
		// This is called from the feed cache events 
		// $strMethodName is a md5 hashed string of unit label with the prefix of 'aal_func_'. $arguments are not passed.
		
		
		$arrOptions = $this->oOption->arrOptions;	// $arrOptions = get_option('amazon-auto-links');
		
		$strUnitLabel = $this->arrFuncRef[$strMethodName];
		$strEventName = 'aal_feed_' . md5( $strUnitLabel );
		$this->oOption->oLog->Append( 'An unset method name is called: ' . $strMethodName . ', which reads to: ' . $strUnitLabel );
		
		// renew the cache
		$oAAL = new AmazonAutoLinks_Core( $strUnitLabel, $this->oOption );	// now the class accepts a unit label to be passed in the parameter
		$oAAL->cache_rebuild();
		
		$oAAL->GetBlackASINs( false );	// false to not to use the transients, which renews the cache
		// schedule the next event so that it will be reccursive
		$strUnitID = $oAAL->get_unitid_from_unitlabel($strUnitLabel);
		$numLifetime = $arrOptions['units'][$strUnitID]['cacheexpiration'];
		wp_schedule_single_event(time() + $numLifetime, $strEventName);
		$this->oOption->oLog->Append( 'Unit Label, "' . $strUnitLabel . '", is renewed and rescheduled the cache renew event at ' . $numLifetime . ' seconds from now: ' . date('Y m d h:i:s A', time() + $numLifetime) );
	}
	
	function load_feed_cache_events() {
		
		$arrOptions = $this->oOption->arrOptions;		// $arrOptions = get_option('amazon-auto-links');	
		
		$i = 0;
		foreach( $arrOptions['units'] as $strUnitID => $arrUnitOption ) {
			$strUnitLabel = $arrUnitOption['unitlabel'];
			$strActionHashName = md5($strUnitLabel);
			$strFunctionName = 'aal_func_' . $strActionHashName;
			$strEventName = 'aal_feed_' . $strActionHashName;
			$this->arrFuncRef[$strFunctionName] = $strUnitLabel;
			add_action( $strEventName, array( &$this, $strFunctionName ) );	// this sets the method name as the hash name of unit label and if the method is triggered, __call() is called.
			$i++;
		}	
		// if ( $i > 0 )
			// $this->oOption->oLog->Append( $i . ' action(s) of feed cache events is(are) hooked. This simply means there are ' . $i . ' unit(s).', __METHOD__ );
	}
	function schedule_feed_cache_rebuild( $strUnitLabel, $numInterval ) {
		
		// this method is called by the AmazonAutoLinks_Core class
		$strActionHashName = md5( $strUnitLabel );
		$strFunctionName = 'aal_func_' . $strActionHashName;
		$strEventName = 'aal_feed_' . $strActionHashName;
		add_action( $strEventName, array( &$this, $strFunctionName ) );	// this sets the method name as the hash name of unit label
		wp_schedule_single_event( time() + $numInterval, $strEventName );	
		$this->oOption->oLog->Append( $strUnitLabel . ' is scheduled to rebuild its feed cache at ' . $numInterval . ' seconds from now.' );
	}
	function reschedule_feed_cache_rebuild($numTimeStamp, $strUnitLabel, $numInterval) {
		
		// this method is called by the AmazonAutoLinks_Core class when it is detected that the user changed the cache expiration time setting.
		$strActionHashName = md5($strUnitLabel);
		wp_unschedule_event($numTimeStamp, 'aal_feed_' . $strActionHashName);	
		$this->oOption->oLog->Append( $strUnitLabel . ' is unscheduled.', __METHOD__);
		$this->schedule_feed_cache_rebuild($strUnitLabel, $numInterval);
	}
	
	// Category Caches
	function load_category_cache_events() {

		// The event option uses a separate option key since cron jobs runs and updates options asyncronomously, 
		// It should not affect or get affected by other processes.
		$arrCatCacheEvents = get_option('amazonautolinks_catcache_events');
		if (!is_array($arrCatCacheEvents)) {
			update_option('amazonautolinks_catcache_events', array());
			return;
		}
		
		// register actions 
		$i = 0;
		foreach($arrCatCacheEvents as $strActionName => $strURL) {
			$i++;
			add_action($strActionName, array(&$this, 'cache_category'));		// the first parameter is the action name to be registered
		}	
		
		// this is mostly for debugging.
		if ( $i > 0 )	
			$this->oOption->oLog->Append( $i . ' action(s) is(are) hooked.', __METHOD__ );
	}		
	function cache_category() {
		
		// This function is triggered by the run-off shcedule event. It builds caches for 5 urls per call.
		
		// Instantiate class objects
		$oAALCatCache = new AmazonAutoLinks_CategoryCache('amazon-auto-links', $this->oOption );
		$arrCatCacheEvents = get_option('amazonautolinks_catcache_events');
		shuffle($arrCatCacheEvents);	// make it randome since this method is called simultaneously so multiple instances should not process the same urls.
		
		// extract the first entry; the oldest jobs from the begginning
		$arrEvents = array_splice($arrCatCacheEvents, 0, 5);   // take out 5 elements from the beggining of the array
		if (count($arrEvents) == 0)	{
			// echo '<!-- Amazon Auto Links: no events are scheduled. Returning. -->';
			$this->oOption->oLog->Append('No events are scheduled. Returning.' );
			return; // if nothing extracted, return
		}
			
		// if the events are all executed, this line won't be reached
		// first trigger a background process before building the caches since an interruption can occur during the process of caching
		// $oAALCatCache->run_in_background('Background Process: Keep fetching!');
		
		// build cache for this url; this array, $arrEvent only holds one element with a key of the action name and the value of url
		$i = 0;
		foreach($arrEvents as $strURL) 
			if ($oAALCatCache->cache_category($strURL)) $i++;
		
		echo '<!-- ' . __METHOD__ . ': ' . $i . ' number of page(s) are cached: -->';
		$this->oOption->oLog->Append($i . ' number of page(s) are cached' );

		// Do not update the option! Since the process can be stopped by any reason and do not complete caching.
		// So let cache_html() update the option when the passed url is cached.
		// update_option('amazonautolinks_catcache_events', $arrCatCacheEvents);
		
		// if there are remaining tasks, continue executing in the background
		if ( count($arrCatCacheEvents) > 0 ) {
			$oAALCatCache->run_in_background('Background Process: There are '. count($arrCatCacheEvents) .' remaining events. Keep fetching!');
		}
		else 
			$this->oOption->oLog->Append( 'All done!' );
	}

	/* For User Country Code*/
	function cache_usercountrycode($strUserID=1) {
	
		// since v1.0.7
		$oAALUserAds = new AmazonAutoLinks_UserAds('amazon-auto-links');
		$strCountryCode = $oAALUserAds->get_user_countrycode();
		
		// keep it for 60 days, it won't hardly expire by itself.
		// the plugin checks its modified date set in the below code and if it passes the set amount of time,
		// it will call this method in the background, so the data is silently renewed 
		set_transient('aal_usercountry_' . $strUserID, $strCountryCode, 60*60*24*60 );	
		update_option('_transient_aal_mod_usercountry_' . $strUserID, time());			// save the modified date togherther
	}
	function setup_userad_unitoptions($strCountryCode='US') {
	
		// since v1.0.7
		
		// lock this call for one minute
		if (get_transient('aal_userad_setunit') !== false){
			$this->oOption->oLog->Append( 'The user ad setup unit is already in process. Returning.' );		
			return;
		}
		set_transient('aal_userad_setunit', time(), 60 );	
		$this->oOption->oLog->Append('setting up the unit option for a user ad: ' . $strCountryCode );		
		
		// creates a unit option with the key name of the given county code and saves it in the option with the "amazonautolinks_userads" key.
		$oAALUserAds = new AmazonAutoLinks_UserAds('amazon-auto-links');
		$oAALUserAds->setup_unitoption($strCountryCode);
	}
		 
	/*
	 * For the plugin activation hook
	* */ 
	function SetupTransients() {
		// Should be called in the background.
		// file_put_contents( AMAZONAUTOLINKSPLUGINDIR . '/' . __FUNCTION__ . '.txt' , 
			// __FILE__ . PHP_EOL . 
			// __METHOD__ . PHP_EOL,
			// FILE_APPEND );
				
		// AmazonAutoLinks_RegisterClasses();
		$o = new AmazonAutoLinks_UserAds( 'amazonautolinks', new AmazonAutoLinks_Options( 'amazonautolinks' ) );
		$o->SetupTransients();
	}
	
}