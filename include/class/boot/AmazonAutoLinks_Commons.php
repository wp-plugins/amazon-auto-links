<?php
/**
 * Provides the common data shared among plugin files.
 * 
 * To use the class, first call the setUpStaticProperties() method, which sets up the necessary properties.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0
*/

final class AmazonAutoLinks_Commons {
	
	const TextDomain = 'amazon-auto-links';
	const PostTypeSlug = 'amazon_auto_links';
	const ShortCode = 'amazon_auto_links';
	const TagSlug = 'amazon_auto_links_tag';
	const AdminOptionKey = 'amazon_auto_links_admin';
	const TransientPrefix = 'AAL';
	const PostTypeSlugAutoInsert = 'aal_auto_insert';	// amazon_auto_links_auto_insert fails creating the post type.
	const PageSettingsSlug = 'aal_settings';	// this is to be referred by Pro and third party extension.
	const SectionID_License = 'pro_license';
	const FieldID_LicenseKey = 'pro_license_key';
	
	// These properties will be defined when performing setUpStaticProperties() method.
	static public $strPluginFilePath ='';	// must set a value as it will be cheched in setUpStaticProperties()
	static public $strPluginDirPath ='';
	static public $strPluginName ='';
	static public $strPluginURI ='';
	static public $strPluginDescription ='';
	static public $strPluginAuthor ='';
	static public $strPluginAuthorURI ='';
	static public $strPluginVersion ='';
	static public $strPluginTextDomain ='';
	static public $strPluginDomainPath ='';
	static public $strPluginNetwork ='';
	static public $strPluginSiteWide ='';
	static public $strPluginStoreURI ='';

	static function setUpStaticProperties( $strPluginFilePath=null ) {
		
		self::$strPluginFilePath = $strPluginFilePath ? $strPluginFilePath : dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'amazon-auto-links.php' ;
		self::$strPluginDirPath = dirname( self::$strPluginFilePath );
		self::$strPluginURI = plugins_url( '', self::$strPluginFilePath );
		
		$arrPluginData = get_file_data( 
			self::$strPluginFilePath, 
			array(
				'strPluginName' => 'Plugin Name',
				'strPluginURI' => 'Plugin URI',
				'strPluginVersion' => 'Version',
				'strPluginDescription' => 'Description',
				'strPluginAuthor' => 'Author',
				'strPluginAuthorURI' => 'Author URI',
				'strPluginTextDomain' => 'Text Domain',
				'strPluginDomainPath' => 'Domain Path',
				'strPluginNetwork' => 'Network',
				'strPluginSiteWide' => 'Site Wide Only',	// Site Wide Only is deprecated in favor of Network.
				'strPluginStoreURI' => 'Store URI',
			),
			'plugin' 
		);
		
		foreach( $arrPluginData as $strKey => $strValue )
			if ( isset( self::${$strKey} ) )	// must be checked as get_file_data() returns a filtered result
				self::${$strKey} = $strValue;
	
	}	
	
	public static function getPluginURL( $strRelativePath='' ) {
		return plugins_url( $strRelativePath, self::$strPluginFilePath );
	}

	
}