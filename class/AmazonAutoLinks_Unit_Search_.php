<?php
/**
 * Creates Amazon product links by search.
 * 
 * @package     	Amazon Auto Links
 * @copyright   	Copyright (c) 2013, Michael Uno
 * @license     	http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

abstract class AmazonAutoLinks_Unit_Search_ extends AmazonAutoLinks_Unit {

	public static $arrStructure_Args = array(
		'count' => 10,
		'column' => 4,
		'country' => 'US',
		'associate_id' => null,
		'image_size' => 160,
		'Keywords'	=> '',	// the keyword to search
		'Operation' => 'ItemSearch',	// ItemSearch, ItemLookup, SimilarityLookup
		'Sort' => 'salesrank',		// pricerank, inversepricerank, sales_rank, relevancerank, reviewrank
		'SearchIndex' => 'All',		
		'BrowseNode' => '',	// ( optional )
		'Availability' => 'Available',	// ( optional ) 
		'Condition' => 'New',	
		'MaximumPrice' => null,
		'MinimumPrice' => null,
		'MinPercentageOff' => null,
		'additional_attribute' => null,
		'search_by' => 'Author',
		// 'nodes' => 0,	// 0 is for all nodes.	Comma delimited strings will be passed. e.g. 12345,12425,5353
		'ref_nosim' => false,
		'title_length' => -1,
		'description_length' => 250,
		'link_style' => 1,
		'credit_link' => 1,
		'title' => '',		// won't be used to fetch links. Used to create a unit.
		'template' => '',		// the template name - if multiple templates with a same name are registered, the first found item will be used.
		'template_id' => null,	// the template ID: md5( dir path )
		'template_path' => '',	// the template can be specified by the template path. If this is set, the 'template' key won't take effect.
		'cache_duration' => '',
		
		'image_format' => '',
		'title_format' => '',
		'item_format' => '',
		
		/* used outside the class */
		'is_preview' => false,	// for the search unit, true won't be used but just for the code consistency. 
		'operator' => 'AND', // this is for fetching by label. AND, IN, NOT IN can be used
	);

	function __construct( $arrArgs=array() ) {
			
		parent::__construct();
		$this->setArguments( $arrArgs );
		$this->strUnitType = 'search';
		
	}	
	
	public function setArguments( $arrArgs ) {
		
		$this->arrArgs = $arrArgs + self::$arrStructure_Args + self::getItemFormatArray();
		
	}
	
	public function fetch( $arrURLs=array() ) {
		
		// The search unit type does not use directly passed urls.
		// Maybe later at some point, custom request URIs can get implemented and they can be directly passed to this method.
		unset( $arrURLs );
		
// AmazonAutoLinks_Debug::dumpArray( $this->arrArgs );

		$oAPI = new AmazonAutoLinks_ProductAdvertisingAPI( 
			$this->arrArgs['country'], 
			$this->oOption->getAccessPublicKey(),
			$this->oOption->getAccessPrivateKey(),
			$this->arrArgs['associate_id']
		);
			
		// Keys with an empty value will be filtered out when performing the request.			
		$fIsIndexAllOrBlended = ( $this->arrArgs['SearchIndex'] == 'All' || $this->arrArgs['SearchIndex'] == 'Blended' );
		$arrResponse = $oAPI->request( 	
			// Regarding the parameters see: http://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemSearch.html
			array(
				'Keywords' => AmazonAutoLinks_Utilities::trimDelimitedElements( $this->arrArgs['Keywords'], ',', false ),			
				'Title' => $fIsIndexAllOrBlended ? null : AmazonAutoLinks_Utilities::trimDelimitedElements( $this->arrArgs['Keywords'], ',', false ),
				'Operation' => $this->arrArgs['Operation'],
				'SearchIndex' => $this->arrArgs['SearchIndex'],
				$this->arrArgs['search_by'] => $this->arrArgs['additional_attribute'] ? $this->arrArgs['additional_attribute'] : null,
				'Sort' => $fIsIndexAllOrBlended ? null : $this->arrArgs['Sort'],	// when the search index is All, sort cannot be specified
				'ResponseGroup' => "Large",
				'BrowseNode' => ! $fIsIndexAllOrBlended && isset( $this->arrArgs['BrowseNode'] ) && $this->arrArgs['BrowseNode'] ? $this->arrArgs['BrowseNode'] : null,
				'Availability' => isset( $this->arrArgs['Availability'] ) && $this->arrArgs['Availability'] ? 'Available' : null,
				'Condition' => $fIsIndexAllOrBlended ? null : $this->arrArgs['Condition'],
				// 'ItemPage' => 
				'IncludeReviewsSummary' => "True",
				'MaximumPrice' => ! $fIsIndexAllOrBlended && $this->arrArgs['MaximumPrice'] ? $this->arrArgs['MaximumPrice'] : null,
				'MinimumPrice' => ! $fIsIndexAllOrBlended && $this->arrArgs['MinimumPrice'] ? $this->arrArgs['MinimumPrice'] : null,
				'MinPercentageOff' => $this->arrArgs['MinPercentageOff'] ? $this->arrArgs['MinPercentageOff'] : null,
			)
		);
		if ( isset( $arrResponse['Error']['Code']['Message'] ) ) 
			return $arrResponse;
			
			
// $arrProductItems = $arrResponse['Items'];
// unset( $arrProductItems['Item'] );
// AmazonAutoLinks_Debug::dumpArray( $arrProductItems );			
		
		$arrProducts = $this->composeArray( $arrResponse );
		
// AmazonAutoLinks_Debug::dumpArray( $arrProducts );
			
		return $arrProducts;
		
	}
	
	protected function composeArray( $arrResponse ) {

		$arrItems = isset( $arrResponse['Items']['Item'] ) ? $arrResponse['Items']['Item'] : $arrResponse;

		// When only one item is found, the item elements are not contained in an array. So contain it.
		if ( isset( $arrItems['ASIN'] ) ) $arrItems = array( $arrItems );
		
		$arrProducts = array();
		foreach ( ( array ) $arrItems as $arrItem )	{

			if ( $this->isBlocked( $arrItem['ASIN'], 'asin' ) ) continue;
			if ( $this->arrArgs['is_preview'] || ! $this->fNoDuplicate )
				$this->arrBlackListASINs[] = $arrItem['ASIN'];	// this search unit type does not have the preview mode so it won't be triggered
			else 
				$GLOBALS['arrBlackASINs'][] = $arrItem['ASIN'];	
				
			$strTitle = $this->sanitizeTitle( $arrItem['ItemAttributes']['Title'] );
			if ( $this->isBlocked( $strTitle, 'title' ) ) continue;
			
			$strProductURL = $this->formatProductLinkURL( rawurldecode( $arrItem['DetailPageURL'] ), $arrItem['ASIN'] );
// AmazonAutoLinks_Debug::dumpArray( $arrItem );			
			$strContent = isset( $arrItem['EditorialReviews']['EditorialReview'] ) 
				? $this->joinIfArray( $arrItem['EditorialReviews']['EditorialReview'], 'Content' )
				: '';
			$strDescription = $this->sanitizeDescription( $strContent, $this->arrArgs['description_length'], $strProductURL );
			if ( $this->isBlocked( $strDescription, 'description' ) ) continue;
			
		// unset( $arrItem['ItemLinks'], $arrItem['ImageSets'], $arrItem['BrowseNodes'], $arrItem['SimilarProducts'] );
			$arrProduct = array(
				'ASIN' => $arrItem['ASIN'],
				'product_url' => $strProductURL,
				'title' => $strTitle,
				'text_description' => $this->sanitizeDescription( $strContent, 250 ),
				'description' => $strDescription,
				'meta' => '',
				'content'  => $strContent,
				'image_size' => $this->arrArgs['image_size'],
				'thumbnail_url' => $this->formatImage( $arrItem['MediumImage']['URL'], $this->arrArgs['image_size'] ),	
				'author' => isset( $arrItem['ItemAttributes']['Author'] ) ? implode( ', ', ( array ) $arrItem['ItemAttributes']['Author'] ) : '',
				// 'manufacturer' => $arrItem['ItemAttributes']['Manufacturer'], 
				'category' => isset( $arrItem['ItemAttributes']['ProductGroup'] ) ? $arrItem['ItemAttributes']['ProductGroup'] : '',
				'date' => isset( $arrItem['ItemAttributes']['PublicationDate'] ) ? $arrItem['ItemAttributes']['PublicationDate'] : '',	// ReleaseDate
				// 'is_adult_product' => $arrItem['ItemAttributes']['IsAdultProduct'],
				'price' => isset( $arrItem['ItemAttributes']['ListPrice']['FormattedPrice'] ) ? $arrItem['ItemAttributes']['ListPrice']['FormattedPrice'] : '',
				'lowest_new_price' => isset( $arrItem['OfferSummary']['LowestNewPrice']['FormattedPrice'] ) ? $arrItem['OfferSummary']['LowestNewPrice']['FormattedPrice'] : '',
				'lowest_used_price' => isset( $arrItem['OfferSummary']['LowestUsedPrice']['FormattedPrice'] ) ? $arrItem['OfferSummary']['LowestUsedPrice']['FormattedPrice'] : '',
			) + $arrItem;
			
			// Add meta data to the description
			$arrProduct['meta'] .= $arrProduct['author'] ? "<span class='amazon-product-author'>" . sprintf( __( 'by %1$s', 'amazon-auto-links' ) . "</span>", $arrProduct['author'] ) . ' ' : '';
			$arrProduct['meta'] .= $arrProduct['price'] ? "<span class='amazon-product-price'>" . sprintf( __( 'at %1$s', 'amazon-auto-links' ), $arrProduct['price'] ) . "</span> " : '';
			$arrProduct['meta'] .= $arrProduct['lowest_new_price'] ? "<span class='amazon-product-lowest-new-price'>" . sprintf( __( 'New from %1$s', 'amazon-auto-links' ) . "</span> ", $arrProduct['lowest_new_price'] ) . ' ' : '';
			$arrProduct['meta'] .= $arrProduct['lowest_used_price'] ? "<span class='amazon-product-lowest-used-price'>" . sprintf( __( 'Used from %1$s', 'amazon-auto-links' ) . "</span> ", $arrProduct['lowest_used_price'] ) . ' ' : '';
			$arrProduct['meta'] = empty( $arrProduct['meta'] ) ? '' : "<div class='amazon-product-meta'>{$arrProduct['meta']}</div>";
			$arrProduct['description'] = $arrProduct['meta'] . $arrProduct['description'];
						
			// Format the item
			// Thumbnail
			$arrProduct['formed_thumbnail'] = str_replace( 
				array( "%href%", "%title_text%", "%src%", "%max_width%", "%description_text%" ),
				array( $arrProduct['product_url'], $arrProduct['title'], $arrProduct['thumbnail_url'], $this->arrArgs['image_size'], $arrProduct['text_description'] ),
				$this->arrArgs['image_format'] 
			);
			// Title
			$arrProduct['formed_title'] = str_replace( 
				array( "%href%", "%title_text%", "%description_text%" ),
				array( $arrProduct['product_url'], $arrProduct['title'], $arrProduct['text_description'] ),
				$this->arrArgs['title_format'] 
			);
			// Item		
			$arrProduct['formed_item'] = str_replace( 
				array( "%href%", "%title_text%", "%description_text%", "%title%", "%image%", "%description%" ),
				array( $arrProduct['product_url'], $arrProduct['title'], $arrProduct['text_description'], $arrProduct['formed_title'], $arrProduct['formed_thumbnail'], $arrProduct['description'] ),
				$this->arrArgs['item_format'] 
			);
			
			$arrProducts[] = $arrProduct;		
			
			
			
		}
			
		return $arrProducts;
		
	}
		/**
		 * Joins the given value if it is an array with the provided key.
		 * 
		 */
		protected function joinIfArray( $arrParentArray, $strKey ) {
			
			if ( isset( $arrParentArray[ $strKey ] ) ) return ( string ) $arrParentArray[ $strKey ];
			
			$arrElems = array();
			foreach( $arrParentArray as $vElem ) 
				if ( isset( $vElem[ $strKey ] ) )
					$arrElems[] = $vElem[ $strKey ];
					
			return implode( '', $arrElems );		
			
		}
		
	
	protected function formatImage( $strImageURL, $numImageSize ) {
		
		if ( $this->fIsSSL )
			$strImageURL = $this->respectSSLImage( $strImageURL );
		return $this->setImageSize( $strImageURL, $numImageSize );
		
	}
	
}