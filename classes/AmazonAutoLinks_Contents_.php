<?php
class AmazonAutoLinks_Contents_ {
	/*
	 * Since v1.1.3
	 *	Separated from the AmazonAutoLinks_Admin_ class.
		This class is for Shortcode, hooks for content, excerpt, and rss-feed.
	*/

	function __construct( $strPluginkey , &$oOption ) {
		
		$this->pluginkey = $strPluginkey;
		$this->pluginname = 'Amazon Auto Links';
		
		// the option array
		$this->oOption = $oOption; 
			
	}
	function RegisterHooks() {
		
		// Create Shortcode
		add_shortcode( $this->pluginkey, array( $this, 'shortcode' ) );
		
		// Hook post & RSS contents
		add_filter( 'the_content', array( $this, 'insertinpost' ) );
		add_filter( 'the_excerpt', array( $this, 'insertinexcerpt' ) );
		add_filter( 'the_content_feed', array( $this, 'insertincontentfeed' ) );
		add_filter( 'the_excerpt_rss', array( $this, 'insertinexcerptrss' ) );				
		add_filter( 'wp_insert_post_data' , array( $this, 'InsertInPostOnPublish' ) , '99', 2 );
	}
	
	function shortcode($atts) {
	
		// reload the option since the timing of this function call depends and the options can have not be updated
		extract(shortcode_atts(array(
			'label' => '',
			// 'numitems' => 10,
		), $atts));
		$strUnitID = $this->oOption->get_unitid_from_unitlabel( $label );
		if ( !$strUnitID ) {
			echo $this->pluginname . ' ';
			_e('Error: No such unit label exists.', 'amazon-auto-links');
			echo ':&nbsp;' . $label ;
			return;		
		}
		
		$oAAL = new AmazonAutoLinks_Core($label);
		return $oAAL->fetch();			
	}	
	
	function InsertInPostOnPublish( $arrPostContent, $arrPostMeta='' ) {
		
		// since v1.1.9
		
		// apply only for posts and pages
		if ( $arrPostMeta['post_type'] != 'post' && $arrPostMeta['post_type'] != 'page' ) return $arrPostContent;
				
		// if the publish key exists, it means it is an update
		if ( $arrPostMeta['save'] == 'Update' ) return $arrPostContent;
	
		static $oAALs = array();
		foreach( $this->oOption->arrOptions['units'] as $strUnitID => $arrUnitOptions ) {
			if ( $arrUnitOptions['insert']['postabove_static'] ) {	
				if ( !array_key_exists( $strUnitID, $oAALs ) ) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions );
				$arrPostContent['post_content'] = $oAALs[$strUnitID]->fetch() . $arrPostContent['post_content'];
			}
			if ( $arrUnitOptions['insert']['postbelow_static'] ) {
				if ( !array_key_exists( $strUnitID, $oAALs ) ) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions );
				$arrPostContent['post_content'] .= $oAALs[$strUnitID]->fetch();			
			}
		}
		unset($oAALs);
		return $arrPostContent;
		
	}

	function insertinpost( $content ) {
		static $oAALs = array();
		
		foreach( $this->oOption->arrOptions['units'] as $strUnitID => $arrUnitOptions ) {
			if ( $arrUnitOptions['insert']['postabove'] ) {	
				if ( !array_key_exists( $strUnitID, $oAALs ) ) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions );	
				$content = $oAALs[$strUnitID]->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['postbelow']) {
				if (!array_key_exists( $strUnitID, $oAALs)) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions );
				$content = $content . $oAALs[$strUnitID]->fetch();			
			}
		}
		unset( $oAALs );
		return trim( $content );
	}
	function insertinexcerpt( $content ){
		foreach($this->oOption->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['excerptabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['excerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		unset($oAAL);
		return trim($content);
	}
	function insertincontentfeed($content) {
		foreach($this->oOption->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		unset($oAAL);
		return trim($content);
	}
	function insertinexcerptrss($content) {

		foreach($this->oOption->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedexcerptabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedexcerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		unset($oAAL);
		return trim($content);
	}
		
}