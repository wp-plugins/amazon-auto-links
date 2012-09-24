<?php
class AmazonAutoLinks_Events_ {
	/*
		Since: v1.0.5
		Description: this class loads saved events to hook actions with add_action() for WP Cron tasks.
	*/
	public $arrFuncRef = array();	// store md5 hash strings associating with the unit label.
	function __construct($bIsManualLoad=False) {		
	
		// if True is passed to the constructer, it means the class is intanciated manually. 
		// AmazonAutoLinks_Log( 'instanciated ' . ($bIsManualLoad ? 'manually.' : 'automatically.') , __METHOD__);
		if ($bIsManualLoad === True)	return;	// in that case, do not load events.
	
		// load category cache events
		$this->load_category_cache_events();
		
		// load feed cache events
		$this->load_feed_cache_events();
	}
	
	// Feed Caches
	function __call($strMethodName, $arguments) {
	
		// This is called from the feed cache events 
		// $strMethodName is a md5 hashed string of unit label with a prefix of 'aal_func_'. $arguments are not passed.
		
		$arrOptions = get_option('amazonautolinks');	
		$strUnitLabel = $this->arrFuncRef[$strMethodName];
		$strEventName = 'aal_feed_' . md5($strUnitLabel);
		AmazonAutoLinks_Log( 'unset method name is called: ' . $strMethodName . ' This reads to: ' . $strUnitLabel, __METHOD__);
		
		// renew the cache
		$oAAL = new AmazonAutoLinks_Core($strUnitLabel);	// now the class accepts a unit label to be passed in the parameter
		$oAAL->cache_rebuild();
		
		// schedule the next event so that it will be reccursive
		$numLifetime = $arrOptions['units'][$strUnitLabel]['cacheexpiration'];
		wp_schedule_single_event(time() + $numLifetime, $strEventName);
		AmazonAutoLinks_Log( 'Unit Label, "' . $strUnitLabel . '", is renewed and rescheduled the cache renew event at ' . $numLifetime . ' seconds from now: ' . date('Y m d h:i:s A', time() + $numLifetime), __METHOD__);
	}
	
	function load_feed_cache_events() {
		$arrOptions = get_option('amazonautolinks');	
		$i = 0;
		foreach($arrOptions['units'] as $strUnitLabel => $arrUnitOption) {
			$strActionHashName = md5($strUnitLabel);
			$strFunctionName = 'aal_func_' . $strActionHashName;
			$strEventName = 'aal_feed_' . $strActionHashName;
			$this->arrFuncRef[$strFunctionName] = $strUnitLabel;
			add_action($strEventName, array(&$this, $strFunctionName));	// this sets the method name as the hash name of unit label and if the method is triggered, __call() is called.
			$i++;
		}	
		AmazonAutoLinks_Log( $i . ' action(s) of feed cache events is(are) hooked. This simply means there are ' . $i . ' unit(s).', __METHOD__);
	}
	function schedule_feed_cache_rebuild($strUnitLabel, $numInterval) {
		
		// this method is called by the AmazonAutoLinks_Core class
		$strActionHashName = md5($strUnitLabel);
		$strFunctionName = 'aal_func_' . $strActionHashName;
		$strEventName = 'aal_feed_' . $strActionHashName;
		add_action($strEventName, array(&$this, $strFunctionName));	// this sets the method name as the hash name of unit label
		wp_schedule_single_event(time() + $numInterval, $strEventName);	
		AmazonAutoLinks_Log( $strUnitLabel . ' is scheduled to rebuild its feed cache at ' . $numInterval . ' seconds from now.', __METHOD__);
	}
	function reschedule_feed_cache_rebuild($numTimeStamp, $strUnitLabel, $numInterval) {
		
		// this method is called by the AmazonAutoLinks_Core class when it is detected that the user changed the cache expiration time setting.
		$strActionHashName = md5($strUnitLabel);
		wp_unschedule_event($numTimeStamp, 'aal_feed_' . $strActionHashName);	
		AmazonAutoLinks_Log( $strUnitLabel . ' is unscheduled.', __METHOD__);
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
		AmazonAutoLinks_Log( $i . ' action(s) is(are) hooked.', __METHOD__);
	}		
	function cache_category() {
		
		// This function is triggered by the run-off shcedule event.
		// It builds caches for 10 urls per call.
		// AmazonAutoLinks_Log(' called.', __METHOD__);
		
		// Instantiate class objects
		$oAALCatCache = new AmazonAutoLinks_CategoryCache('amazonautolinks');
		$arrCatCacheEvents = get_option('amazonautolinks_catcache_events');
		
		// extract the first entry; the oldest jobs from the begginning
		$arrEvents = array_splice($arrCatCacheEvents, 0, 10);   // take out 10 elements from the beggining of the array
		if (count($arrEvents) == 0)	{
			// echo '<!-- Amazon Auto Links: no events are scheduled. Returning. -->';
			AmazonAutoLinks_Log('No events are scheduled. Returning.', __METHOD__);
			return; // if nothing extracted, return
		}
			
		// build cache for this url; this array, $arrEvent only holds one element with a key of the action name and the value of url
		$i = 0;
		foreach($arrEvents as $strURL) 
			if ($oAALCatCache->cache_category($strURL)) $i++;
		
		echo '<!-- ' . __METHOD__ . ': ' . $i . ' number of page(s) are cached: -->';
		AmazonAutoLinks_Log($i . ' number of page(s) are cached', __METHOD__);

		// Do not update the option! Since the process can be stopped by any reason and do not complete caching.
		// So let cache_html() update the option when the passed url is cached.
		// update_option('amazonautolinks_catcache_events', $arrCatCacheEvents);
		
		// if there are remaining tasks, continue executing in the background
		if ( count($arrCatCacheEvents) > 0 ) 
			$oAALCatCache->run_in_background('Background Process: There are '. count($arrCatCacheEvents) .' remaining events. Keep fetching!');
		else 
			AmazonAutoLinks_Log('All done!', __METHOD__);
		}	
	}