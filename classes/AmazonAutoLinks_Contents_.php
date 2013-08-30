<?php
/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.3
 * @description	Displays product links by the shortcode and the hooks for content, excerpt, and rss-feed.
*/
class AmazonAutoLinks_Contents_ {
	
	function __construct( $strPluginkey , &$oOption ) {
		
		$this->pluginkey = $strPluginkey;
		$this->pluginname = 'Amazon Auto Links';
		
		// the option object
		$this->oOption = $oOption; 
			
		// register hooks
		$this->RegisterHooks();
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
	
		// Apply shortcodes in the feed descriptions and the contents.
		add_filter( 'the_content_feed', 'do_shortcode', 11 );
		add_filter( 'the_excerpt_rss', 'do_shortcode', 11 );	
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
		
		$oAAL = new AmazonAutoLinks_Core( $this->oOption->arrOptions['units'][$strUnitID], $this->oOption );
		return $oAAL->fetch();			
	}	
	
	function InsertInPostOnPublish( $arrPostContent, $arrPostMeta='' ) {
		
		// since v1.1.9
		
		// apply only for posts and pages
		if ( $arrPostMeta['post_type'] != 'post' && $arrPostMeta['post_type'] != 'page' ) return $arrPostContent;
				
		// if the publish key exists, it means it is an update
		if ( isset( $arrPostMeta['save'] ) && $arrPostMeta['save'] == 'Update' ) return $arrPostContent;
	
		static $oAALs = array();
		foreach( $this->oOption->arrOptions['units'] as $strUnitID => $arrUnitOptions ) {
			if ( $arrUnitOptions['insert']['postabove_static'] ) {	
				if ( !array_key_exists( $strUnitID, $oAALs ) ) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$arrPostContent['post_content'] = $oAALs[$strUnitID]->fetch() . $arrPostContent['post_content'];
			}
			if ( $arrUnitOptions['insert']['postbelow_static'] ) {
				if ( !array_key_exists( $strUnitID, $oAALs ) ) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
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
				if ( !array_key_exists( $strUnitID, $oAALs ) ) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );	
				$content = $oAALs[$strUnitID]->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['postbelow']) {
				if (!array_key_exists( $strUnitID, $oAALs)) $oAALs[$strUnitID] = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$content = $content . $oAALs[$strUnitID]->fetch();			
			}
		}
		unset( $oAALs );
		return trim( $content );
	}
	function insertinexcerpt( $content ){
		foreach( $this->oOption->arrOptions['units'] as $arrUnitOptions ) {
			if ($arrUnitOptions['insert']['excerptabove']) {
				$oAAL = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['excerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$content = $content . $oAAL->fetch();
			}
		}	
		unset($oAAL);
		return trim($content);
	}
	function insertincontentfeed($content) {
		foreach($this->oOption->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedabove']) {
				$oAAL = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedbelow']) {
				$oAAL = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$content = $content . $oAAL->fetch();
			}
		}	
		unset($oAAL);
		return trim($content);
	}
	function insertinexcerptrss($content) {

		foreach($this->oOption->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedexcerptabove']) {
				$oAAL = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedexcerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core( $arrUnitOptions, $this->oOption );
				$content = $content . $oAAL->fetch();
			}
		}	
		unset($oAAL);
		return trim($content);
	}
		
}