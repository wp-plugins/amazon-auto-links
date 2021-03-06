<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno
 * 
 */

/**
 * Provides shared methods for the category select form.
 * 
 */
abstract class AmazonAutoLinks_Form_CategorySelect_Base extends AmazonAutoLinks_WPUtility {

    /**
     * Sets up basic properties.
     */
    public function __construct() {
        
        $this->sCharEncoding = get_bloginfo( 'charset' ); 
        $this->oEncrypt      = new AmazonAutoLinks_Encrypt;
        $this->oDOM          = new AmazonAutoLinks_DOM;        
        
        $_aParams = func_get_args();
        call_user_func_array(
            array( $this, 'construct' ),
            $_aParams
        );
    }
    
    /**
     * User constructor.
     */
    public function construct() {}
    

    /**
     * Checks whether the category item limit is reached.
     * 
     */
    protected function isNumberOfCategoryReachedLimit( $iNumberOfCategories ) {
        $_oOption = AmazonAutoLinks_Option::getInstance();
        return ( boolean ) $_oOption->isReachedCategoryLimit( 
            $iNumberOfCategories
        );            
    }   
    
}
        