<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno
 * 
 */


/**
 * Deals with the plugin admin pages.
 * 
 * @since       3
 */
class AmazonAutoLinks_SearchUnitAdminPage extends AmazonAutoLinks_SimpleWizardAdminPage {
          
    /**
     * Sets the default option values for the setting form.
     * @callback    filter      options_{class name}
     * @return      array       The options array.
     */
    public function setOptions( $aOptions ) {

        // $_sTransientID     = isset( $_GET[ 'transient_id' ] )
            // ? $_GET[ 'transient_id' ]
            // : '';
        // $_aTempUnitOptions = $_sTransientID
            // ? ( array ) AmazonAutoLinks_WPUtility::getTransient( 
                // 'AAL_CreateUnit_' . $_sTransientID
            // )
            // : array();
        // return $aOptions + $_aTempUnitOptions;
// @todo examine whether it is possibe to merge with unit options of the choosen search type.
        return $aOptions;
        
// return $aOptions + AmazonAutoLinks_UnitOption_tag::$aStructure_Default;

    }

    /**
     * Sets up admin pages.
     */
    public function setUp() {
        
        // Page group root.
        $this->setRootMenuPageBySlug( 
            'edit.php?post_type=' . AmazonAutoLinks_Registry::$aPostTypes[ 'unit' ]
        );
                    
        // Add pages
        new AmazonAutoLinks_SearchUnitAdminPage_SearchUnit( 
            $this,
            array(
                'page_slug'     => AmazonAutoLinks_Registry::$aAdminPages[ 'search_unit' ],
                'title'         => __( 'Add Unit by Search', 'amazon-auto-links' ),
                'screen_icon'   => AmazonAutoLinks_Registry::getPluginURL( "asset/image/screen_icon_32x32.png" ),
            )
        );        
        
    }
    /**
     * Registers custom filed types of Admin Page Framework.
     */
    public function registerFieldTypes() {}
    
    /**
     * Page styling
     * @since       3
     * @return      void
     */
    public function doPageSettings() {

        $this->setPageTitleVisibility( false ); // disable the page title of a specific page.
        $this->setInPageTabTag( 'h2' );                
        $this->setPluginSettingsLinkLabel( '' ); // pass an empty string to disable it.
        $this->enqueueStyle( AmazonAutoLinks_Registry::getPluginURL( 'asset/css/admin.css' ) );
        
                    
    }
        
}