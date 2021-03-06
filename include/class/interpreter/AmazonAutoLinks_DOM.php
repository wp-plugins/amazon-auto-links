<?php
/**
 * Amazon Auto Links
 * 
 * http://en.michaeluno.jp/amazon-auto-links/
 * Copyright (c) 2013-2015 Michael Uno
 * 
 */

/**
 * Provides Dom related functions.
 * 
 * @package     Amazon Auto Links
 * @since       2.0.0
 * @since       3       Extends `AmazonAutoLinks_WPUtility`.
 */
final class AmazonAutoLinks_DOM extends AmazonAutoLinks_WPUtility {
    
    /**
     * Sets up properties.
     */
    function __construct() {
        
        $this->sCharEncoding    = get_bloginfo( 'charset' ); 
        $this->oEncrypt         = new AmazonAutoLinks_Encrypt;
        $this->sHTMLCachePrefix = AmazonAutoLinks_Registry::TRANSIENT_PREFIX . "_HTML_";
            
        $this->bIsMBStringInstalled = function_exists( 'mb_language' );
    }
    
    /**
     * Creates a DOM object from a given HTML string.
     * 
     * @return      object      DOM object
     */
    public function loadDOMFromHTMLElement( $sHTMLElements, $sMBLang='uni', $sSourceCharSet='' ) {
                
        return $this->loadDOMFromHTML( 
            // Enclosing in a div tag prevents from inserting the comment <!-- xml version .... --> when using saveXML() later.
            '<div>' 
                . $sHTMLElements 
            . '</div>', 
            $sMBLang,
            $sSourceCharSet 
        );
        
    }    
    /**
     * Creates a DOM object from a given url.
     * @return      object      DOM object
     */
    public function loadDOMFromURL( $sURL, $sMBLang='uni', $bUseFileGetContents=false, $sSourceCharSet='' ) {
            
        return $this->loadDOMFromHTML( 
            $this->getHTML( 
                $sURL, 
                $bUseFileGetContents 
            ), 
            $sMBLang,
            $sSourceCharSet
        );

    }    
    /**
     * 
     * @param       string          $sHTML     
     * @param       string          $sMBLang     
     * @param       string  $sSourceCharSet     If true, it auto-detects the character set. If a string is given, 
     * the HTML string will be converted to the given character set. If false, the HTML string is treated as it is.
     */
    public function loadDOMFromHTML( $sHTML, $sMBLang='uni', $sSourceCharSet='' ) {
        
        // without this, the characters get broken    
        if ( ! empty( $sMBLang ) && $this->bIsMBStringInstalled ) {
            mb_language( $sMBLang ); 
        }
       
        if ( false !== $sSourceCharSet ) {
            $sHTML       = $this->convertCharacterEncoding( 
                $sHTML, // subject
                $this->sCharEncoding, // to
                $sSourceCharSet, // from
                false   // no html entities conversion
            );           
        }

        // @todo    Examine whether the below line takes effect or not.
        // mb_internal_encoding( $this->sCharEncoding );                     
        
        $oDOM                     = new DOMDocument( 
            '1.0', 
            $this->sCharEncoding
        );
        $oDOM->recover            = true;    // @see http://stackoverflow.com/a/7386650, http://stackoverflow.com/a/9281963
        // $oDOM->sictErrorChecking = false; // @todo examine whether this is necessary or not. 
        $oDOM->preserveWhiteSpace = false;
        $oDOM->formatOutput       = true;
        @$oDOM->loadHTML( 
            function_exists( 'mb_convert_encoding' )
                ? mb_convert_encoding( $sHTML, 'HTML-ENTITIES', $this->sCharEncoding )
                : $sHTML
        );    
        return $oDOM;
        
    }
    
    /**
     * 
     * @return      string
     */
    public function getInnerHTML( $oNode ) {
        $sInnerHTML  = ""; 
        if ( ! $oNode ) {
            return $sInnerHTML;
        }
        $oChildNodes = $oNode->childNodes; 
        foreach ( $oChildNodes as $oChildNode ) { 
            $oTempDom    = new DOMDocument( '1.0', $this->sCharEncoding );
            
            $_oImportedNode = $oTempDom->importNode( 
                $oChildNode, 
                true 
            );
            if ( $_oImportedNode ) {
                $oTempDom->appendChild( 
                    $_oImportedNode    
                ); 
            }
            
            $sInnerHTML .= trim( @$oTempDom->saveHTML() ); 
        } 
        return $sInnerHTML;     
        
    }

    /**
     * Fetches HTML body with the specified URL with caching functionality.
     * 
     * @return      string
     */
    public function getHTML( $sURL, $bUseFileGetContents=false ) {
    
        if ( $bUseFileGetContents ) {
            $_oHTML = new AmazonAutoLinks_HTTPClient_FileGetContents( $sURL );
            return $_oHTML->get();
        }
        $_oHTML = new AmazonAutoLinks_HTTPClient( $sURL );
        return $_oHTML->get();    
    
    }
    
    /**
     * Deletes the cache of the provided URL.
     */
    public function deleteCache( $sURL ) {
// @todo delete the item of the custom database table.        
// or deprecate this method.
        $this->deleteTransient( $this->sHTMLCachePrefix . md5( $sURL ) );
    }
    
    /**
     * Modifies the attributes of the given node elements by specifying a tag name.
     * 
     * Example:
     * `
     * $oDom->setAttributesByTagName( $oNode, 'a', array( 'target' => '_blank', 'rel' => 'nofollow' ) );
     * `
     */
    public function setAttributesByTagName( $oNode, $sTagName, $aAttributes=array() ) {
        
        foreach( $oNode->getElementsByTagName( $sTagName ) as $_oSelectedNode ) {
            foreach( $this->getAsArray( $aAttributes ) as $_sAttribute => $_sProperty ) {
                if ( in_array( $_sAttribute, array( 'src', 'href' ) ) ) {
                    $_sProperty = esc_url( $_sProperty );
                }
                @$_oSelectedNode->setAttribute( 
                    $_sAttribute, 
                    esc_attr( $_sProperty )
                );
            }
        }
            
    }

    /**
     * Removes nodes by tag and class selector. 
     * 
     * Example:
     * `
     * $this->oDOM->removeNodeByTagAndClass( $nodeDiv, 'span', 'riRssTitle' );
     * `
     */
    public function removeNodeByTagAndClass( $oNode, $sTagName, $sClassName, $iIndex='' ) {
        
        $oNodes = $oNode->getElementsByTagName( $sTagName );
        
        // If the index is specified,
        if ( 0 === $iIndex || is_integer( $iIndex ) ) {
            $oTagNode = $oNodes->item( $iIndex );
            if ( $oTagNode ) {
                if ( stripos( $oTagNode->getAttribute( 'class' ), $sClassName ) !== false ) {
                    $oTagNode->parentNode->removeChild( $oTagNode );
                }
            }
        }
        
        // Otherwise, remove all - Dom is a live object so iterate backwards
        for ( $i = $oNodes->length - 1; $i >= 0; $i-- ) {
            $oTagNode = $oNodes->item( $i );
            if ( stripos( $oTagNode->getAttribute( 'class' ), $sClassName ) !== false ) {
                $oTagNode->parentNode->removeChild( $oTagNode );
            }
        }
        
    }                
    
}