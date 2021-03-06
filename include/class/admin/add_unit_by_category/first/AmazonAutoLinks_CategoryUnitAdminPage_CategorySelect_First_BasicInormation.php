<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno; Licensed GPLv2
 */

/**
 * Adds the 'Category' form section to the 'Add Unit by Category' tab.
 * 
 * @since       3
 */
class AmazonAutoLinks_CategoryUnitAdminPage_CategorySelect_First_BasicInormation extends AmazonAutoLinks_AdminPage_Section_Base {
    
    /**
     * A user constructor.
     * 
     * @since       3
     * @return      void
     */
    protected function construct( $oFactory ) {
        
        add_filter(
            "validation_{$this->sPageSlug}_{$this->sTabSlug}",
            array( $this, 'validateTabForm' ),
            5,  // higher priority
            4   // number of prameters
        );    
    }
    
    /**
     * Adds form fields.
     * @since       3
     * @return      void
     */
    public function addFields( $oFactory, $sSectionID ) {
        
        $_oFieldsBasicInformation = new AmazonAutoLinks_FormFields_CategoryUnit_BasicInformation;
        $_aFields                 = array_merge(
            array(
                array(
                    'field_id'      => 'unit_title',
                    'title'         => __( 'Unit Name', 'amazon-auto-links' ),
                    'type'          => 'text',
                    'description'   => 'e.g. <code>My Unit</code>',
                    // the previous value should not appear
                    'value'         => isset( $_GET[ 'trnsient_id' ] )
                        ? ''
                        : null,
                ),                       
            ),
            $_oFieldsBasicInformation->get()
        );
        
        foreach( $_aFields as $_aField ) {
            $oFactory->addSettingFields(
                $sSectionID, // the target section id    
                $_aField
            );
        }
        
    }
        
    
    /**
     * Validates the submitted form data.
     * 
     * @since       3
     */
    public function validateTabForm( $aInput, $aOldInput, $oAdminPage, $aSubmitInfo ) {
    
        $_bVerified = true;
        $_aErrors   = array();

        $aInput[ 'associate_id' ] = trim( $aInput[ 'associate_id' ] );
        if ( empty( $aInput[ 'associate_id' ] ) ) {
            
            $_aErrors[ 'associate_id' ] = __( 'The associate ID cannot be empty.', 'amazon-auto-links' );
            $_bVerified = false;
            
        }
            
        // Evacuate some extra field items.
        $_aInputTemp = $aInput;
            
        // Format the unit options - this will also drop unnecessary keys for units.
        $_oUnitOptions = new AmazonAutoLinks_UnitOption_category(
            null,   // unit id
            $aInput
        );
        $aInput = $_oUnitOptions->get() + $_aInputTemp;
        
        // An invalid value is found. Set a field error array and an admin notice and return the old values.
        if ( ! $_bVerified ) {
            $oAdminPage->setFieldErrors( $_aErrors );     
            $oAdminPage->setSettingNotice( __( 'There was something wrong with your input.', 'amazon-auto-links' ) );
            return $aInput;
        }

        return $aInput;     
        
    }   
    
}