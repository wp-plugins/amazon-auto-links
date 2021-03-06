<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno; Licensed GPLv2
 */

/**
 * Adds the 'Capability' form section to the 'Misc' tab.
 * 
 * @since       3
 */
class AmazonAutoLinks_AdminPage_Setting_Reset_RestSettings extends AmazonAutoLinks_AdminPage_Section_Base {
    
    /**
     * A user constructor.
     * 
     * @since       3
     * @return      void
     */
    protected function construct( $oFactory ) {
             
        // reset_{instantiated class name}_{section id}_{field id}
        add_action( 
            "reset_{$oFactory->oProp->sClassName}_{$this->sSectionID}_all",
            array( $this, 'replyToResetOptions' ), 
            10, // priority
            4 // number of parameters
        );
        
    }
    
    /**
     * Adds form fields.
     * @since       3
     * @return      void
     */
    public function addFields( $oFactory, $sSectionID ) {

       $oFactory->addSettingFields(
            $sSectionID, // the target section id
            array( 
                'field_id'          => 'all',
                'title'             => __( 'Restore Default', 'amazon-auto-links' ),
                'type'              => 'submit',
                'reset'             => true,
                'value'             => __( 'Restore', 'amazon-auto-links' ),
                'description'       => __( 'Restore the default options.', 'amazon-auto-links' ),
                'attributes'        => array(
                    'size'          => 30,
                    // 'required' => 'required',
                ),
            ),
            array(
                'field_id'          => 'reset_on_uninstall',
                'title'             => __( 'Delete Options upon Uninstall', 'amazon-auto-links' ),
                'type'              => 'checkbox',
                'label'             => __( 'Delete options and caches when the plugin is uninstalled.', 'amazon-auto-links' ),
            )           
        );          
            
    
    
    }
        
    public function replyToResetOptions( $asKeyToReset, $aInput, $oFactory, $aSubmitInfo ) {
        
        // Delete the template options as well.
        delete_option(
            AmazonAutoLinks_Registry::$aOptionKeys[ 'template' ]
        );
        
        // Button options
        delete_option(
            AmazonAutoLinks_Registry::$aOptionKeys[ 'button_css' ]
        );
    
    }
    
   
}