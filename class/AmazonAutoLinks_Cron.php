<?php
/**
	A cron task handler class.
	
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.4
*/
class AmazonAutoLinks_Cron  {
		
	static $sTransientKey = 'doing_amazon_auto_links_cron';
		
	/**
	 * Handles Fetch Tweets cron tasks.
	 * 
	 * Called from the constructor. 
	 * 
	 * @since			2.0.4
	 */
	protected function _handleCronTasks( $aActionHooks ) {

		// If not called from the background, return.
		if ( isset( $_GET['doing_wp_cron'] ) ) return;	// WP Cron
		if ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'admin-ajax.php' ) return;	// WP Heart-beat API
		if ( ! isset( $_COOKIE[ self::$sTransientKey ] ) ) return;
		
		// Do not process if a delay is not set.
		if ( ! isset( $_COOKIE[ 'delay' ] ) ) {	
			die( $this->_loadBackgroundPageWithDelay( 2 ) );	// give 2 seconds delay
		}
		
		// At this point, the page is loaded in the background with some delays.
		$_aTasks = get_transient( self::$sTransientKey );				
		$_nNow = microtime( true );
		$_nCalledTime = isset( $_aTasks['called'] ) ? $_aTasks['called'] : 0;
		$_nLockedTime = isset( $_aTasks['locked'] ) ? $_aTasks['locked'] : 0;
		unset( $_aTasks['called'], $_aTasks['locked'] );	// leave only task elements.
		
		// If it's still locked do nothing. Locked duration: 10 seconds.
		if ( $_nLockedTime + 10 > $_nNow ) {		
			return;
		}		
		
		// Retrieve the plugin cron scheduled tasks.
		if ( empty( $_aTasks ) ) {
			$_aTasks = $this->_getScheduledCronTasksByActionName( $aActionHooks );
		}
		// If the task is still empty,
		if ( empty( $_aTasks ) ) {					
			return;
		} 
		
		$aFlagKeys = array(
			'locked'	=>	microtime( true ),	// set/renew the locked time
			'called'	=>	$_nCalledTime,		// inherit the called time
		);
		set_transient( self::$sTransientKey, $aFlagKeys + $_aTasks, $this->getAllowedMaxExecutionTime() ); // lock the process.
		$this->_doTasks( $_aTasks );	

		// remove tasks but leave the 'called' element so that the background page load will not be triggered during the set interval.
		set_transient( self::$sTransientKey, $aFlagKeys, $this->getAllowedMaxExecutionTime() ); // lock the process.
		exit;
		
	}
	
		/**
		 * Performs a delayed background page load.
		 * 
		 * This gives the server to store transients to the database in case massive simultaneous accesses occur.
		 * 
		 * @since			2.0.4
		 */
		private function _loadBackgroundPageWithDelay( $iSecond=1 ) {
			
			sleep( $iSecond );
			wp_remote_get( // this forces the task to be performed right away in the background.		
				site_url( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '?debug=true' : null ), 
				array( 
					'timeout'	=>	0.01, 
					'sslverify'	=>	false, 
					'cookies'	=>	array( self::$sTransientKey => true, 'delay' => true ),
				) 
			);				
		}
		
	/**
	 * Performs the plugin-specific scheduled tasks in the background.
	 * 
	 * This should only be called when the self::$sTransientKey transient is present. 
	 * 
	 * @since 2.0.4
	 */
	protected function _doTasks( $aTasks ) {
		
		foreach( $aTasks as $iTimeStamp => $aCronHooks ) {
			
			if ( ! is_array( $aCronHooks ) ) continue;		// the 'locked' key flag element should be skipped
			foreach( $aCronHooks as $sActionName => $_aActions ) {
				
				foreach( $_aActions as $sHash => $aArgs ) {
																		
					$sSchedule = $aArgs['schedule'];
					if ( $sSchedule != false ) {
						$aNewArgs = array( $iTimeStamp, $sSchedule, $sActionName, $aArgs['args'] );
						call_user_func_array( 'wp_reschedule_event', $aNewArgs );
					}
	
					wp_unschedule_event( $iTimeStamp, $sActionName, $aArgs['args'] );
					do_action_ref_array( $sActionName, $aArgs['args'] );
				
				}
			}	
		}
		
	}
	
	/**
	 * Sets plugin specific cron tasks by extracting plugin's cron jobs from the WP cron job array.
	 *  
	 * @since 2.0.4
	 */
	protected function _getScheduledCronTasksByActionName( $aActionHooks ) {
		
		$_aTheTasks = array();		
		$_aTasks = _get_cron_array();
		if ( ! $_aTasks ) return $_aTheTasks;	// if the cron tasks array is empty, do nothing. 

		$_iGMTTime = microtime( true );	// the current time stamp in micro seconds.
		$_aScheduledTimeStamps = array_keys( $_aTasks );
		if ( isset( $_aScheduledTimeStamps[ 0 ] ) && $_aScheduledTimeStamps[ 0 ] > $_iGMTTime ) return $_aTheTasks; // the first element has the least number.
				
		foreach ( $_aTasks as $_iTimeStamp => $_aScheduledActionHooks ) {
			if ( $_iTimeStamp > $_iGMTTime ) break;	// see the definition of the wp_cron() function.
			foreach ( ( array ) $_aScheduledActionHooks as $_sScheduledActionHookName => $_aArgs ) {
				if ( in_array( $_sScheduledActionHookName, $aActionHooks ) ) {
					$_aTheTasks[ $_iTimeStamp ][ $_sScheduledActionHookName ] = $_aArgs;
		
				}
			}
		}
		return $_aTheTasks;
				
	}
	
	/**
	 * Retrieves the server set allowed maximum PHP script execution time.
	 * 
	 */
	static protected function getAllowedMaxExecutionTime( $iDefault=30, $iMax=120 ) {
		
		$iSetTime = function_exists( 'ini_get' ) && ini_get( 'max_execution_time' ) 
			? ( int ) ini_get( 'max_execution_time' ) 
			: $iDefault;
		
		return $iSetTime > $iMax
			? $iMax
			: $iSetTime;
		
	}
			
	/**
	 * Accesses the site in the background.
	 * 
	 * This is used to trigger cron events in the background and sets a static flag so that it ensures it is done only once per page load.
	 * 
	 * @since			2.0.4
	 */
	static public function triggerBackgroundProcess() {
		
		// if this is called during the WP cron job, do not trigger a background process as WP Cron will take care of the scheduled tasks.
		if ( isset( $_GET['doing_wp_cron'] ) ) return;	
		if ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'admin-ajax.php' ) return;	// WP Heart-beat API
	
		// Ensures the task is done only once in a page load.
		static $_bIsCalled;
		if ( $_bIsCalled ) return;
		$_bIsCalled = true;		

		$_sSelfClassName = get_class();
		if ( did_action( 'shutdown' ) ) {
			self::_replyToAccessSite();
			return;	// important as what the action has performed does not mean the action never will be fired again.
		}
		add_action( 'shutdown', "{$_sSelfClassName}::_replyToAccessSite", 999 );	// do not pass self::_replyToAccessSite.

	}	
		/**
		 * A callback for the accessSiteAtShutDown() method.
		 * 
		 * @since			2.0.4
		 */
		static public function _replyToAccessSite() {
		
			// Retrieve the plugin scheduled tasks array.
			$_aTasks = get_transient( self::$sTransientKey );
			$_aTasks = $_aTasks ? $_aTasks : array();
			$_nNow = microtime( true );
			
			// Check if the excessive background call protection interval 
			$_nCalled = isset( $_aTasks['called'] ) ? $_aTasks['called'] : 0;
			if ( $_nCalled + 10 > $_nNow ) {					
				return;	// if it's called within 10 seconds from the last time of calling this method, do nothing to avoid excessive calls.
			} 
			
			// Renew the called time.
			$_aFlagKeys = array(
				'called'	=>	$_nNow,
			);
			set_transient( self::$sTransientKey, $_aFlagKeys + $_aTasks, self::getAllowedMaxExecutionTime() );	// set a locked key so it prevents duplicated function calls due to too many calls caused by simultaneous accesses.
				
			wp_remote_get( // this forces the task to be performed right away in the background.		
				site_url( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '?debug=true' : null ), 
				array( 
					'timeout'	=>	0.01, 
					'sslverify'	=>	false, 
					'cookies'	=>	$_aFlagKeys + array( self::$sTransientKey => true ),
				) 
			);	
			
		}		
					
}