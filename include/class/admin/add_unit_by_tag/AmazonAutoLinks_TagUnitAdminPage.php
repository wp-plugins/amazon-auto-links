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
class AmazonAutoLinks_TagUnitAdminPage extends AmazonAutoLinks_SimpleWizardAdminPage {
          
    /**
     * Sets the default option values for the setting form.
     * @callback    filter      options_{class name}
     * @return      array       The options array.
     */
    public function setOptions( $aOptions ) {
        return $aOptions + AmazonAutoLinks_UnitOption_tag::$aStructure_Default;
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
        new AmazonAutoLinks_TagUnitAdminPage_TagUnit( 
            $this,
            array(
                'page_slug'     => AmazonAutoLinks_Registry::$aAdminPages[ 'tag_unit' ],
                'title'         => __( 'Add Unit by Tag', 'amazon-auto-links' ),
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
                    
        $this->setPageTitleVisibility( true ); // disable the page title of a specific page.   
        $this->enqueueStyle( AmazonAutoLinks_Registry::getPluginURL( 'asset/css/admin.css' ) );
                    
    }
        
}