<?php
/**
 *	Provides utility methods that uses WordPerss built-in functions.
 *
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0
 * 
 */

final class AmazonAutoLinks_WPUtilities {

	/**
	 * Returns an array of the installed taxonomies on the site.
	 * 
	 */
	public static function getSiteTaxonomies() {
		
		$arrTaxonomies = get_taxonomies( '', 'names' );
		unset( $arrTaxonomies['nav_menu'] );
		unset( $arrTaxonomies['link_category'] );
		unset( $arrTaxonomies['post_format'] );
		return $arrTaxonomies;
		
	}

	/**
	 * Returns an array of associated taxonomies of the given post.
	 * 
	 * @param			string|integer|object			$vPost			Either the post ID or the post object.
	 */
	public static function getPostTaxonomies( $vPost ) {
		
		if ( is_integer( $vPost ) || is_string( $vPost ) )
			$oPost = get_post( $vPost );
		else if ( is_object( $vPost ) )
			$oPost = $vPost;
					
		return ( array ) get_object_taxonomies( $oPost, 'objects' );

	}	
	
	/**
	 * Returns the current url of admin page.
	 */
	public static function getCurrentAdminURL() {
		
		return add_query_arg( $_GET, admin_url( $GLOBALS['pagenow'] ) );
		
	}
	
}