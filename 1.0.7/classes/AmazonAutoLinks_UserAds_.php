<?php

class AmazonAutoLinks_UserAds_
{
	/*
		Since: v1.0.7
		Description: Creates links for the user.
		Used Option Key: amazonautolinks_userads
	*/
	
	function __construct($pluginkey) {
		$this->pluginkey = $pluginkey;
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($pluginkey);
		$this->oAALOptions = new AmazonAutoLinks_Options($pluginkey);
		
		// import global variables
		$this->current_user = & $GLOBALS["current_user"];
	}
	function check_user_countrycode() {
		
		// get the current user id
		get_currentuserinfo();
		$strUserID = $this->current_user->ID;		
		
		// check the modified date of the country transient for this user
		if ( get_option('_transient_aal_mod_usercountry_' . $strUserID) + 60*60*24*2 < time() ) {
			
			// this means two days passed since the last saved date 
			AmazonAutoLinks_Log('The transient "_transient_aal_mod_usercountry_' . $strUserID . '" is expired or not set yet. Renew it: ' . $modtime , __METHOD__);		
			
			// schedule an event to retrieve/renew the user country code in the background.
			$cron_url = site_url('?amazonautolinks_cache=usercountrycode&userid=' . $strUserID);	// $cron_url = site_url('wp-cron.php?doing_wp_cron=0');
			wp_remote_post( $cron_url, array( 'timeout' => 0.01, 'blocking' => false, 'sslverify' => apply_filters( 'https_local_ssl_verify', true ) ) );
			AmazonAutoLinks_Log('called the background process: ' . $cron_url, __METHOD__);	
		}

		// get the transient.
		$strUserCountry = get_transient('aal_usercountry_' . $strUserID);		
		if( false !== $strUserCountry ) return $strUserCountry;
	
		// nothing worked, return "US" as the default
		return 'US';
	}
	function get_user_countrycode() {
		
		// called in the background process, AmazonAutoLinks_Event
		// attempt from user ip
		$strCountryCode = $this->oAALfuncs->get_countrycode_by_ip($_SERVER['REMOTE_ADDR']);
		if ($strCountryCode) return $strCountryCode;
		
		// attempt from WP lang
		$strCountryCode = $this->oAALfuncs->get_user_country_from_lang(get_locale());
		if ($strCountryCode) return $strCountryCode;
		
		// attempt from browser Lang
		// not implemented yet.
		
		// if nothing found, return US
		if (!$strCountryCode) return 'US';	//'not found: ' . $strUserIP;
	}
	function setup_unitoption($strCountryCode='US') {

		AmazonAutoLinks_Log('called: ' . $strCountryCode, __METHOD__);		
		
		// this method is only calld in the background from AmazonAutoLinks_Event
		// user ad unit option
		$arrUserAdsOptions = get_option('amazonautolinks_userads');
		if (!is_array($arrUserAdsOptions)) $arrUserAdsOptions = array();
		
		// get the default value
		$arrUnitOptions = $this->oAALOptions->unitdefaultoptions;
		$arrUnitOptions['unitdefaultoptions'] = $strCountryCode;
		$arrUnitOptions['numitems'] = 1;
		$arrUnitOptions['imagesize'] = 30;
		$arrUnitOptions['associateid'] = $this->oAALOptions->get_token($strCountryCode);
		$arrUnitOptions['mblang'] = $this->oAALOptions->arrCountryLang[$strCountryCode];
		$arrUnitOptions['countryurl'] = $this->oAALOptions->arrCountryURLs[$strCountryCode];
		$arrUnitOptions['modifieddate'] = time();
		$arrUnitOptions['id'] = uniqid();
		$arrUnitOptions['unitlabel'] = $strCountryCode;
		
		// check if the feed urls are ready
		if (!is_array($arrUserAdsOptions[$strCountryCode]['feedurls'])) {
		
			// the urls are not ready. So shcedule prefetch and return 
			// generate feed urls for this country from the root category
			// this is heavy and takes time. so the script may be timed out
			$oAALCategoryCache = new AmazonAutoLinks_CategoryCache($this->pluginkey);
			AmazonAutoLinks_Log('Retrieving category urls: ' . $arrUnitOptions['countryurl'], __METHOD__);
			$arrCatUrls = $oAALCategoryCache->get_subcategories_from_url($arrUnitOptions['countryurl']);
			shuffle($arrCatUrls);		// get_rsslink_from_urls() gets stuck at completing a certain number of urls and does not complete it by one call but it stores caches by url so shuffle it and strart from un-saved elements
			$arrCatUrls = array_splice($arrCatUrls, 0, 10);		// limit the number to 10 urls; otherwise, it might exeeds maximum DB connection
			AmazonAutoLinks_Log('retrieving rss urls.' . implode(', ', $arrCatUrls), __METHOD__);		
			$arrUnitOptions['feedurls'] = $oAALCategoryCache->get_rsslink_from_urls($arrCatUrls);	// the option key, "feedurls" is only used for this class
			AmazonAutoLinks_Log('completed retrieving the rss urls.', __METHOD__);		
		}

		// save the update
		$arrUserAdsOptions[$strCountryCode] = $arrUnitOptions;
		update_option('amazonautolinks_userads', $arrUserAdsOptions);
		AmazonAutoLinks_Log('The user ad unit option is successfully saved: ' . $strCountryCode, __METHOD__);		
	}
	function get_unitoption($strCountryCode) {
		$arrUserAdOptions = get_option('amazonautolinks_userads');
		
		// if the option unit is not created, or if the option is 2 week-old, set up the unit in the background.
		if (!is_array($arrUserAdOptions[$strCountryCode]) || $arrUserAdOptions[$strCountryCode]['modifieddate'] + 60*60*24*14 < time()) {
			
			$cron_url = site_url('?amazonautolinks_cache=userads&country=' . $strCountryCode);	// $cron_url = site_url('wp-cron.php?doing_wp_cron=0');
			wp_remote_post( $cron_url, array( 'timeout' => 0.01, 'blocking' => false, 'sslverify' => apply_filters( 'https_local_ssl_verify', true ) ) );
			AmazonAutoLinks_Log('called the background process: ' . $cron_url, __METHOD__);				
			return;
		}
		return $arrUserAdOptions[$strCountryCode];
	}
	function show_top_banner() {
	
		$strCountryCode = $this->check_user_countrycode();			// if the country code cache is not ready, it will return 'US'
		$arrUnitOptions = $this->get_unitoption($strCountryCode);	// if the cache of the user ad unit option  is not ready, it will return false
		if (!$arrUnitOptions)  {
			AmazonAutoLinks_Log('The user ad is not ready: ' . $strCountryCode, __METHOD__);				
			return;	// now it should be preparing the unit option
		}
		if (!is_array($arrUnitOptions['feedurls'])) {
			AmazonAutoLinks_Log('The option is not formated correctly: ' . $strCountryCode, __METHOD__);	
// print_r($arrUnitOptions);	
			// clean the broken unit
			$arrUserAdOptions = get_option('amazonautolinks_userads');
			unset($arrUserAdOptions[$strCountryCode]);
			update_option('amazonautolinks_userads', $arrUserAdsOptions);
			
			// reschedule the unit setup
			$cron_url = site_url('?amazonautolinks_cache=userads&country=' . $strCountryCode);	// $cron_url = site_url('wp-cron.php?doing_wp_cron=0');
			wp_remote_post( $cron_url, array( 'timeout' => 0.01, 'blocking' => false, 'sslverify' => apply_filters( 'https_local_ssl_verify', true ) ) );
			AmazonAutoLinks_Log('rescheduled the user ad unit option setup in the background process: ' . $cron_url, __METHOD__);			
			
			return;	// now it should be preparing the unit option
		}
		// shuffle ad-types annd set one of them to be true.
		$arrFeedTypes = array(	'bestsellers' => False, 
								'hotnewreleases' => False,
								'moverandshakers' => False,
								// 'toprated' => False,
								'mostwishedfor' => False,
								'giftideas' => False
							);	
		$strRandKey_FeedTypes = array_rand($arrFeedTypes, 1);
		$arrFeedTypes[$strRandKey_FeedTypes] = True;
		$arrUnitOptions['feedtypes'] = $arrFeedTypes;
		$arrUnitOptions['adtypes'] = array(
			'bestsellers' 		=> array('check' => $arrUnitOptions['feedtypes']['bestsellers'], 'slug' => 'bestsellers'),
			'hotnewreleases'	=> array('check' => $arrUnitOptions['feedtypes']['hotnewreleases'], 'slug' => 'new-releases'),
			'moverandshakers'	=> array('check' => $arrUnitOptions['feedtypes']['moverandshakers'], 'slug' => 'movers-and-shakers'),
			'toprated'			=> array('check' => false, 'slug' => 'top-rated'),
			'mostwishedfor'		=> array('check' => $arrUnitOptions['feedtypes']['mostwishedfor'], 'slug' => 'most-wished-for'),
			'giftideas'			=> array('check' => $arrUnitOptions['feedtypes']['giftideas'], 'slug' => 'most-gifted')
		);
		
		// shuffle categories and pick one of them to use
		$strRandKey = array_rand($arrUnitOptions['feedurls'], 1);	
		$arrCategories = array(	'tmp' => array(
											'feedurl' => $arrUnitOptions['feedurls'][$strRandKey],	//<-- this must be set
											'pageurl' => ''	//<-- this can be whatever
										)	
							);
		$arrUnitOptions['categories'] = $arrCategories;
		$arrUnitOptions['imagesize'] = 36;
		$arrUnitOptions['titlelength'] = 60;
		$arrUnitOptions['containerformat']	= '<div style="width:70%;float:right;"><div class="amazon-auto-links-userads" style="float:right;padding:8px 0px 0px 20px; vertical-align:middle">%items%</div></div>';
		$arrUnitOptions['itemformat'] = '<div valign="middle"><a href="%link%" title="%title%: %textdescription%" rel="nofollow">%img%</a><a href="%link%" title="%title%: %textdescription%" rel="nofollow">%title%</a> %htmldescription%</div>';
		$arrUnitOptions['imgformat'] = '<img src="%imgurl%" alt="%textdescription%" style="float:left; margin-right:8px;"/>';
		$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
		$output = $oAAL->fetch();
		if (!$output) AmazonAutoLinks_Log('no result: ad-type: ' . strRandKey_FeedTypes . ' feed-url: ' . $arrUnitOptions['feedurls'][$strRandKey], __METHOD__);	
		echo $output;
	}	
	
}