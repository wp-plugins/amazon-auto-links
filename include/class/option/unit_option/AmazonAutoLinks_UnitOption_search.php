<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazn-auto-links/
 * Copyright (c) 2013-2015 Michael Uno
 * 
 */

/**
 * Handles search unit options.
 * 
 * @since       3

 */
class AmazonAutoLinks_UnitOption_search extends AmazonAutoLinks_UnitOption_Base {

    /**
     * Stores the default structure and key-values of the unit.
     * @remark      Accessed from the base class constructor to construct a default option array.
     */
    public static $aStructure_Default = array(

        'additional_attribute'  => null,
        'search_by'             => 'Author',
        'description_length'    => 250,
        
        // 'nodes' => 0,    // 0 is for all nodes.    Comma delimited strings will be passed. e.g. 12345,12425,5353
        
        // These are used for API parameters as well
        'Keywords'              => null,      // the keyword to search
        'Power'                 => null,        // @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/PowerSearchSyntax.html
        'Operation'             => 'ItemSearch',    // ItemSearch, ItemLookup, SimilarityLookup
        'Title'                 => '',      // for the advanced Title option
        'Sort'                  => 'salesrank',        // pricerank, inversepricerank, sales_rank, relevancerank, reviewrank
        'SearchIndex'           => 'All',        
        'BrowseNode'            => '',    // ( optional )
        'Availability'          => 'Available',    // ( optional ) 
        'Condition'             => 'New',    
        'MaximumPrice'          => null,
        'MinimumPrice'          => null,
        'MinPercentageOff'      => null,
        'MerchantId'            => null,    // 2.0.7+
        'MarketplaceDomain'     => null,    // 2.1.0+
        'ItemPage'              => null,        

    );


}