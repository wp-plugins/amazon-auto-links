<?php
/**
 * Provides the definitions of form fields for the category type unit.
 * 
 * @since			2.0.0
 * @remark			The admin page and meta box access it.
 */
abstract class AmazonAutoLinks_Form_Search_ extends AmazonAutoLinks_Form {
	
	protected $strPageSlug = 'aal_add_search_unit';
	
	public function getSections( $strPageSlug='' ) {
		
		$strPageSlug = $strPageSlug ? $strPageSlug : $this->strPageSlug;
		return array(
			array(
				'strSectionID'		=> 'search',
				'strTabSlug'		=> 'first_tab',
				'strPageSlug'		=> $strPageSlug,
				'strTitle'			=> __( 'Add New Unit by Search', 'amazon-auto-links' ),
			),		
			array(
				'strSectionID'		=> 'search_second',
				'strTabSlug'		=> 'second_tab',
				'strPageSlug'		=> $strPageSlug,
				'strTitle'			=> __( 'Add New Unit by Search', 'amazon-auto-links' ),
			),		
			array(
				'strSectionID'		=> 'search_advanced',
				'strTabSlug'		=> 'second_tab',
				'strPageSlug'		=> $strPageSlug,
				'strTitle'			=> __( 'Advanced Search Criteria', 'amazon-auto-links' ),
			),				
			array(
				'strSectionID'		=> 'search_auto_insert',
				'strPageSlug'		=> $strPageSlug,
				'strTabSlug'		=> 'second_tab',
				'strTitle'			=> __( 'Auto Insert', 'amazon-auto-links' ),
			),
			array(
				'strSectionID'		=> 'search_template',
				'strPageSlug'		=> $strPageSlug,
				'strTabSlug'		=> 'second_tab',
				'strTitle'			=> __( 'Template', 'amazon-auto-links' ),
			),			
		);
	
	}

	/**
	 * Returns the field array with the given section ID.
	 * 
	 * Pass an empty string to the parameter for meta box options. 
	 * 
	 */	
	public function getFields( $strSectionID='search', $strPrefix='search_' ) {
		
		switch( $strSectionID ) {
			case 'search':
				return $this->getFieldsOfFirstTab( $strSectionID, $strPrefix );
			case 'search_second':
				return $this->getFieldsOfSecondTab( $strSectionID, 'search2_' );
			case 'search_advanced':
				return $this->getFieldsOfAdvanced( $strSectionID, 'search2_' );
			case 'search_auto_insert':
				return $this->getFieldsOfAutoInsert( $strSectionID, 'search2_' ); 
			case 'search_template':
				return $this->getFieldsOfTemplate( $strSectionID, 'search2_' ); 
		}

	}
	
	/**
	 * Returns the field array with the given section ID.
	 * 
	 * Pass an empty string to the parameter for meta box options. 
	 * 
	 */		
	public function getFieldsOfFirstTab( $strSectionID='search', $strPrefix='search_' ) {
		
		return array(
			array(
				'strFieldID' => $strPrefix . 'unit_title',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Unit Name', 'amazon-auto-links' ),
				'strType' => 'text',
				'strDescription' => 'e.g. <code>My Search Unit</code>',
				'vValue' => '',	// the previous value should not appear
			),
			array(
				'strFieldID' => $strPrefix . 'access_key',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Access Key ID', 'amazon-auto-links' ),
				'strDescription' => __( 'The public key consisting of 20 alphabetic characters.', 'amazon-auto-links' )
					. ' e.g.<code>022QF06E7MXBSH9DHM02</code><br />'
					. sprintf( __( 'The keys can be obtained by logging in to the <a href="%1$s" target="_blank">Amazon Web Services web site</a>.', 'amazon-auto-links' ), 'http://aws.amazon.com/' )
					. ' ' . sprintf( __( 'The instruction is documented <a href="%1$s" target="_blank">here</a>.', 'amazon-auto-links' ), '?post_type=amazon_auto_links&page=aal_help&tab=notes#How_to_Obtain_Access_Key_and_Secret_Key' ),
				'strType' => 'text',
				'vSize' => 40,
				'fIf' => empty( $GLOBALS['oAmazonAutoLinks_Option']->arrOptions['aal_settings']['authentication_keys']['access_key'] ),
				'vDefault' => $GLOBALS['oAmazonAutoLinks_Option']->arrOptions['aal_settings']['authentication_keys']['access_key'],
			),
			array(
				'strFieldID' => $strPrefix . 'access_key_secret',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Secret Access Key', 'amazon-auto-links' ),
				'strDescription' => __( 'The private key consisting of 40 alphabetic characters.', 'amazon-auto-links' )
					. ' e.g.<code>kWcrlUX5JEDGM/LtmEENI/aVmYvHNif5zB+d9+ct</code>',
				'strType' => 'text',
				'vSize' => 60,
				'fIf' => empty( $GLOBALS['oAmazonAutoLinks_Option']->arrOptions['aal_settings']['authentication_keys']['access_key_secret'] ),
				'vDefault' => $GLOBALS['oAmazonAutoLinks_Option']->arrOptions['aal_settings']['authentication_keys']['access_key_secret'],
			),			
			array(
				'strFieldID' => $strPrefix . 'country',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Country', 'amazon-auto-links' ),
				'strType' => 'select',
				'vLabel' => array(						
					'CA' => 'CA - ' . __( 'Canada', 'amazon-auto-links' ),
					'CN' => 'CN - ' . __( 'China', 'amazon-auto-links' ),
					'FR' => 'FR - ' . __( 'France', 'amazon-auto-links' ),
					'DE' => 'DE - ' . __( 'Germany', 'amazon-auto-links' ),
					'IT' => 'IT - ' . __( 'Italy', 'amazon-auto-links' ),
					'JP' => 'JP - ' . __( 'Japan', 'amazon-auto-links' ),
					'UK' => 'UK - ' . __( 'United Kingdom', 'amazon-auto-links' ),
					'ES' => 'ES - ' . __( 'Spain', 'amazon-auto-links' ),
					'US' => 'US - ' . __( 'United States', 'amazon-auto-links' ),
					// 'IN' => 'IN - ' . __( 'India', 'amazon-auto-links' ),
					// 'BR' => 'BR - ' . __( 'Brazil', 'amazon-auto-links' ),
					// 'MX' => 'MX - ' . __( 'Mexico', 'amazon-auto-links' ),
				),
				'vDefault' => 'US',
			),				
			array(
				'strFieldID' => $strPrefix . 'associate_id',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Associate ID', 'amazon-auto-links' ),
				'strType' => 'text',
				'strDescription' => 'e.g. <code>miunosoft-20</code>',
			),		
			array(
				'strFieldID' => $strPrefix . 'Operation',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Types', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vLabel' => array(						
					'ItemSearch'		=> '<strong>' . __( 'Products', 'amazon-auto-links' ) . '</strong> - ' . __( 'returns items that satisfy the search criteria in the title and descriptions.', 'amazon-auto-links' ),
					'ItemLookup'		=> '<span class="disabled"><strong>' . __( 'ASIN', 'amazon-auto-links' ) . '</strong> - ' . __( 'returns some or all of the item attributes with the given item identifier.', 'amazon-auto-links' ) . '</span>',
					'SimilarityLookup'	=> '<span class="disabled"><strong>' . __( 'Similar Products', 'amazon-auto-links' ) . '</strong> - ' . __( 'returns products that are similar to one or more items specified.', 'amazon-auto-links' ) . '</span>',
				),
				'vDisable' => array(
					'ItemSearch' => false,
					'ItemLookup' => true,
					'SimilarityLookup'	=> true,
				),
				'strDescription' => __( 'Currently only the Products type search is available. The other types are still work in progress.', 'amazon-auto-links' ),
				'vDefault' => 'ItemSearch', // array( 'ItemSearch' => true ),
			),
			array(  // single button
				'strFieldID' => $strPrefix . 'submit_initial_options',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strType' => 'submit',
				'fIf' => ! empty( $strSectionID ),
				'strBeforeField' => "<div style='display: inline-block;'>" . $this->oUserAds->getTextAd() . "</div>"
					. "<div class='right-button'>",
				'strAfterField' => "</div>",
				'vLabelMinWidth' => 0,
				'vLabel' => __( 'Proceed', 'amazon-auto-links' ),
				'vClassAttribute' => 'button button-primary',
				'strAfterField' => ''
					. "<input type='hidden' name='amazon_auto_links_admin[{$this->strPageSlug}][{$strSectionID}][{$strPrefix}unit_type]' value='search'>"
					. "<input type='hidden' name='amazon_auto_links_admin[{$this->strPageSlug}][{$strSectionID}][{$strPrefix}transient_id]' value='" . ( $strTransientID = isset( $_GET['transient_id'] ) ? $_GET['transient_id'] : uniqid() ) . "'>"
					. "<input type='hidden' name='amazon_auto_links_admin[{$this->strPageSlug}][{$strSectionID}][{$strPrefix}mode]' value='1'>"
					. "<input type='hidden' name='amazon_auto_links_admin[{$this->strPageSlug}][{$strSectionID}][{$strPrefix}bounce_url]' value='" . add_query_arg( array( 'transient_id' => $strTransientID ) + $_GET, admin_url( $GLOBALS['pagenow'] ) ) . "'>",
				'vRedirect'	=> add_query_arg( array( 'tab' => 'second_tab', 'transient_id' => $strTransientID ) + $_GET, admin_url( $GLOBALS['pagenow'] ) ),
			)				
		);
		
	}
	
	/**
	 * Returns the field array with the given section ID.
	 * 
	 * Pass an empty string to the parameter for meta box options. 
	 * 
	 */	
	public function getFieldsOfSecondTab( $strSectionID='search', $strPrefix='search2_' ) {
		
		$arrUnitOptions = isset( $_REQUEST['transient_id'] )
			? get_transient( 'AAL_CreateUnit_' . $_REQUEST['transient_id'] )
			: ( $GLOBALS['strAmazonAutoLinks_UnitType'] == 'search' && isset( $_GET['post'] ) && $_GET['post'] != 0
				? $GLOBALS['oAmazonAutoLinks_Option']->getUnitOptionsByPostID( $_GET['post'] )
				: array()
			);
		
							
		return array(
			array(
				'strFieldID' => $strPrefix . 'unit_title',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Unit Name', 'amazon-auto-links' ),
				'strType' => 'text',
				'fIf' => isset( $_REQUEST['transient_id'] ),
				'vValue' => isset( $arrUnitOptions['unit_title'] ) ? $arrUnitOptions['unit_title'] : null,
			),			
			array(
				'strFieldID' => $strPrefix . 'Keywords',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Search Keyword', 'amazon-auto-links' ),
				'strType' => 'text',
				'vSize' => 60,
				'strDescription' => __( 'Enter the keyword to search. For multiple items, separate them by commas.', 'amazon-auto-links' ) 
					. ' e.g. <code>WordPress, PHP</code>',
				'vValue' => '',	// the previous value should not appear
			),
			array(
				'strFieldID' => $strPrefix . 'search_type',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Search Type', 'amazon-auto-links' ),
				'strType' => 'text',
				'vDisable' => true,
				'vReadOnly' => true,
				'vValue' => isset( $arrUnitOptions['Operation'] ) ? $this->getSearchTypeLabel( $arrUnitOptions['Operation'] ) : null,
			),							
			array(
				'strFieldID' => $strPrefix . 'Operation',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Operation', 'amazon-auto-links' ),
				'strType' => 'hidden',
				'vReadOnly' => true,
				'vValue' => isset( $arrUnitOptions['Operation'] ) ? $arrUnitOptions['Operation'] : null,
			),				
			array(
				'strFieldID' => $strPrefix . 'country',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Locale', 'amazon-auto-links' ),
				'strType' => 'text',
				'vReadOnly' => true,
				'vValue' => isset( $arrUnitOptions['country'] ) ? $arrUnitOptions['country'] : null,	// for the meta box, pass null so it uses the stored value
			),					
			array(
				'strFieldID' => $strPrefix . 'associate_id',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Associate ID', 'amazon-auto-links' ),
				'strType' => 'text',
				'strDescription' => 'e.g. <code>miunosoft-20</code>',
				'vValue' => isset( $arrUnitOptions['associate_id'] ) ? $arrUnitOptions['associate_id'] : null,	// for the meta box, pass null so it uses the stored value
			),		
			array(
				'strFieldID' => $strPrefix . 'SearchIndex',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Categories', 'amazon-auto-links' ),			
				'strType' => 'select',
				'vLabel' => AmazonAutoLinks_Properties::getSearchIndexByLocale( isset( $arrUnitOptions['country'] ) ? $arrUnitOptions['country'] : null ),
				'vDefault' => 'All',
				'strDescription' => __( 'Select the category to limit the searching area.', 'amazon-auto-links' )
					. ' ' . __( 'Since some options do not work with the All index, it is recommended to pick one.', 'amazon-auto-links' ),
			),
/* 			array(
				'strFieldID' => $strPrefix . 'nodes',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Categories', 'amazon-auto-links' ),
				'strType' => 'select',
				'vMultiple' => true,
				// If the transient_id, which should be set in the previous page, is empty, 
				// the locale gets unknown and thus it's impossible to process.
				// $_GET['transient_id'] is for Add Unit by Search page and $_GET['post'] is for the meta box
				'fIf' => isset( $_REQUEST['transient_id'] ) || $GLOBALS['strAmazonAutoLinks_UnitType'] == 'search',
				'vLabel' => $arrList = $this->getNodeListByCategory( $arrUnitOptions ),
				'vSize' => count( $arrList ),
				'vDefault' => 0,	// 0 for All
				'strDescription' => __( 'Select the categories to limit the searching area.', 'amazon-auto-links' ),
			),	 */
	
			array(
				'strFieldID' => $strPrefix . 'count',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Number of Items', 'amazon-auto-links' ),
				'strType' => 'number',
				'vMax' => $GLOBALS['oAmazonAutoLinks_Option']->getMaximumProductLinkCount() ? $GLOBALS['oAmazonAutoLinks_Option']->getMaximumProductLinkCount() : null,
				'vMin' => 1,
				'strDescription' => __( 'The number of product links to display.' ),
				'vDefault' => 10,
			),
			// array(
				// 'strFieldID' => $strPrefix . 'column',
				// 'strSectionID' => $strSectionID ? $strSectionID : null,
				// 'strTitle' => __( 'Number of Columns', 'amazon-auto-links' ),
				// 'strType' => 'number',
				// 'vClassAttribute' => ( $intMaxCol = $GLOBALS['oAmazonAutoLinks_Option']->getMaxSupportedColumnNumber() ) > 1 ? '' : 'disabled',
				// 'vDisable' => $intMaxCol > 1 ? false : true,
				// 'vMax' => $intMaxCol,
				// // 'vMin' => 1, // <-- not sure this horizontally diminishes the input element
				// 'vAfterInputTag' => "<div style='margin:auto; width:100%; clear: both;'><img src='" . AmazonAutoLinks_Commons::getPluginURL( 'image/columns.gif' ) . "' title='" . __( 'The number of columns', 'amazon-auto-links' ) . "' style='width:220px; margin-top: 8px;' /></div>",
				// 'strDescription' => __( 'This option requires a column supported template to be activated.' ) 
					// . ( $intMaxCol > 1 ? '' : ' ' . sprintf( __( 'Get one <a href="%1$s" target="_blank">here</a>!' ), 'http://en.michaeluno.jp/amazon-auto-links-pro/' ) ),
				// 'vDefault' => 4,
				// 'vDelimiter' => '',
			// ),				
			array(
				'strFieldID' => $strPrefix . 'image_size',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Image Size', 'amazon-auto-links' ),
				'strType' => 'number',
				'vAfterInputTag' => ' ' . __( 'pixel', 'amazon-auto-links' ),
				'vDelimiter' => '',
				'strDescription' => __( 'The maximum width of the product image in pixel. Set <code>0</code> for no image.', 'amazon-auto-links' )
					. ' ' . __( 'Max', 'amazon-auto-links' ) . ': <code>500</code> ' 
					. __( 'Default', 'amazon-auto-links' ) . ': <code>160</code>',				
				'vMax' => 500,
				'vMin' => 0,
				'vDefault' => 160,
			),		
			array(
				// see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/SortingbyPopularityPriceorCondition.html
				'strFieldID' => $strPrefix . 'Sort',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Sort Order', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vLabel' => array(						
					'pricerank'			=> "<strong>" . __( 'Price Ascending', 'amazon-auto-links' ) . "</strong> - " . __( 'Sorts items from the cheapest to the most expensive.', 'amazon-auto-links' ) . '<br />',
					'inversepricerank'	=> "<strong>" . __( 'Price Descending', 'amazon-auto-links' ) . "</strong> - " . __( 'Sorts items from the most expensive to the cheapest.', 'amazon-auto-links' ) . '<br />',
					'salesrank'		=> "<strong>" . __( 'Sales Rank', 'amazon-auto-links' ) . "</strong> - " . __( 'Sorts items based on how well they have been sold, from best to worst sellers.', 'amazon-auto-links' ) . '<br />',
					'relevancerank'		=> "<strong>" . __( 'Relevance Rank', 'amazon-auto-links' ) . "</strong> - " . __( 'Sorts items based on how often the keyword appear in the product description.', 'amazon-auto-links' ) . '<br />',
					'reviewrank'		=> "<strong>" . __( 'Review Rank', 'amazon-auto-links' ) . "</strong> - " . __( 'Sorts items based on how highly rated the item was reviewed by customers where the highest ranked items are listed first and the lowest ranked items are listed last.', 'amazon-auto-links' ) . '<br />',
				),
				'vDefault' => 'salesrank',
				'strDescription' => __( 'When the search index is selected to All, this option does not take effect.', 'amazon-auto-links' ),
			),				
			array(
				'strFieldID' => $strPrefix . 'ref_nosim',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Direct Link Bonus', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vLabel' => array(						
					1		=> __( 'On', 'amazon-auto-links' ),
					0		=> __( 'Off', 'amazon-auto-links' ),
				),
				'strDescription'	=> sprintf( __( 'Inserts <code>ref=nosim</code> in the link url. For more information, visit <a href="%1$s">this page</a>.', 'amazon-auto-links' ), 'https://affiliate-program.amazon.co.uk/gp/associates/help/t5/a21' ),
				'vDefault' => 0,
			),		
			array(
				'strFieldID' => $strPrefix . 'title_length',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Title Length', 'amazon-auto-links' ),
				'strType' => 'number',
				'strDescription' => __( 'The allowed character length for the title.', 'amazon-auto-links' ) . '&nbsp;'
					. __( 'Use it to prevent a broken layout caused by a very long product title. Set -1 for no limit.', 'amazon-auto-links' ) . '<br />'
					. __( 'Default', 'amazon-auto-links' ) . ": <code>-1</code>",
				'vDefault' => -1,
			),				
			array(
				'strFieldID' => $strPrefix . 'description_length',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Description Length', 'amazon-auto-links' ),
				'strType' => 'number',
				'strDescription' => __( 'The allowed character length for the description.', 'amazon-auto-links' ) . '&nbsp;'
					. __( 'Set -1 for no limit.', 'amazon-auto-links' ) . '<br />'
					. __( 'Default', 'amazon-auto-links' ) . ": <code>250</code>",
				'vDefault' => 250,
			),		
			array(
				'strFieldID' => $strPrefix . 'link_style',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Link Style', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vLabel' => array(						
					1	=> 'http://www.amazon.<code>[domain-suffix]</code>/<code>[product-name]</code>/dp/<code>[asin]</code>/ref=<code>[...]</code>?tag=<code>[associate-id]</code>'
						. "&nbsp;<span class='description'>(" . __( 'Default', 'amazon-auto-links' ) . ")</span>",
					2	=> 'http://www.amazon.<code>[domain-suffix]</code>/exec/obidos/ASIN/<code>[asin]</code>/<code>[associate-id]</code>/ref=<code>[...]</code>',
					3	=> 'http://www.amazon.<code>[domain-suffix]</code>/gp/product/<code>[asin]</code>/?tag=<code>[associate-id]</code>&ref=<code>[...]</code>',
					4	=> 'http://www.amazon.<code>[domain-suffix]</code>/dp/ASIN/<code>[asin]</code>/ref=<code>[...]</code>?tag=<code>[associate-id]</code>',
					5	=> site_url() . '?' . $GLOBALS['oAmazonAutoLinks_Option']->arrOptions['aal_settings']['query']['cloak'] . '=<code>[asin]</code>&locale=<code>[...]</code>&tag=<code>[associate-id]</code>'
				),
				'vDefault' => 1,
			),		
			array(
				'strFieldID' => $strPrefix . 'credit_link',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Credit Link', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vLabel' => array(						
					1		=> __( 'On', 'amazon-auto-links' ),
					0		=> __( 'Off', 'amazon-auto-links' ),
				),
				'strDescription'	=> sprintf( __( 'Inserts the credit link at the end of the unit output.', 'amazon-auto-links' ), '' ),
				'vDefault' => 1,
			),	
		);
	}

		protected function getSearchTypeLabel( $strSearchTypeKey ) {
			switch ( $strSearchTypeKey ) {
				case 'ItemSearch' :
					return __( 'Products', 'amazon-auto-links' );
				case 'ItemLookup' :
					return __( 'ASIN', 'amazon-auto-links' );
				case 'SimilarityLookup' :
					return __( 'Similar Products', 'amazon-auto-links' );				
			}
		}
		protected function getNodeListByCategory( $arrPreviousPageInput ) {
			
			// Determine the locale.
			if ( ! empty( $arrPreviousPageInput ) ) 	
				$strLocale = $arrPreviousPageInput['country'];
			else if ( $GLOBALS['strAmazonAutoLinks_UnitType'] == 'search' ) 
				$strLocale = get_post_meta( $_GET['post'], 'country', true );			
			else 
				return array( 'error' => 'ERROR' );
	
			// Prepare API object
			$strPublicKey = $GLOBALS['oAmazonAutoLinks_Option']->getAccessPublicKey();
			$strPrivateKey = $GLOBALS['oAmazonAutoLinks_Option']->getAccessPrivateKey();		
			$oAmazonAPI = new AmazonAutoLinks_ProductAdvertisingAPI( $strLocale, $strPublicKey, $strPrivateKey );
			
	
			// Now fetch the category node.
			$arrBrowseNodes = array();
			$arrNodeLabels = array( 0 => __( 'All', 'amazon-auto-links' ) );
			
			foreach( AmazonAutoLinks_Properties::getRootNoeds( $strLocale ) as $arrNodeIDs ) 	{
				
				$arrResult = $oAmazonAPI->request( 
					array(
						"Operation" => "BrowseNodeLookup",
						"BrowseNodeId" => implode( ',', $arrNodeIDs ),
					),
					$strLocale,
					30	// 24*3600*7 // set custom cache lifespan, 7 days
				);
				if ( ! isset( $arrResult['BrowseNodes']['BrowseNode'] ) ) continue;
						
				$arrBrowseNodes = array_merge( $arrResult['BrowseNodes']['BrowseNode'], $arrBrowseNodes );
		
			}
				
			foreach( $arrBrowseNodes as $arrNode ) {
				
				if ( isset( $arrNode['Ancestors'] ) )
					$arrNode = $arrNode['Ancestors']['BrowseNode'];
													
				$arrNodeLabels[ $arrNode['BrowseNodeId'] ] = $arrNode['Name'];
				
			}
				
// AmazonAutoLinks_Debug::logArray( $arrNodeLabels );	
			
			return $arrNodeLabels;
		}
	
	/**
	 * 
	 * @remark			The scope is public because the meta box calls it.
	 */
	public function getFieldsOfAdvanced( $strSectionID, $strPrefix ) {
 			
		$fIsDisabled = ! $GLOBALS['oAmazonAutoLinks_Option']->isAdvancedAllowed();
		$strOpeningTag = $fIsDisabled ? "<div class='upgrade-to-pro' style='margin:0; padding:0; display: inline-block;' title='" . __( 'Please consider upgrading to Pro to use this feature!', 'amazon-auto-links' ) . "'>" : "";
		$strClosingTag = $fIsDisabled ? "</div>" : "";
		
		return array(
			array(
				'strFieldID' => $strPrefix . 'Title',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Title', 'amazon-auto-links' ) . ' <span class="description">(' . __( 'optional', 'amazon-auto-links' ) . ')</span>',
				'strType' => 'text',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,
				'strDescription' => __( 'Enter keywords which should be matched in the product title. For multiple keywords, separate them by commas.', 'amazon-auto-links' )
					. ' ' . __( 'If this is set, the Search Keyword option can be empty.', 'amazon-auto-links' ), 
			),
			array(
				'strFieldID' => $strPrefix . 'additional_attribute',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Additional Attribute', 'amazon-auto-links' ) . ' <span class="description">(' . __( 'optional', 'amazon-auto-links' ) . ')</span>',
				'strType' => 'text',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,
			),	
			array(
				'strFieldID' => $strPrefix . 'search_by',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => '', //__( '', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',	
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,				
				'vLabel' => array(
					'Manufacturer'	=> __( 'Manufacturer', 'amazon-auto-links' ),
					'Author'		=> __( 'Author', 'amazon-auto-links' ),
					'Actor'			=> __( 'Actor', 'amazon-auto-links' ),
					'Composer'		=> __( 'Composer', 'amazon-auto-links' ),
					'Brand'			=> __( 'Brand', 'amazon-auto-links' ),
					'Artist'		=> __( 'Artist', 'amazon-auto-links' ),
					'Conductor'		=> __( 'Conductor', 'amazon-auto-links' ),
					'Director'		=> __( 'Director', 'amazon-auto-links' ),
				),
				'vDefault' => 'Author',
				'strDescription' => __( 'Enter a keyword to narrow down the results with one of the above attributes.', 'amazon-auto-links' )
					. ' ' . __( 'If this is set, the Search Keyword option can be empty.', 'amazon-auto-links' ), 
			), 			
			// array(
				// 'strFieldID' => $strPrefix . 'Author',
				// 'strSectionID' => $strSectionID ? $strSectionID : null,
				// 'strTitle' => __( 'Author', 'amazon-auto-links' ) . ' <span class="description">(' . __( 'optional', 'amazon-auto-links' ) . ')</span>',
				// 'strType' => 'text',
				// 'strDescription' => __( 'Enter keywords to narrow down the results by author.', 'amazon-auto-links' ), 
			// ),						
			array(
				'strFieldID' => $strPrefix . 'Availability',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Availability', 'amazon-auto-links' ),
				'strType' => 'checkbox',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',		
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,
				'vLabel' => __( 'Filter out most of the items that are unavailable as may products can become unavailable quickly.', 'amazon-auto-links' ),
				'vDefault' => 1,
			),		
			array(
				'strFieldID' => $strPrefix . 'Condition',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Condition', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',		
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,				
				'vLabel' => array(
					'New' => __( 'New', 'amazon-auto-links' ),
					'Used' => __( 'Used', 'amazon-auto-links' ),
					'Collectible' => __( 'Collectible', 'amazon-auto-links' ),
					'Refurbished' => __( 'Refurbished', 'amazon-auto-links' ),
					'All' => __( 'All', 'amazon-auto-links' ),
				),
				'vDefault' => 'New',
				'strDescripton' => __( 'If the search index is All, this option does not take effect.' ),
			),
			array(
				'strFieldID' => $strPrefix . 'MaximumPrice',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Maximum Price', 'amazon-auto-links' ) . ' <span class="description">(' . __( 'optional', 'amazon-auto-links' ) . ')</span>',
				'strType' => 'number',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',	
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,				
				'vMin' => 1,
				'strDescription' => __( 'Specifies the maximum price of the items in the response. Prices are in terms of the lowest currency denomination, for example, pennies. For example, 3241 represents $32.41.', 'amazon-auto-links' ),
			),						
			array(
				'strFieldID' => $strPrefix . 'MinimumPrice',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Minimum Price', 'amazon-auto-links' ) . ' <span class="description">(' . __( 'optional', 'amazon-auto-links' ) . ')</span>',
				'strType' => 'number',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',	
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,				
				'vMin' => 1,
				'strDescription' => __( 'Specifies the minimum price of the items to return. Prices are in terms of the lowest currency denomination, for example, pennies, for example, 3241 represents $32.41.', 'amazon-auto-links' ),
			),					
			array(
				'strFieldID' => $strPrefix . 'MinPercentageOff',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Minimum Price', 'amazon-auto-links' ) . ' <span class="description">(' . __( 'optional', 'amazon-auto-links' ) . ')</span>',
				'strType' => 'number',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',		
				'vBeforeInputTag' => $strOpeningTag,
				'vAfterInputTag' => $strClosingTag,
				'vMin' => 1,
				'strDescription' => __( 'Specifies the minimum percentage off for the items to return.', 'amazon-auto-links' ),
			),	
			array(
				'strFieldID' => $strPrefix . 'BrowseNode',
				'strSectionID' => $strSectionID ? $strSectionID : null,
				'strTitle' => __( 'Browse Node ID', 'amazon-auto-links' ) . ' <span class="description">(' . __( 'optional', 'amazon-auto-links' ) . ')</span>',
				'strType' => 'number',
				'vDisable' => $fIsDisabled,
				'vClassAttribute' => $fIsDisabled ? 'disabled' : '',		
				'vBeforeInputTag' => $strOpeningTag,		
				'vAfterInputTag' => $strClosingTag,				
				'vMin' => 1,
				'strDescription' => __( 'If you know the browse node that you are searching, specify it here. It is a positive integer.', 'amazon-auto-links' ),
			),				
		);		
		
	}
	
	protected function getFieldsOfAutoInsert( $strSectionID, $strPrefix ) {
		
		return array(	
			array(
				'strFieldID' => $strPrefix . 'auto_insert',
				'strSectionID' => $strSectionID,
				'strTitle' => __( 'Enable Auto Insert', 'amazon-auto-links' ),
				'strType' => 'radio',
				'vLabel' => array(						
					1		=> __( 'On', 'amazon-auto-links' ),
					0		=> __( 'Off', 'amazon-auto-links' ),
				),
				'strDescription' => __( 'Set it On to insert product links into post and pages automatically. More advanced options can be configured later.', 'amazon-auto-links' ),
				'vDefault' => 1,
			),
		
		);
	}
	
	protected function getFieldsOfTemplate( $strSectionID, $strPrefix ) {
		
		$oForm_Template = new AmazonAutoLinks_Form_Template( $this->strPageSlug );
		return $oForm_Template->getTemplateFields( $strSectionID, $strPrefix, true, 'search' );		
		
		return array(
			array(
				'strFieldID' => $strPrefix . 'template_id',
				'strSectionID' => $strSectionID,
				'strType' => 'select',			
				'strDescription'	=> __( 'Sets a default template for this unit.', 'amazon-auto-links' ),
				'vLabel'			=> $GLOBALS['oAmazonAutoLinks_Templates']->getTemplateArrayForSelectLabel(),
				'strType'			=> 'select',
				'vDefault'			=> $GLOBALS['oAmazonAutoLinks_Templates']->getPluginDefaultTemplateID( 'search' ),	// // defined in the 'unit_type' field
			),		
			array(  // single button
				'strFieldID' => $strPrefix . 'submit_second_options',
				'strSectionID' => $strSectionID,
				'strType' => 'submit',
				'strBeforeField' => "<div style='display: inline-block;'>" . $this->oUserAds->getTextAd() . "</div>"
					. "<div class='right-button'>",
				'strAfterField' => "</div>",
				'vLabelMinWidth' => 0,
				'vLabel' => __( 'Create', 'amazon-auto-links' ),
				'vClassAttribute' => 'button button-primary',
				'strAfterField' => "<input type='hidden' name='amazon_auto_links_admin[aal_add_search_unit][{$strSectionID}][{$strPrefix}unit_type]' value='search'>",				

			)	
		);
		
	}
	// 
	
}