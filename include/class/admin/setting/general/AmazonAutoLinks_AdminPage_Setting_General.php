<?php
/**
 * Amazon Auto Links
 * 
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno; Licensed GPLv2
 * 
 */

/**
 * Adds the 'General' tab to the 'Settings' page of the loader plugin.
 * 
 * @since       3
 * @extends     AmazonAutoLinks_AdminPage_Tab_Base
 */
class AmazonAutoLinks_AdminPage_Setting_General extends AmazonAutoLinks_AdminPage_Tab_Base {
    
    /**
     * Triggered when the tab is loaded.
     */
    public function replyToLoadTab( $oAdminPage ) {
        
        // Form sections
        new AmazonAutoLinks_AdminPage_Setting_General_ProductFilters( 
            $oAdminPage,
            $this->sPageSlug, 
            array(
                'section_id'    => 'product_filters',
                'tab_slug'      => $this->sTabSlug,
                'title'         => __( 'Global Product Filters', 'amazon-auto-links' ),
                'description'   => array(
                    __( 'Set the criteria to filter fetched items.', 'amazon-auto-links' ),
                ),
            )
        );
        new AmazonAutoLinks_AdminPage_Setting_General_UnitPreview( 
            $oAdminPage,
            $this->sPageSlug, 
            array(
                'section_id'    => 'unit_preview',
                'tab_slug'      => $this->sTabSlug,
                'title'         => __( 'Unit Preview', 'amazon-auto-links' ),
            )
        );        
        new AmazonAutoLinks_AdminPage_Setting_General_CustomQueryKey( 
            $oAdminPage,
            $this->sPageSlug, 
            array(
                'section_id'    => 'query',
                'tab_slug'      => $this->sTabSlug,
                'title'         => __( 'Custom Query Key', 'amazon-auto-links' ),
            )
        );         
        new AmazonAutoLinks_AdminPage_Setting_General_ExternalScript( 
            $oAdminPage,
            $this->sPageSlug, 
            array(
                'section_id'    => 'external_scripts',
                'tab_slug'      => $this->sTabSlug,
                'title'         => __( 'External Scripts', 'amazon-auto-links' ),
            )
        );              
     
    }
    
    public function replyToDoTab( $oFactory ) {
        echo "<div class='right-submit-button'>"
                . get_submit_button()  
            . "</div>";
    }
            
}
