<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno; Licensed GPLv2
 */

/**
 * Adds the 'Auto Insert' form section to the 'Add Unit by Category' tab.
 * 
 * @since       3
 */
class AmazonAutoLinks_CategoryUnitAdminPage_CategorySelect_First_AutoInsert extends AmazonAutoLinks_AdminPage_Section_Base {
    
    /**
     * A user constructor.
     * 
     * @since       3
     * @return      void
     */
    protected function construct( $oFactory ) {}
    
    /**
     * Adds form fields.
     * @since       3
     * @return      void
     */
    public function addFields( $oFactory, $sSectionID ) {
        
        $_oFields = new AmazonAutoLinks_FormFields_Unit_AutoInsert;
        foreach( $_oFields->get() as $_aField ) {
            $oFactory->addSettingFields(
                $sSectionID, // the target section id    
                $_aField
            );
        }
        
        $_oFields = new AmazonAutoLinks_FormFields_CategoryUnit_ProceedButton;
        $_aFields = $_oFields->get();
        foreach( $_aFields as $_aField ) {
            $oFactory->addSettingFields(
                $sSectionID, // the target section id
                $_aField
            );
        }        
        
    }

}