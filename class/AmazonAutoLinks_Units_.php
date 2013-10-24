<?php
/**
 * Handles unit outputs.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0
*/
abstract class AmazonAutoLinks_Units_ {

	function __construct( $arrArgs ) {
		
		$this->arrArgs = $arrArgs;
		
	}

	public function render() {
		echo $this->getOutput();
	}
	
	public function getOutput() {
			
		// Retrieve IDs 
		$arrIDs = array();

		// The id parameter.
		if ( isset( $this->arrArgs['id'] ) )	// the id parameter can accept comma delimited ids.
			if ( is_string( $this->arrArgs['id'] ) || is_integer( $this->arrArgs['id'] ) )
				$arrIDs = array_merge( AmazonAutoLinks_Utilities::convertStringToArray( $this->arrArgs['id'], "," ), $arrIDs );
			else if ( is_array( $this->arrArgs['id'] ) )
				$arrIDs = $this->arrArgs['id'];	// The Auto-insert feature passes the id as array.
			
		// The label parameter.
		if ( isset( $this->arrArgs['label'] ) ) {
			
			$arrLabels = AmazonAutoLinks_Utilities::convertStringToArray( $this->arrArgs['label'], "," );
			$arrIDs = array_merge( $this->getPostIDsByLabel( $arrLabels, isset( $arrArgs['operator'] ) ? $arrArgs['operator'] : null ), $arrIDs );
			
		}
			
		$arrOutputs = array();
		$arrIDs = array_unique( $arrIDs );

// AmazonAutoLinks_Debug::logArray( $arrIDs );		
		foreach( $arrIDs as $intID ) 
			$arrOutputs[] = $this->getOutputByID( $intID );
		
		return implode( '', $arrOutputs );
		

	}
			
		protected function getOutputByID( $intPostID ) {
			
			$arrUnitOptions = AmazonAutoLinks_Option::getUnitOptionsByPostID( $intPostID );
			$arrUnitOptions = $this->arrArgs + $arrUnitOptions + array( 'unit_type' => null );	// if the unit gets deleted, auto-insert causes an error for not finding the options
			switch ( $arrUnitOptions['unit_type'] ) {
				case 'category':
					$oAALCat = new AmazonAutoLinks_Unit_Category( $arrUnitOptions );
					return $oAALCat->getOutput();
				case 'tag':
					$oAALTag = new AmazonAutoLinks_Unit_Tag( $arrUnitOptions );
					return $oAALTag->getOutput();
				case 'search':
					$oAALSearch = new AmazonAutoLinks_Unit_Search( $arrUnitOptions );
					return $oAALSearch->getOutput();
				default:
					return AmazonAutoLinks_Commons::$strPluginName . ': ' . __( 'Could not identify the unit type.', 'amazon-auto-links' );

			}		
			
		}
		
		protected function getPostIDsByLabel( $arrLabels, $strOperator ) {
			
			// Retrieve the taxonomy slugs of the given taxonomy names.
			$arrTermSlugs = array();
			foreach( ( array ) $arrLabels as $strTermName ) {
				
				$arrTerm = get_term_by( 'name', $strTermName, AmazonAutoLinks_Commons::TagSlug, ARRAY_A );
				$arrTermSlugs[] = $arrTerm['slug'];
				
			}

			return $this->getPostIDsByTag( $arrTermSlugs, 'slug', $strOperator );
			
		}

		public function getPostIDsByTag( $arrTermSlugs, $strFieldType='slug', $strOperator='AND' ) {

			if ( empty( $arrTermSlugs ) ) return array();
				
			$strFieldType = $this->sanitizeFieldKey( $strFieldType );	// only id or slug 

			$arrPostObjects = get_posts( 
				array(
					'post_type' => AmazonAutoLinks_Commons::PostTypeSlug,	// fetch_tweets
					'posts_per_page' => -1, // ALL posts
					'tax_query' => array(
						array(
							'taxonomy' => AmazonAutoLinks_Commons::TagSlug,	// fetch_tweets_tag
							'field' => $strFieldType,	// id or slug
							'terms' => $arrTermSlugs,	// the array of term slugs
							'operator' => $this->sanitizeOperator( $strOperator ),	// 'IN', 'NOT IN', 'AND. If the item is only one, use AND.
						)
					)
				)
			);
			$arrIDs = array();
			foreach( $arrPostObjects as $oPost )
				$arrIDs[] = $oPost->ID;
			return array_unique( $arrIDs );
			
		}
		protected function sanitizeFieldKey( $strField ) {
			switch( strtolower( trim( $strField ) ) ) {
				case 'id':
					return 'id';
				default:
				case 'slug':
					return 'slug';
			}		
		}
		protected function sanitizeOperator( $strOperator ) {
			switch( strtoupper( trim( $strOperator ) ) ) {
				case 'NOT IN':
					return 'NOT IN';
				case 'IN':
					return 'IN';
				default:
				case 'AND':
					return 'AND';
			}
		}		
	
}