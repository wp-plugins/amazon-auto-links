<?php
/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.6
 * @description	Creates a widget.
*/
class AmazonAutoLinks_Widget_ extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'amazonautolinks_widget', 
			'Amazon Auto Links', 
			array( 'description' => __( 'Amazon Auto Links widget', 'amazon-auto-links' ), ) // Args
		);
	}

	static function RegisterWidget() {
		register_widget( "AmazonAutoLinks_Widget" );
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$unit = apply_filters( 'widget_title', $instance['unit'] );	// backward compatibility or v1.0.6 or below
		$strUnitID = apply_filters( 'widget_title', $instance['strUnitID'] );
		
		echo $before_widget;
		
		if ( ! empty( $title ) ) echo $before_title . $title . $after_title;
		
		/* START WIDGET CODE */
		$oAALOptions = new AmazonAutoLinks_Options( AMAZONAUTOLINKSKEY );
		
		if (isset($instance['unit'])) {	
			
			// backward compatiblity v 1.0.6 or below
			foreach ($oAALOptions->arrOptions['units'] as $strUnitKey => $arrUnitOption) {
				if ($arrUnitOption['unitlabel'] == $unit || (isset($arrUnitOption['id']) && $arrUnitOption['id'] == $strUnitID)) {
					$oAAL = new AmazonAutoLinks_Core( $oAALOptions->arrOptions['units'][$strUnitKey], $oAALOptions );
// echo 'found the match: ' . $unit . '<br />';								
					break;
				}
			}
		}
		else {		// there is no problem	
			// && !isset($oAALOptions->arrOptions['units'][$strUnitID])
// echo 'called normally: ' . $strUnitID . '<br />';			
			$oAAL = new AmazonAutoLinks_Core( $oAALOptions->arrOptions['units'][$strUnitID], $oAALOptions ); // the constructor accepts a unit option in the first parameter
		}
		
		if ( isset( $oAAL ) && is_object( $oAAL ) ) echo $oAAL->fetch();
		
		/* END of WIDGET CODE */
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['strUnitID'] = $new_instance['strUnitID'];
		$instance['unit'] = ''; //$old_instance['unit'];	// for backward compatiblity v1.0.6 or below, return the old value
		return $instance;
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) $title = $instance[ 'title' ];
		else $title = __( 'New title', 'amazon-auto-links' );
		$selected_unit = ( isset( $instance[ 'strUnitID' ] ) ) ? $instance[ 'strUnitID' ] : '';
		$unit = ( isset( $instance[ 'unit' ] ) && !empty($instance[ 'unit' ]) ) ? $instance[ 'unit' ] : '';	// for backward compatiblity v1.0.6 or below
		
		$arrOptions = get_option( 'amazonautolinks' );
		
		// for backward compatiblity v 1.0.6 or below, which does not have a unique key in the option
		$arrOptions = $this->fix_unitoption($arrOptions);
		
		// for backward compatiblity v 1.0.6 or below, set the selected unit from the previous version's selected unit label 
		if (!empty($unit) && empty($selected_unit)) {
			foreach($arrOptions['units'] as $arrUnitOption) {
				if ($arrUnitOption['unitlabel'] == $unit) {
					$selected_unit = $arrUnitOption['id'];
					break;
				}
			}
		}
		
		$numUnits = count($arrOptions['units']);
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'amazon-auto-links' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'strUnitID' ); ?>"><?php _e( 'Units:', 'amazon-auto-links' ); ?></label><br />
			<select name="<?php echo $this->get_field_name( 'strUnitID' ); ?>" id="<?php echo $this->get_field_id( 'strUnitID' ); ?>">
				<option value=""><?php echo $numUnits > 0 ? __('Select Unit', 'amazon-auto-links') : __('No Unit' , 'amazon-auto-links'); ?></option>
				<?php 
				foreach($arrOptions['units'] as $arrUnitOption) {
					echo '<option value="' . esc_attr( $arrUnitOption['id'] ) . '" ' . ($arrUnitOption['id'] == $selected_unit ? 'selected="Selected"' : '') .  '>'
						. $arrUnitOption['unitlabel'] 
						. '</option>';
					
					}
				?>
			</select>
			<input type="hidden" name="<?php echo $this->get_field_name( 'unit' ); ?>" value="" />	
		</p>
		<?php 
		/* 
		<input type="hidden" id="<?php echo $this->get_field_id( 'unit' ); ?>" name="<?php echo $this->get_field_name( 'unit' ); ?>" value="<?php echo esc_attr( $arrUnitOption['unitlabel'] ) ; // for backward compatiblity v1.0.6 or below?>" />			
		*/
	}
	function fix_unitoption($arrOptions) {
		
		// since v1.0.7
		// if there is a unit option without the ID element, adds it and rename the unit key to be the unique ID. 
		$arrUnitKeys_without_ID = array();
		foreach($arrOptions['units'] as $strUnitKey => $arrUnitOption) 
			if (!isset($arrUnitOption['id'])) array_push($arrUnitKeys_without_ID, $strUnitKey);
		if (count($arrUnitKeys_without_ID) == 0) return $arrOptions;	// no problem found, return
		
		// fix the option to have an ID and store it with the key name of the ID
		foreach($arrUnitKeys_without_ID as $strUnitKey){
			$arrUnitOption = $arrOptions['units'][$strUnitKey];
			$arrUnitOption['id'] = uniqid();
			unset($arrOptions['units'][$strUnitKey]);
			$arrOptions['units'][$arrUnitOption['id']] = $arrUnitOption;
		}
		update_option('amazon-auto-links', $arrOptions);
		return $arrOptions;
	}
} 

