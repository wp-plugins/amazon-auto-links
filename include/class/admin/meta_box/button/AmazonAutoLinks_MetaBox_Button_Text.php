<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno
 * 
 */

/**
 * Defines the meta box for the button post type.
 */
class AmazonAutoLinks_MetaBox_Button_Text extends AmazonAutoLinks_MetaBox_Button {

    public function setUp() {        
    
        $this->addSettingFields(
            array(
                'field_id'      => 'button_label',
                'type'          => 'text',
                'title'         => __( 'Button Label', 'amazon-auto-links' ),
                'default'       => __( 'Buy Now', 'amazon-auto-links' ),
                'attributes'    => array(
                    'data-property' => 'text',
                ),                
            ),                    
            array(
                'field_id'      => 'font_color',
                'type'          => 'color',
                'title'         => __( 'Font Color', 'amazon-auto-links' ),
                'default'       => '#ffffff',
                'attributes'    => array(
                    'data-property' => 'color',
                ),                
            ),
            array(
                'field_id'      => 'font_size',
                'type'          => 'number',
                'title'         => __( 'Font Size', 'amazon-auto-links' ),
                'attributes'    => array(
                    'min'           => 0,
                    'data-property' => 'font-size',
                ),                
                'default'       => 13,
            ),
            
            array(
                'field_id'      => 'text_shadow_switch',
                'type'          => 'revealer',
                'select_type'   => 'radio',
                'title'         => __( 'Text Shadow Switch', 'amazon-auto-links' ),
                'label'         => array(
                    '.text_shadow_on' => __( 'On', 'amazon-auto-links' ),
                    '.text_shadow_off' => __( 'Off', 'amazon-auto-links' ),
                ),
                'attributes'    => array(
                    '.text_shadow_on'  => array(
                        'data-switch' => '.text_shadow_off',
                    ),
                    '.text_shadow_off' => array(
                        'data-switch' => '.text_shadow_on',
                    )
                ),
                'default'       => '.text_shadow_off',
            ),       
            array(
                'field_id'      => 'text_shadow_color',
                'type'          => 'color',
                'title'         => __( 'Text Shadow', 'amazon-auto-links' ),
                'class'         => array(
                    'fieldrow'  => 'text_shadow_on',
                ),
                'attributes'    => array(
                    'data-property' => 'text-shadow-color',
                ),
            )
        );
                
    }
    
    
}