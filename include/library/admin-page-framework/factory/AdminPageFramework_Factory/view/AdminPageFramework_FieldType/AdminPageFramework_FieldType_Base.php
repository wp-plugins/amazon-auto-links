<?php
/**
 Admin Page Framework v3.5.11b02 by Michael Uno
 Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
 <http://en.michaeluno.jp/admin-page-framework>
 Copyright (c) 2013-2015, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT>
 */
abstract class AmazonAutoLinks_AdminPageFramework_FieldType_Base extends AmazonAutoLinks_AdminPageFramework_WPUtility {
    public $_sFieldSetType = '';
    public $aFieldTypeSlugs = array('default');
    protected $aDefaultKeys = array();
    protected static $_aDefaultKeys = array('value' => null, 'default' => null, 'repeatable' => false, 'sortable' => false, 'label' => '', 'delimiter' => '', 'before_input' => '', 'after_input' => '', 'before_label' => null, 'after_label' => null, 'before_field' => null, 'after_field' => null, 'label_min_width' => 140, 'before_fieldset' => null, 'after_fieldset' => null, 'field_id' => null, 'page_slug' => null, 'section_id' => null, 'before_fields' => null, 'after_fields' => null, 'attributes' => array('disabled' => null, 'class' => '', 'fieldrow' => array(), 'fieldset' => array(), 'fields' => array(), 'field' => array(),),);
    protected $oMsg;
    function __construct($asClassName = 'admin_page_framework', $asFieldTypeSlug = null, $oMsg = null, $bAutoRegister = true) {
        $this->aFieldTypeSlugs = empty($asFieldTypeSlug) ? $this->aFieldTypeSlugs : ( array )$asFieldTypeSlug;
        $this->oMsg = $oMsg ? $oMsg : AmazonAutoLinks_AdminPageFramework_Message::getInstance();
        if ($bAutoRegister) {
            foreach (( array )$asClassName as $_sClassName) {
                add_filter("field_types_{$_sClassName}", array($this, '_replyToRegisterInputFieldType'));
            }
        }
        $this->construct();
    }
    protected function construct() {
    }
    protected function isTinyMCESupported() {
        return version_compare($GLOBALS['wp_version'], '3.3', '>=') && function_exists('wp_editor');
    }
    protected function getElementByLabel($asElement, $sKey, $bIsLabelArray) {
        return $bIsLabelArray ? $this->getElement($asElement, array($sKey), $asElement) : $asElement;
    }
    protected function geFieldOutput(array $aField) {
        if (!is_object($aField['_caller_object'])) {
            return '';
        }
        $aField['_nested_depth']++;
        $_oCaller = $aField['_caller_object'];
        $_aOptions = $_oCaller->getSavedOptions();
        $_oField = new AmazonAutoLinks_AdminPageFramework_FormField($aField, $_aOptions, $_oCaller->getFieldErrors(), $_oCaller->oProp->aFieldTypeDefinitions, $_oCaller->oMsg, $_oCaller->oProp->aFieldCallbacks);
        return $_oField->_getFieldOutput();
    }
    public function _replyToRegisterInputFieldType($aFieldDefinitions) {
        foreach ($this->aFieldTypeSlugs as $sFieldTypeSlug) {
            $aFieldDefinitions[$sFieldTypeSlug] = $this->getDefinitionArray($sFieldTypeSlug);
        }
        return $aFieldDefinitions;
    }
    public function getDefinitionArray($sFieldTypeSlug = '') {
        $_aDefaultKeys = $this->aDefaultKeys + self::$_aDefaultKeys;
        $_aDefaultKeys['attributes'] = isset($this->aDefaultKeys['attributes']) && is_array($this->aDefaultKeys['attributes']) ? $this->aDefaultKeys['attributes'] + self::$_aDefaultKeys['attributes'] : self::$_aDefaultKeys['attributes'];
        return array('sFieldTypeSlug' => $sFieldTypeSlug, 'aFieldTypeSlugs' => $this->aFieldTypeSlugs, 'hfRenderField' => array($this, "_replyToGetField"), 'hfGetScripts' => array($this, "_replyToGetScripts"), 'hfGetStyles' => array($this, "_replyToGetStyles"), 'hfGetIEStyles' => array($this, "_replyToGetInputIEStyles"), 'hfFieldLoader' => array($this, "_replyToFieldLoader"), 'hfFieldSetTypeSetter' => array($this, "_replyToFieldTypeSetter"), 'hfDoOnRegistration' => array($this, "_replyToDoOnFieldRegistration"), 'aEnqueueScripts' => $this->_replyToGetEnqueuingScripts(), 'aEnqueueStyles' => $this->_replyToGetEnqueuingStyles(), 'aDefaultKeys' => $_aDefaultKeys,);
    }
    public function _replyToGetField($aField) {
        return '';
    }
    public function _replyToGetScripts() {
        return '';
    }
    public function _replyToGetInputIEStyles() {
        return '';
    }
    public function _replyToGetStyles() {
        return '';
    }
    public function _replyToFieldLoader() {
    }
    public function _replyToFieldTypeSetter($sFieldSetType = '') {
        $this->_sFieldSetType = $sFieldSetType;
    }
    public function _replyToDoOnFieldRegistration(array $aField) {
    }
    protected function _replyToGetEnqueuingScripts() {
        return array();
    }
    protected function _replyToGetEnqueuingStyles() {
        return array();
    }
    protected function enqueueMediaUploader() {
        add_filter('media_upload_tabs', array($this, '_replyToRemovingMediaLibraryTab'));
        wp_enqueue_script('jquery');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
        if (function_exists('wp_enqueue_media')) {
            new AmazonAutoLinks_AdminPageFramework_Script_MediaUploader($this->oMsg);
        } else {
            wp_enqueue_script('media-upload');
        }
        if (in_array($this->getPageNow(), array('media-upload.php', 'async-upload.php',))) {
            add_filter('gettext', array($this, '_replyToReplaceThickBoxText'), 1, 2);
        }
    }
    public function _replyToReplaceThickBoxText($sTranslated, $sText) {
        if (!in_array($this->getPageNow(), array('media-upload.php', 'async-upload.php'))) {
            return $sTranslated;
        }
        if ($sText != 'Insert into Post') {
            return $sTranslated;
        }
        if ($this->getQueryValueInURLByKey(wp_get_referer(), 'referrer') != 'admin_page_framework') {
            return $sTranslated;
        }
        if (isset($_GET['button_label'])) {
            return $_GET['button_label'];
        }
        return $this->oProp->sThickBoxButtonUseThis ? $this->oProp->sThickBoxButtonUseThis : $this->oMsg->get('use_this_image');
    }
    public function _replyToRemovingMediaLibraryTab($aTabs) {
        if (!isset($_REQUEST['enable_external_source'])) {
            return $aTabs;
        }
        if (!$_REQUEST['enable_external_source']) {
            unset($aTabs['type_url']);
        }
        return $aTabs;
    }
}