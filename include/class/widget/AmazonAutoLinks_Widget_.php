<?php
/**
 * The base class for the plugin's widget classes.
 * 
 */
abstract class AmazonAutoLinks_Widget_ extends WP_Widget {

	protected $arrStructure_FormElements = array(
		'title'			=> null,
		'id'	=> array(),
	);

	// Redefine this method in the extended class
	public static function registerWidget() {
		return register_widget( 'Put_The_Extended_Class_Name_Here' );	// the class name - get_class( self ) does not work.
	}	
	// Redefine this method in the extended class
	protected function echoFormElements( $arrInstance, $arrIDs, $arrNames ) {
		// Render form elements in the extended class method.
	}
	// Redefine this method in the extended class
	protected function echoContents( $arrInstance ) {
		var_dump( $arrInstance );
	}
	
	public function widget( $arrWidgetInfo, $arrInstance ) {	// must be public, the protected scope will cause fatal error.
		
		echo $arrWidgetInfo['before_widget']; 
		
		// Avoid undefined index warnings.
		$arrInstance = $arrInstance + $this->arrStructure_FormElements;
		if ( $arrInstance['title'] )
			echo "<h3 class='amazon-auto-links-widget widget-title'>{$arrInstance['title']}</h3>";
		
		$this->echoContents( $arrInstance );
		
		echo $arrWidgetInfo['after_widget'];
		
	}	

	public function form( $arrInstance ) {	
		
		// Avoid undefined index warnings.
		$arrInstance = $arrInstance + $this->arrStructure_FormElements;
		// $arrInstance['template'] = isset( $arrInstance['template'] ) 
			// ? $arrInstance['template']
			// : $GLOBALS['oAmazonAutoLinks_Templates']->getPluginDefaultTemplateID();
		$arrIDs = $this->getFieldValues( 'id' );
		$arrNames = $this->getFieldValues( 'name' );
		
		$this->echoFormElements( $arrInstance, $arrIDs, $arrNames );
		
	}
	
	
	/**
	 * Returns an array of filed values by a specified field.
	 * @param			string			$strField			can be either name or id.
	 */
	protected function getFieldValues( $strField='id' ) {
		
		$arrFields = array();
		foreach( $this->arrStructure_FormElements as $strFieldKey => $v )  
			$arrFields[ $strFieldKey ] = $strField == 'id' 
				? $this->get_field_id( $strFieldKey )
				: $this->get_field_name( $strFieldKey );
	
		return $arrFields;
	}
	
	/**
	 * The validation method for the widget form.
	 */
	public function update( $arrNewInstance, $arrOldInstance ) {
        return $arrNewInstance;
    }
	
}