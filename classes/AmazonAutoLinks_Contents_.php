<?php
class AmazonAutoLinks_Contents_ {
	/*
	 *	Separated from the AmazonAutoLinks_Admin_ class.
		This class is for Shortcode, hooks for contents, excerpts, and rss-feed.
	*/

	function __construct($oOptions) {
		
		// the option array
		$this->oAALOptions = $oOptions; // new AmazonAutoLinks_Options($this->pluginkey);
			
	}
	function RegisterHooks() {
		
		// Create Shortcode
		add_shortcode($this->pluginkey, array(&$this, 'shortcode'));
		
		// Hook post & RSS contents
		add_filter('the_content', array(&$this, 'insertinpost'));
		add_filter('the_excerpt', array(&$this, 'insertinexcerpt'));
		add_filter('the_content_feed', array(&$this, 'insertincontentfeed'));
		add_filter('the_excerpt_rss', array(&$this, 'insertinexcerptrss'));				
		
	}
	
	function shortcode($atts) {
	
		// reload the option since the timing of this function call depends and the options can have not be updated
		extract(shortcode_atts(array(
			'label' => '',
			// 'numitems' => 10,
		), $atts));
		$strUnitID = $this->oAALOptions->get_unitid_from_unitlabel($label);
		if (!$strUnitID) {
			echo $this->pluginname . ' ';
			_e('Error: No such unit label exists.', 'amazonautolinks');
			return;		
		}
		
		// $oAAL = new AmazonAutoLinks_Core($this->oAALOptions->arrOptions['units'][$label]);
		$oAAL = new AmazonAutoLinks_Core($label);
		return $oAAL->fetch();			
	}	

	function insertinpost($content) {
		// if (is_home()) return $content;
		static $oAALs = array();
		
		foreach($this->oAALOptions->arrOptions['units'] as $strUnitID => $arrUnitOptions) {
			if ($arrUnitOptions['insert']['postabove']) {	
				if (!array_key_exists(strUnitID, $oAALs)) $oAALs[$strUnitID] = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAALs[$strUnitID]->fetch() . $content;
				// $oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				// $content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['postbelow']) {
				if (!array_key_exists(strUnitID, $oAALs)) $oAALs[$strUnitID] = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAALs[$strUnitID]->fetch();			
				// $oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				// $content = $content . $oAAL->fetch();
			}
		}
		return trim($content);
	}
	function insertinexcerpt($content){
		foreach($this->oAALOptions->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['excerptabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['excerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		return trim($content);
	}
	function insertincontentfeed($content) {
		foreach($this->oAALOptions->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		return trim($content);
	}
	function insertinexcerptrss($content) {

		foreach($this->oAALOptions->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedexcerptabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedexcerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		return trim($content);
	}
		
}