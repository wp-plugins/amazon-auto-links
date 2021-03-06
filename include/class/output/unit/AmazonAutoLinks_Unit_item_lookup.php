<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno
 * 
 */

/**
 * Creates Amazon product links by Item Look-up.
 * 
 * @package         Amazon Auto Links
 */
class AmazonAutoLinks_Unit_item_lookup extends AmazonAutoLinks_Unit_search {
    
    /**
     * Stores the unit type.
     * @remark      Note that the base constructor will create a unit option object based on this value.
     */    
    public $sUnitType = 'item_lookup';
    
    /**
     * Represents the array structure of the API request arguments.
     * @since            2.0.2
     */
    public static $aStructure_APIParameters = array(    
        'Operation'             => 'ItemLookup',
        'Condition'             => 'New',
        'IdType'                => null,
        'IncludeReviewsSummary' => null,
        'ItemId'                => null,
        'MerchantId'            => null,
        'RelatedItemPage'       => null,
        'RelationshipType'      => null,
        'SearchIndex'           => null,
        'TruncateReviewsAt'     => null,
        'VariationPage'         => null,
        'ResponseGroup'         => null,
    );

    /**
     * Performs an Amazon Product API request.
     * 
     * @since            2.0.2
     */
    protected function getRequest( $iCount ) {
        
        $_oAPI = new AmazonAutoLinks_ProductAdvertisingAPI( 
            $this->oUnitOption->get( 'country' ), 
            $this->oOption->get( 'authentication_keys', 'access_key' ),
            $this->oOption->get( 'authentication_keys', 'access_key_secret' ),
            $this->oUnitOption->get( 'associate_id' )
        );
            
        // Perform the search for the first page regardless the specified count (number of items).
        // Keys with an empty value will be filtered out when performing the request.
        return $_oAPI->request( 
            $this->getAPIParameterArray( 
                $this->oUnitOption->get( 'Operation' ) 
            ), 
            $this->oUnitOption->get( 'country' ),   // locale
            $this->oUnitOption->get( 'cache_duration' )
        );
                 
    }
    
    
    /**
     * 
     * 'Operation' => 'ItemSearch',    // ItemSearch, ItemLookup, SimilarityLookup
     * @see              http://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemLookup.html
     * @since            2.0.2
     */
    protected function getAPIParameterArray( $sOperation='ItemLookup', $iItemPage=null ) {

        // $this->arrArgs = $this->arrArgs + self::$aStructure_ItemLookup;
        $_aUnitOptions = $this->oUnitOption->get()
            + self::$aStructure_APIParameters;        
        $aParams = array(
            'Operation'             => $sOperation,
            'Condition'             => $_aUnitOptions['Condition'],    // (optional) Used | Collectible | Refurbished, All
            'IdType'                => $_aUnitOptions['IdType'],    // (optional) All IdTypes except ASINx require a SearchIndex to be specified.  SKU | UPC | EAN | ISBN (US only, when search index is Books). UPC is not valid in the CA locale.
            'IncludeReviewsSummary' => "True",        // (optional)
            'ItemId'                => $_aUnitOptions['ItemId'],    // (required)  If ItemIdis an ASIN, a SearchIndex cannot be specified in the request.
            // 'RelatedItemPage' => null,    // (optional) This optional parameter is only valid when the RelatedItems response group is used.
            // 'RelationshipType' => null,    // (conditional)    This parameter is required when the RelatedItems response group is used. 
            'SearchIndex'           => $_aUnitOptions['SearchIndex'],    // (conditional) see: http://docs.aws.amazon.com/AWSECommerceService/latest/DG/APPNDX_SearchIndexValues.html
            // 'TruncateReviewsAt' => 1000, // (optional)
            // 'VariationPage' => null, // (optional)
            'ResponseGroup'         => 'Large', // (optional)
        );
        
        if ( 'ASIN' === $_aUnitOptions['IdType'] ) {
            unset( $aParams['SearchIndex'] );
        }

        $_aAPIParameters = 'Amazon' === $_aUnitOptions['MerchantId']
            ? $aParams + array( 'MerchantId' => $_aUnitOptions['MerchantId'] )    // (optional) 'Amazon' restrict the returned items only to be soled by Amazon.
            : $aParams;
        return $_aAPIParameters;
        
    }
    
}