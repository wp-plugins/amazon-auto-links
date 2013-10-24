<?php
abstract class AmazonAutoLinks_MetaBox_Template_ extends AmazonAutoLinks_AdminPageFramework_MetaBox {

	public function setUp() {
		
		$oTemplates = $GLOBALS['oAmazonAutoLinks_Templates'];
		$this->addSettingField(			
			array(
				'strFieldID'		=> 'template_id',
				'strTitle'			=> __( 'Select Template', 'amazon-auto-links' ),
				'strDescription'	=> __( 'Sets a default template for this unit.', 'amazon-auto-links' ),
				'vLabel'			=> $arr = $oTemplates->getTemplateArrayForSelectLabel(),
				'strType'			=> 'select',
				// 'strAfterField' 	=> '<pre>' . print_r( $arr, true ) . '</pre>', // debug
				'vDefault'			=> $oTemplates->getPluginDefaultTemplateID( $GLOBALS['strAmazonAutoLinks_UnitType'] ),	
				'fHideTitleColumn'	=> true,
			)						
		);
		
	}
	
}