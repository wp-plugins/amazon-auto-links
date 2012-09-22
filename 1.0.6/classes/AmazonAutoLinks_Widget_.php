<?php
class AmazonAutoLinks_Widget_ extends WP_Widget {

	/*
		Since: v1.0.6
		Description: Creates a widget.
	*/
	public function __construct() {
		parent::__construct(
	 		'amazonautolinks_widget', 
			'Amazon Auto Links', 
			array( 'description' => __( 'Amazon Auto Links widget', 'amazonautolinks' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$unit = apply_filters( 'widget_title', $instance['unit'] );
		
		echo $before_widget;
		
		if ( ! empty( $title ) ) echo $before_title . $title . $after_title;
		
		// WIDGET CODE GOES HERE
		$oAALOptions = new AmazonAutoLinks_Options(AMAZONAUTOLINKSKEY);
		$oAAL = new AmazonAutoLinks_Core($unit);
		echo $oAAL->fetch();
		
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['unit'] = $new_instance['unit'];
		return $instance;
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) $title = $instance[ 'title' ];
		else $title = __( 'New title', 'amazonautolinks' );
		if ( isset( $instance[ 'unit' ] ) ) $selected_unit = $instance[ 'unit' ];

		$arrOptions = get_option('amazonautolinks');
		$numUnits = count($arrOptions['units']);
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'amazonautolinks' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'unit' ); ?>"><?php _e( 'Units:', 'amazonautolinks' ); ?></label><br />
			<select name="<?php echo $this->get_field_name( 'unit' ); ?>" id="<?php echo $this->get_field_id( 'unit' ); ?>">
				<option value=""><?php echo $numUnits > 0 ? __('Select Unit', 'amazonautolinks') : __('No Unit' , 'amazonautolinks'); ?></option>
				<?php foreach($arrOptions['units'] as $arrUnitOption) 
					echo '<option value="' . esc_attr( $arrUnitOption['unitlabel'] ) . '" ' . ($arrUnitOption['unitlabel'] == $selected_unit ? 'selected="Selected"' : '') .  '>'
						. $arrUnitOption['unitlabel'] 
						. '</option>';
				?>
			</select>
		</p>
		<?php 
	}
} 

