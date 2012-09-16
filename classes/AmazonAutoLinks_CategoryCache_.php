<?php
class AmazonAutoLinks_CategoryCache {
/*
	Since: v1.0.3
	Description:
		This class is used to build category caches for the category selection page.
		The methods are called in a run-once scheduled event prior to user's opening the page.
		It is like link prefetching functionality.
		
		This class uses the 'amazonautolinks_events' key for the option which is separated from
		the plugin main option key, 'amazonautolinks' because cron tasks constantly updates the 
		option in the background, it should not affect other processes using the main option and
		vice versa.
*/
	protected $pluginname = 'Amazon Auto Links';
	protected $pluginkey = 'amazonautolinks';
	protected $eventoptionkey = 'amazonautolinks_events';
	protected $pageslug = 'amazonautolinks';
	protected $textdomain = 'amazonautolinks';	
	protected $arrCountryURLs = array(
		'AT'	=> 'http://www.amazon.de/gp/bestsellers/',
		'CA'	=> 'http://www.amazon.ca/gp/bestsellers/',
		'CN'	=> 'http://www.amazon.cn/gp/bestsellers/',
		'FR'	=> 'http://www.amazon.fr/gp/bestsellers/',
		'DE'	=> 'http://www.amazon.de/gp/bestsellers/',
		'IT'	=> 'http://www.amazon.it/gp/bestsellers/',
		'JP'	=> 'http://www.amazon.co.jp/gp/bestsellers/',
		'UK'	=> 'http://www.amazon.co.uk/gp/bestsellers/',
		'ES'	=> 'http://www.amazon.es/gp/bestsellers/',
		'US'	=> 'http://www.amazon.com/gp/bestsellers/',
	);
	public $arrCountryLang = array(
		'AT'	=> 'uni',
		'CA'	=> 'uni',
		'CN'	=> 'uni',
		'FR'	=> 'uni',
		'DE'	=> 'uni',
		'IT'	=> 'uni',
		'JP'	=> 'ja',
		'UK'	=> 'en',
		'ES'	=> 'uni',
		'US'	=> 'en',	
	);	
	function __construct($pluginkey) {
			
		// set up properties
		$this->pluginkey = $pluginkey;
		// $this->eventoptionkey = $pluginkey . '_events';
		// set up classes
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($pluginkey);
		
		// format the event option
		$this->formatoption();
	}
	function formatoption() {
		$arrEventOptions = get_option('amazonautolinks_events');
		$arrEventOptions = array_merge(array('events' => array()), is_array($arrEventOptions) ? $arrEventOptions : array());
		update_option('amazonautolinks_events', $arrEventOptions);
	}	
	function eventkey($strURL) {
		return 'aal_' . sha1($strURL);
	}
	function schedule_prefetch($strURL='') {
		echo '<!-- Amazon Auto Links: The prefetch function is called. Deciding whether prefetch tasks should be created. -->';		
		// save urls in the option key, ['events'], with the key name of 'aal_' . sha1($strURL), 
		// which is used as the event action name
		// if the $strURL is empty, it means county urls (the root page) will be inserted
	
		// to check if the url is already scheduled
		$arrEventOptions = get_option('amazonautolinks_events');
	
		// if $strURL has a value, it means the url is specified, then pre-fetch only the url contents.
		if ($strURL != '') {	
			$strURL = preg_replace('/ref=.+$/i', '', $strURL);	// http://amazon.com/..../ref=nnn/1832-39087  to http://amazon.com/..../
			$this->schedule_prefetch_from_url_array($this->get_subcategories_from_url($strURL), $arrEventOptions['events']);
			return;
		} 
	
		// otherwise, fetch root pages.
		$this->schedule_prefetch_from_url_array($this->arrCountryURLs, $arrEventOptions['events']);		
	}
	function is_exec_enabled() {
		$arrDisabled = explode(', ', ini_get('disable_functions'));
		return (!(in_array('exec', $arrDisabled) || in_array('shell_exec', $arrDisabled)));
	}	
	function run_in_background($strMsg='called a php process in the background') {
		if (!$this->is_exec_enabled()) {
			AmazonAutoLinks_Log('Could not run a background process since the server disabled the shell_exec function.', __FUNCTION__ );
			return;
		}
		AmazonAutoLinks_Log('shell_exec is enabled. Keep it going.', __FUNCTION__ );
						
		$strDump = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'NUL &' : '/dev/null 2>/dev/null &';
		// $strDump = '2>&1';	// use this for debugging
		$strBakePie = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? '&' : ';';
		$strPHPPath = 'php';
					
		$output = shell_exec("cd " . escapeshellarg(ABSPATH) . $strBakePie . " " . $strPHPPath . ' index.php ' . $strDump);

		echo '<!-- ' . __FUNCTION__ . ': ' . $strMsg . ' -->';
		AmazonAutoLinks_Log($strMsg, __FUNCTION__ );
		if ($strDump == '2>&1') AmazonAutoLinks_Log('$output: ' . mb_substr ( strip_tags($output) , 0, 200), ABSPATH );
	}	
	function schedule_prefetch_from_url_array($arrURLs, $arrEvents) {
		$i = 0;
		foreach($arrURLs as $strURL) {
		
			// if the transient for this url already exists, skip it
			$html = get_transient( $this->eventkey($strURL) ) ;
			if ( $html ) {
			// if( false !== $html ) {		// if ( true === ( $value = get_transient( $this->eventkey($strURL) ) ) ) <-- not sure this doesn't work
				echo '<!-- The transient for the url already exists, not scheduling prefetch. : ' . $this->eventkey($strURL) . ' : ' .  $strURL . ' -->';
				// AmazonAutoLinks_Log('Transient already exists, not scheduling prefetch : ' . $this->eventkey($strURL) . ' : ' .  $strURL, __FUNCTION__ );
				continue;
			}
		
			// if the url is being scheduled now, skip it 
			if (in_array($strURL, $arrEvents)) {
				echo '<!-- The transient is in the event que. : ' . $this->eventkey($strURL) . ' : ' .  $strURL . ' -->';
				// AmazonAutoLinks_Log('transient is in the event que. : ' . $this->eventkey($strURL) . ' : ' .  $strURL, __FUNCTION__ );
				continue;
			}
				
			// go for it
			$this->schedule_prefetch_event_now($strURL);
			
			// count
			$i++;
		}
		
		echo '<!-- ' . $i . ' url(s) are scheduled to pre-fetch. -->';
		AmazonAutoLinks_Log($i . ' url(s) are scheduled to pre-fetch.', __FUNCTION__ );
		if ($i == 0) 
			return;

		// run the WordPress Cron silently before the user loads another page.
AmazonAutoLinks_Log('Line befeore run_in_background.', __FUNCTION__ );
		$this->run_in_background('run the WordPress Cron silently before the user loads another page.', __FUNCTION__);
AmazonAutoLinks_Log('Line after run_in_background.', __FUNCTION__ );		
	}
	function schedule_prefetch_event_now($strURL) {
		
		$this->set_catcache_url($strURL);		// save the action name in the option.
		$strKey = $this->eventkey($strURL);		// define the action name
		
		// Without this add_action(), events won't fire eventhough this plugin loasds saved action names at the beginning. 
		// $strKey holds the action name for the event. The second paramer is the function name defined in amazonautolinks.php
		add_action($strKey, 'AmazonAutoLinks_CacheCategory');	
		
		// the value in the first parameter means do it right away. But actually it will be executed in the next page load.
		wp_schedule_single_event(time(), $strKey);	
		AmazonAutoLinks_Log('prefetch task is scheduled just now.', __FUNCTION__ );
		echo '<!-- Amazon Auto Links: the prefetch task is scheduled just now: ' . $strKey . ': ' . $strURL . ' -->'; 
	}
	function set_catcache_url($strURL) {
	
		// saves the event option. The event option is managed separately from the other option
		// since events occur sort of asyncronomously 
		$arrEventOptions = get_option('amazonautolinks_events');
		$arrEventOptions['events'][$this->eventkey($strURL)] = $strURL;	// add the new item to the event option array

// echo 'updated: ' . $this->eventkey($strURL) . ' : ' . $strURL . '<br />';
		update_option('amazonautolinks_events', $arrEventOptions);
// $arrEventOptions = get_option('amazonautolinks_events');
// echo count($arrEventOptions['events']) . ' of events are now saved.<br />';		
	}
	function cache_html($strURL) {
		$strTransient = $this->eventkey($strURL); // the char lengths for the transient key must be within 45. 			
		$html = get_transient($strTransient);	
		if( $html ) { // if cache is available, do nothing
			echo '<!-- ' . __FUNCTION__ . ': page cache already exists: ' . $strTransient . ' : ' . $strURL . ' -->';
			$this->log('page cache already exists: ' . $strTransient . ' : ' . $strURL, __FUNCTION__);		
			return false;
		}
		
		// if the cache is empty
		$html = $this->oAALfuncs->get_html($strURL);
		set_transient($strTransient, $this->oAALfuncs->encrypt($html), 60*60*12 );
		echo '<!-- ' . __FUNCTION__ . ': page is cached: ' . $strTransient . ' : ' . $strURL . ' -->';
		$this->log('page is cached: ' . $strTransient . ' : ' . $strURL , __FUNCTION__);		
		return true;
	} 
	function log($strMsg, $strFunc='') {
		AmazonAutoLinks_Log($strMsg, $strFunc);
		return;
		// for debugging
/* 		if ($strFunc=='') $strFunc = __FUNCTION__;
		$file = dirname(__DIR__) . '/log.html';
		$strLog = date('l jS \of F Y h:i:s A') . ': ' . $strFunc . ': ' . $strMsg . '<br />';
		file_put_contents($file, $strLog, FILE_APPEND); */
	}
	function get_html($strURL) {
	
		// retrieve web contents from the cache if available; otherwise, retrieve them from the web.
		$strTransient = $this->eventkey($strURL);	// the char lengths for the transient key must be within 45. sha1() generates 40 length caracters.
		$html = get_transient($strTransient);		// retrieve the data from cache
		if( false === $html ) {	// if the cache is empty
			echo '<!-- ' . __FUNCTION__ . ' : transient is not used: ' . $strTransient . ': ' . $strURL . ' -->';	
			
			/*
				wp_remote_get() somehow breaks the href strings passed as a url query. 
				Probably it's an encoding issue but I could not find the exact problem.
				If the problem is found, the web grabbing method should be switched to the WordPress HTTP API
				since currently it has only two options cURL or file_get_contents().
			*/
			
			$html = $this->oAALfuncs->get_html($strURL);
			set_transient($strTransient, $this->oAALfuncs->encrypt($html), 60*60*24 );	// the storing data must be encrypted; otherwise, the data gets sanitized by WordPress and crrupts the cache
			echo '<!-- Transient is now saved: ' . $strTransient . ' -->' ;
			AmazonAutoLinks_Log('Transient is now saved: ' . $strTransient , __FUNCTION__ );
			return $html;
		}
		echo '<!-- Amazon Auto Links : transient is used: ' . $strURL . ' -->';	
		AmazonAutoLinks_Log('Transient is used: ' . $strURL , __FUNCTION__ );		
		return $this->oAALfuncs->decrypt($html);	
	}
	function get_subcategories_from_url($strURL) {
	
		// retrieves subcategory urls from the given url and returns an array containing the subcategory urls
		
		// Without spcifying the encoding with mb_language(), the characters get broken.
		// I don't know if there are other workarounds. Let me know if anybody finds a solution for this, please contact to Michael Uno: michael@michaeluno.jp
		mb_language($this->detect_lang($strURL)); 
		$html = $this->oAALfuncs->get_html($strURL);
		$html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'AUTO');
		$doc = new DOMDocument();	
		@$doc->loadHTML($html);	
		return $this->get_subcategories_from_dom($doc);
	}
	function get_subcategories_from_dom($doc) {
		
		$domleftCol = $doc->getElementById('zg_browseRoot');	// zg_browseRoot is the id of the tag containing the list of sub categories
		if (!$domleftCol)
			return;
		$arrURLs = array();
		ForEach( $domleftCol->getElementsByTagName('a') as $nodeA) {
			$strURL = preg_replace('/ref=.+$/i', '', $nodeA->getAttribute('href'));	// http://amazon.com/..../ref=nnn/1832-39087  to http://amazon.com/..../
			array_push($arrURLs, $strURL);
		}
		return array_unique($arrURLs);	
	}
	function detect_lang($strURL) {
	
		// store the checking domain into $arrThisURL['host']
		$arrThisURL = parse_url($strURL);
		
		// parse through the county urls stored in this class property to find the match
		foreach($this->arrCountryURLs as $strID => $strCountryURL) {
			$arrCountryURL = parse_url($strCountryURL);
			if ($arrThisURL['host'] == $arrCountryURL['host']) 	// matched
				return $this->arrCountryLang[$strID];
		}
		
		// this line should not be reached because it means it did not match any but just in case
		return 'uni'; // let's set the default to uni, which likely work in most cases
	}

}
?>