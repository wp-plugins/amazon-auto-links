<?php
/**
 Admin Page Framework v3.5.11b02 by Michael Uno
 Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
 <http://en.michaeluno.jp/admin-page-framework>
 Copyright (c) 2013-2015, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT>
 */
abstract class AmazonAutoLinks_AdminPageFramework_TaxonomyField extends AmazonAutoLinks_AdminPageFramework_TaxonomyField_Controller {
    static protected $_sFieldsType = 'taxonomy';
    function __construct($asTaxonomySlug, $sOptionKey = '', $sCapability = 'manage_options', $sTextDomain = 'admin-page-framework') {
        if (empty($asTaxonomySlug)) {
            return;
        }
        $this->oProp = new AmazonAutoLinks_AdminPageFramework_Property_TaxonomyField($this, get_class($this), $sCapability, $sTextDomain, self::$_sFieldsType);
        $this->oProp->aTaxonomySlugs = ( array )$asTaxonomySlug;
        $this->oProp->sOptionKey = $sOptionKey ? $sOptionKey : $this->oProp->sClassName;
        parent::__construct($this->oProp);
        $this->oUtil->addAndDoAction($this, "start_{$this->oProp->sClassName}");
    }
}