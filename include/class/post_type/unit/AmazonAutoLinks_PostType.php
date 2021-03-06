<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno
 * 
 */

/**
 * Creates Amazon Auto Links custom post type.
 * 
 * @package     Amazon Auto Links
 * @since       2.0.0
 * 
 * @filter      apply       aal_filter_admin_menu_name
 */
class AmazonAutoLinks_PostType extends AmazonAutoLinks_PostType_PostContent {
    
    public function setUp() {
        
        $_oOption = AmazonAutoLinks_Option::getInstance();
        $this->setArguments(
            array(            // argument - for the array structure, refer to http://codex.wordpress.org/Function_Reference/register_post_type#Arguments
                'labels' => array(
                    'name'                  => AmazonAutoLinks_Registry::NAME,
                    'menu_name'             => apply_filters(
                        'aal_filter_admin_menu_name',
                        AmazonAutoLinks_Registry::NAME
                    ),
                    'all_items'             => __( 'Manage Units', 'amazon-auto-links' ),    // sub menu label
                    'singular_name'         => __( 'Amazon Auto Links Unit', 'amazon-auto-links' ),
                    'add_new'               => __( 'Add Unit by Category', 'amazon-auto-links' ),
                    'add_new_item'          => __( 'Add New Unit', 'amazon-auto-links' ),
                    'edit'                  => __( 'Edit', 'amazon-auto-links' ),
                    'edit_item'             => __( 'Edit Unit', 'amazon-auto-links' ),
                    'new_item'              => __( 'New Unit', 'amazon-auto-links' ),
                    'view'                  => __( 'View', 'amazon-auto-links' ),
                    'view_item'             => __( 'View Product Links', 'amazon-auto-links' ),
                    'search_items'          => __( 'Search Units', 'amazon-auto-links' ),
                    'not_found'             => __( 'No unit found for Amazon Auto Links', 'amazon-auto-links' ),
                    'not_found_in_trash'    => __( 'No Unit Found for Amazon Auto Links in Trash', 'amazon-auto-links' ),
                    'parent'                => 'Parent Unit',
                    
                    // framework specific keys
                    'plugin_listing_table_title_cell_link' => __( 'Manage Units', 'amazon-auto-links' ),
                ),
                
                // If a custom preview post type is set, make it not public. 
                // However, other ui arguments should be enabled.
                'public'                => ! $_oOption->isCustomPreviewPostTypeSet(),
                'publicly_queryable'    => ! $_oOption->isCustomPreviewPostTypeSet()
                    && $_oOption->isPreviewVisible(),
                'has_archive'           => true,
                'show_ui'               => true,
                'show_in_nav_menus'     => true,
                'show_in_menu'          => true,

                'menu_position'         => 110,
                'supports'              => array( 'title' ),
                'taxonomies'            => array( '' ),
                'menu_icon'             => $this->oProp->bIsAdmin
                    ? AmazonAutoLinks_Registry::getPluginURL( 'asset/image/menu_icon_16x16.png' )
                    : null,
                'hierarchical'          => false,
                'show_admin_column'     => true,
                'can_export'            => $_oOption->canExport(),
                'exclude_from_search'   => ! $_oOption->get( 'unit_preview', 'searchable' ),
                
            )        
        );
        
        $this->addTaxonomy( 
            AmazonAutoLinks_Registry::$aTaxonomies[ 'tag' ], 
            array(
                'labels'                => array(
                    'name'          => __( 'Label', 'amazon-auto-links' ),
                    'add_new_item'  => __( 'Add New Label', 'amazon-auto-links' ),
                    'new_item_name' => __( 'New Label', 'amazon-auto-links' ),
                ),
                'show_ui'               => true,
                'show_tagcloud'         => false,
                'hierarchical'          => false,
                'show_admin_column'     => true,
                'show_in_nav_menus'     => false,
                'show_table_filter'     => true,  // framework specific key
                'show_in_sidebar_menus' => true,  // framework specific key
            )
        );
        
        if (  $this->_isInThePage() ) {
            
            $this->setAutoSave( false );
            $this->setAuthorTableFilter( false );            
            add_filter( 'months_dropdown_results', '__return_empty_array' );
            
            add_filter( 'enter_title_here', array( $this, 'replyToModifyTitleMetaBoxFieldLabel' ) );    
            add_action( 'edit_form_after_title', array( $this, 'replyToAddTextAfterTitle' ) );    
                
            $this->enqueueStyles(
                AmazonAutoLinks_Registry::$sDirPath . '/asset/css/admin.css'
            );
         
            // unit listing table columns
            add_filter(    
                'columns_' . AmazonAutoLinks_Registry::$aPostTypes[ 'unit' ],
                array( $this, 'replyToModifyColumnHeader' )
            );
         
        }
                    
    }
        
    /**
     * @callback        filter      `enter_title_here`
     */
    public function replyToModifyTitleMetaBoxFieldLabel( $strText ) {
        return __( 'Set the unit name here.', 'amazon-auto-links' );        
    }
    /**
     * @callback        action       `edit_form_after_title`
     */
    public function replyToAddTextAfterTitle() {
        //@todo insert plugin news text headline.
    }
        
    /**
     * Style for this custom post type pages
     * @callback        filter      style_{class name}
     */
    public function style_AmazonAutoLinks_PostType() {
        $_sNone = 'none';
        return "#post-body-content {
                margin-bottom: 10px;
            }
            #edit-slug-box {
                display: {$_sNone};
            }
            #icon-edit.icon32.icon32-posts-" . AmazonAutoLinks_Registry::$aPostTypes[ 'unit' ] . " {
                background:url('" . AmazonAutoLinks_Registry::getPluginURL( "asset/image/screen_icon_32x32.png" ) . "') no-repeat;
            }            
            /* Hide the submit button for the post type drop-down filter */
            #post-query-submit {
                display: {$_sNone};
            }            
            /* List Table Columns */
            .column-unit_type, 
            .column-template,
            .column-feed {
                width:10%; 
            }
            /* Feed column */
            .column-feed { 
                text-align: center 
            }
            .feed-icon {
                display: inline-block;
                margin-top: 0.4em;
                margin-right: 0.8em;
            }
        ";
    }
}

