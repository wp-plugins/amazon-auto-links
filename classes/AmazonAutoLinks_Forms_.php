<?php
class AmazonAutoLinks_Forms_ {

	/*  
		Warning: Never use update_option() in this class.
		this class is to just display form elements, not manipulating option values.
		
		Todo: do not instantiate the option object but make a parameter to the constructor so that we save more memory
	*/
	
	public $classver = 'standard';
	protected $pluginkey = 'amazon-auto-links';
	protected $optionkey = 'amazonautolinks';

	function __construct( $pluginkey, &$oOption, &$oUserAd='' ) {
		$this->pluginkey = $pluginkey;
		$this->pageslug = $pluginkey;
		$this->textdomain = $pluginkey;
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($pluginkey);
		$this->oOption = $oOption; 
		$this->oUserAd = $oUserAd;
	}
	function get_default_unitoptions() {
	
		// returns an array containing the default option values
		return $this->oOption->unitdefaultoptions;
	}
	function embednonce($action, $name, $referer=true, $echo=true) {
		if ( function_exists('wp_nonce_field') ) {
			wp_nonce_field($action, $name, $referer, $echo); 
		}  // embed a nonce field 
		else 
			return null;
	}
	function verifynonce_in_tab( $numTabNumber, $action='', $name='' ) {
	
		// veryfies nonce with the given options and also checks whether the specified hidden tag field is sent
		// the $_POST array's format is specifically designed for this plugin, $_POST[pluginkey][tabNNN][tabNNN_submitted], where NNN is the tab number.
		if ( !$action ) 
			$action = $this->pluginkey;	
		if ( !$name )
			$name = 'nonce';
			
		if ( !function_exists( 'wp_verify_nonce' ) ) return null;
		
		if ( isset( $_POST[$this->pluginkey]['tab' . $numTabNumber]['tab' . $numTabNumber . '_submitted'] ) && !wp_verify_nonce( $_POST[$name], $action ) )	
			return false;
		
		return true;
	}
	function embedhiddenfield($pluginkey, $tabnum) {
	
		// embeds a hidden input field with the given options, specifically formatted to the plugin, Amazon Auto Links
		// the format of the name :  [$pluginkey][tabNNN][tabNNN_submitted]  
		// NNN is the tab number
		?>
			<input type="hidden" name="<?php echo $pluginkey; ?>[tab<?php echo $tabnum; ?>][tab<?php echo $tabnum; ?>_submitted]" value="1" />
		<?php
	}	
	function field_submitbutton( $strFieldName, $strDisplayedValue, $bEnable=True, $strClass="button-primary" ) {
	
		// note that this does not have a form tag.
		$strDisable = $bEnable ? '' : 'disabled="disabled"';
		?>
		<input type="submit" class="<?php echo $strClass; ?>" name="<?php echo $strFieldName; ?>" value="<?php echo $strDisplayedValue; ?>" <?php echo $strDisable; ?> />
		<?php
	}	
	function form_submitbutton( $numTab, $strNameKey, $strDisplayValue="", $strNonceKey="nonce", $bFormTag=True, $bEnable=True, $strButtonClass="button-primary" ) {
	
		// this is a single form button which links to the specified tab numbered page.
		// note that it includes a form tab 
		$strDisplayValue = $strDisplayValue ? $strDisplayValue : __( "Go Back", 'amazon-auto-links' );
		if ( $bFormTag )
			echo '<form method="post" action="?page=' . $this->pageslug . '&tab=' . $numTab . '" >';
		$this->embednonce( $this->pluginkey, $strNonceKey );
		$this->embedhiddenfield( $this->pluginkey, $numTab ); 
		$this->field_submitbutton( $this->pluginkey . '[tab' . $numTab . '][' . $strNameKey . ']', $strDisplayValue, $bEnable, $strButtonClass );
		if ( $bFormTag )
			echo '</form>';
	}	
	function clean_generaloptions($arrGeneralOptions) {

		// since v1.0.9
		// - added for the cloak query option
		
		// if nothing is submitted for the "cloakquery" value, set the default
		if (strlen(trim($arrGeneralOptions['cloakquery'])) == 0)  
			$arrGeneralOptions['cloakquery'] = $this->oOption->generaldefaultoptions['cloakquery'];
		
		$arrGeneralOptions['cloakquery'] = $this->oAALfuncs->fix_request_array_key($arrGeneralOptions['cloakquery']);
		// $arrGeneralOptions['cloakquery'] = rawurlencode(trim($arrGeneralOptions['cloakquery'])); //<-- do not do this since if there is a encoded invalid character, it keeps continue converting and everytime it is saved it keeps changing its value
		return $arrGeneralOptions;
	}
	function validate_generaloptions($arrGeneralOptions) {
		global $table_prefix;
		// since v1.0.9
		// - added for the cloak query option
		$bInvalid = false;
		$arrErrors = array();

		// nothing to do so far
		// since v1.1.3
		// if the prefetch category links is disabled, clean the transients
		if ($arrGeneralOptions['prefetch'] == 0) {
			
			// remove category caches
			global $wpdb;
			$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
			$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
		
		}
		
		return false;
	
	}
	function validate_unitoptions($arrOptions, $mode="new") {
		
		// if invalid, returns an array containing the error infomation
		// called from and dependant on $this->adminpage()
		$bInvalid = false;
		$arrErrors = array();
		
		// check: tab100 -> unitlabel
		if (strlen(trim($arrOptions['unitlabel'])) == 0)  {
			$arrErrors['unitlabel'] .= ' ' . __('The unit label cannot be empty.', 'amazon-auto-links');
			$bInvalid = true;	
		}			

		// if the method is called from the 'Edit Unit' page and the prior unit label is the same
		if ( $mode != 'new' && trim( $arrOptions['unitlabel'] ) == trim( $arrOptions['prior_unitlabel'] ) ) {	
			// no need to check because the unit label is not edited.
		} else {
		// otherwise, check if the same unit label already exists
			$arrRootOptions = get_option( $this->optionkey );
			foreach($arrRootOptions['units'] as $strUnitID => $arrUnitOption) {
				$unitlabel = $arrUnitOption['unitlabel'];
				if ($unitlabel == $arrOptions['unitlabel']) {
					$arrErrors['unitlabel'] .= trim( ' ' . __( 'The unit label already exists:', 'amazon-auto-links' ) . $unitlabel ) . ' ' ; 
					$bInvalid = true;
					break;
				}
			}
		}

		// check: tab100 -> associateid
		if (strlen(trim($arrOptions['associateid'])) == 0) {
			$arrErrors['associateid'] .= ' ' . __( 'Associate ID cannot be empty.', 'amazon-auto-links' );
			$bInvalid = True;
		}
			
		if ($bInvalid)
			return $arrErrors;
		else
			return null;
			
	}	
	function clean_unitoptions($arrUnitOptions) {	
	
		// called from admin_tab100() this also can be called from admin_tab200
		// this fiexes sent form data for creating/modifing the unit information
		
		// just fix it to the default value	
		$arrUnitOptions['imagesize'] = $this->oAALfuncs->fixnum(	$arrUnitOptions['imagesize']	// subject
																,	$this->oOption->unitdefaultoptions['imagesize']	// default value
																,	0		// minimum: 0
																,	500);	// max: 500
		// max number of ites to show		
		$arrUnitOptions['numitems'] = $this->oAALfuncs->fixnum(		$arrUnitOptions['numitems']
																,	$this->oOption->unitdefaultoptions['imagesize']
																,	1
																,	10);
		
		// Clean the text input. 
		$arrUnitOptions['unitlabel'] = trim($arrUnitOptions['unitlabel']);
		$arrUnitOptions['associateid'] = trim($arrUnitOptions['associateid']);	
		
		// set the minimum value for cache expiration seconds 
		$arrUnitOptions['cacheexpiration'] = $this->oAALfuncs->fixnum(	$arrUnitOptions['cacheexpiration']			// subject
																,	$this->oOption->unitdefaultoptions['cacheexpiration']	// default value
																,	60);	// minimum 60, maximum no limit
																
		// *warning: never save option values containing html code without fltering correctly; otherwise, WordPress automatically escapes characters and the code gets messed.
		$arrUnitOptions['containerformat'] = stripslashes(wp_filter_post_kses(addslashes($arrUnitOptions['containerformat'])));
		$arrUnitOptions['itemformat'] = stripslashes(wp_filter_post_kses(addslashes($arrUnitOptions['itemformat'])));
		$arrUnitOptions['imgformat'] = stripslashes(wp_filter_post_kses(addslashes($arrUnitOptions['imgformat'])));
		return $arrUnitOptions;
	}	
	function changecountyinfo_unitoptions($arrUnitOptions) {
	
		// adds/changes countyurl and mblang elements to the unit option
		$arrUnitOptions['countryurl'] = $this->oOption->arrCountryURLs[$arrUnitOptions['country']];
		$arrUnitOptions['mblang'] = $this->oOption->arrCountryLang[$arrUnitOptions['country']];		
		return $arrUnitOptions;	
	}
	function addadtype_unitoptions($arrUnitOptions) {
	
		// adds 'adtypes' element to the option
		$arrUnitOptions['adtypes'] = array(
			'bestsellers' 		=> array('check' => $arrUnitOptions['feedtypes']['bestsellers'], 'slug' => 'bestsellers'),
			'hotnewreleases'	=> array('check' => $arrUnitOptions['feedtypes']['hotnewreleases'], 'slug' => 'new-releases'),
			'moverandshakers'	=> array('check' => $arrUnitOptions['feedtypes']['moverandshakers'], 'slug' => 'movers-and-shakers'),
			'toprated'			=> array('check' => $arrUnitOptions['feedtypes']['toprated'], 'slug' => 'top-rated'),
			'mostwishedfor'		=> array('check' => $arrUnitOptions['feedtypes']['mostwishedfor'], 'slug' => 'most-wished-for'),
			'giftideas'			=> array('check' => $arrUnitOptions['feedtypes']['giftideas'], 'slug' => 'most-gifted')
		);
		
		// if none of feedtypes are checked, set it to bestsellers
		$numChecked = 0;
		foreach($arrUnitOptions['adtypes'] as $adtype) {
			if ($adtype['check']) {
				$numChecked++;
				break;
			}
		}
		if ($numChecked == 0) 
			$arrUnitOptions['adtypes']['bestsellers']['check'] = true;
		
		return $arrUnitOptions;	
	}
	function setup_unitoption($arrUnitOptions) {	
		// v1.0.7
		$arrUnitOptions = $this->clean_unitoptions($arrUnitOptions);
		$arrUnitOptions = $this->changecountyinfo_unitoptions($arrUnitOptions);					
		$arrUnitOptions = $this->addadtype_unitoptions($arrUnitOptions);
		return $arrUnitOptions;
	}	
	function get_pro_description() {
		$strCurrURL = preg_replace('/(?<=tab=)\d+/i', '400', $this->oAALfuncs->selfURL());	// the url specifying the tab number, 400, which is for the pro version info page. // the pattern is replaced from '/tab\=\K\d+/i' since \K is avaiable above PHP 5.2.4
	?>	
		<p><?php _e('In order to set up the following options, please upgrade to the <a href="' . $strCurrURL . '">pro version</a>.', 'amazon-auto-links'); ?></p>
	<?php
	}
	function form_setunit($numTabNum, $arrOptionsToDisplay="", $arrErrors="") {
	
		// called from admin_tab100() and admin_tab202()
		// if the option is not set, put the default value
		// it's premised that this method is called inside a form tag. e.g. <form> ..  $oClass->form_setunit() .. </form>
		if ( !is_array( $arrOptionsToDisplay ) ) 
			$arrOptionsToDisplay = $this->oOption->unitdefaultoptions;
		if ( !is_array( $arrErrors ) ) 
			$arrErrors = array();
					
		?>	
		<table class="form-table" style="clear:left; width:auto;">
			<tbody>
			<?php
			$this->field_element_unitlabel( $numTabNum, $arrOptionsToDisplay['unitlabel'], isset( $arrErrors['unitlabel'] ) ? $arrErrors['unitlabel'] : '' ); 
			$this->field_element_country( $numTabNum, $arrOptionsToDisplay['country'] ); 
			$this->field_element_associateid( $numTabNum, $arrOptionsToDisplay['associateid'], isset( $arrErrors['associateid'] ) ? $arrErrors['associateid'] : '' ); 
			$this->field_element_numberofitems( $numTabNum, $arrOptionsToDisplay['numitems'] ); 
			$this->field_element_imagesize( $numTabNum, $arrOptionsToDisplay['imagesize'] ); 
			$this->field_element_sortorder( $numTabNum, $arrOptionsToDisplay['sortorder'] ); 
			$this->field_element_adtypes( $numTabNum, $arrOptionsToDisplay['feedtypes'] ); 
			$this->field_element_nosim( $numTabNum, $arrOptionsToDisplay['nosim'] ); 
			$this->field_element_insert( $numTabNum, $arrOptionsToDisplay['insert'] ); 
			$this->field_element_titlelength( $numTabNum, $arrOptionsToDisplay['titlelength'] ); 
			$this->field_element_linkstyle( $numTabNum, $arrOptionsToDisplay['linkstyle'] ); 
			$this->field_element_credit( $numTabNum, $arrOptionsToDisplay['credit'] ); 
			$this->field_element_urlcloaking( $numTabNum, $arrOptionsToDisplay['urlcloak'] ); 
			$this->field_element_disableonhome( $numTabNum, $arrOptionsToDisplay['disableonhome'] ); 
			$this->field_element_poststobedisabled( $numTabNum, $arrOptionsToDisplay['poststobedisabled'] ); 
			?>
			</tbody>
		</table>
		<?php $this->oUserAd->ShowTextAd(); // oUserAd must be instantiated prior to this method call ?>
		<p class="submit">
			<?php 
				$strFieldName = $this->pluginkey . '[tab' . $numTabNum . '][proceedbutton]';
				$this->field_submitbutton($strFieldName, __('Proceed', 'amazon-auto-links')); 
				if ($numTabNum == 202) {
					$strFieldNameSaveButton = $this->pluginkey . '[tab' . $numTabNum . '][savebutton]';
					$this->field_submitbutton($strFieldNameSaveButton, __('Save', 'amazon-auto-links')); 
				}
			?>
		</p>
		<h3><?php _e('Advanced Option', 'amazon-auto-links'); ?></h3>
		<?php $this->get_pro_description(); ?>
		<table class="form-table" style="clear:left; width:auto;" >	
			<tbody>
				<?php $this->field_element_cacheexpiration($numTabNum, $arrOptionsToDisplay['cacheexpiration']); ?>
				<?php $this->field_element_containerformat($numTabNum, $arrOptionsToDisplay['containerformat']); ?>
				<?php $this->field_element_itemformat($numTabNum, $arrOptionsToDisplay['itemformat']); ?>		
				<?php $this->field_element_imgformat($numTabNum, $arrOptionsToDisplay['imgformat']); ?>		
			</tbody>
		</table>						

		<p class="submit">
			<?php 
			$this->field_submitbutton($strFieldName, __('Proceed', 'amazon-auto-links')); 
			if ($numTabNum == 202) 
				$this->field_submitbutton($strFieldNameSaveButton, __('Save', 'amazon-auto-links')); 
			?>
		</p>
		<?php
	}
	
	/* Methods for $this->form_setunit() -- the parts of form fields */	
	function field_element_unitlabel( $numTabnum, $strValue="", $strWarning="" ) {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][unitlabel]';
		$strPriorUnitLabel = $this->pluginkey . '[tab' . $numTabnum . '][prior_unitlabel]';
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['unitlabel'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Unit Label', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" size="30" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  />
				<input type="hidden" name="<?php echo $strPriorUnitLabel; ?>" value="<?php echo $strValue; ?>" />
				
				&nbsp;<font color="#666">( <?php _e('String value to identify the ad unit.', 'amazon-auto-links'); ?> e.g. unit-1 )</font>							
				<?php echo $strWarning ? '<font color="red">*' . $strWarning . '</font>' : '' ;  //this message is shown when the field is set blank. ?>
			</td>
		</tr>
		<?php
	}	
	function field_element_country($numTabnum, $strValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][country]';
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['country'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Country', 'amazon-auto-links'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
				<option value="AT" <?php echo $strValue == 'AT' ? 'Selected' : ''; ?>>AT - <?php _e('Austria', 'amazon-auto-links'); ?></option>
				<option value="CA" <?php echo $strValue == 'CA' ? 'Selected' : ''; ?>>CA - <?php _e('Canada', 'amazon-auto-links'); ?></option>
				<option value="CN" <?php echo $strValue == 'CN' ? 'Selected' : ''; ?>>CN - <?php _e('China', 'amazon-auto-links'); ?></option>
				<option value="FR" <?php echo $strValue == 'FR' ? 'Selected' : ''; ?>>FR - <?php _e('France', 'amazon-auto-links'); ?></option>
				<option value="DE" <?php echo $strValue == 'DE' ? 'Selected' : ''; ?>>DE - <?php _e('Germany', 'amazon-auto-links'); ?></option>
				<option value="IT" <?php echo $strValue == 'IT' ? 'Selected' : ''; ?>>IT - <?php _e('Italy', 'amazon-auto-links'); ?></option>
				<option value="JP" <?php echo $strValue == 'JP' ? 'Selected' : ''; ?>>JP - <?php _e('Japan', 'amazon-auto-links'); ?></option>
				<option value="UK" <?php echo $strValue == 'UK' ? 'Selected' : ''; ?>>UK - <?php _e('United Kingdom', 'amazon-auto-links'); ?></option>
				<option value="ES" <?php echo $strValue == 'ES' ? 'Selected' : ''; ?>>ES - <?php _e('Spain', 'amazon-auto-links'); ?></option>
				<option value="US" <?php echo $strValue == 'US' ? 'Selected' : ''; ?>>US - <?php _e('United States', 'amazon-auto-links'); ?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('Select the country for the associate ID.', 'amazon-auto-links'); ?> )</font>							
			</td>
		</tr>
		<?php
	}
	function field_element_associateid($numTabnum, $strValue="", $strWarning="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][associateid]';
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['associateid'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Associate ID', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" size="30" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>" />
				&nbsp;<font color="#666">( e.g. <?php echo $this->myassociateid() ;?> )</font>	
				<?php echo $strWarning ? '<font color="red">*' . $strWarning . '</font>' : ''; ?>
			</td>
		</tr>		
	<?php
	}
	function myassociateid() {
		return 'miunosoft-20';
	}
	function field_element_numberofitems($numTabnum, $numValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][numitems]';
		$numValue = $numValue ? $numValue : $this->oOption->unitdefaultoptions['numitems'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Number of Items to Show', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" size="30" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666">( <?php _e('Default', 'amazon-auto-links');?>: <?php echo $this->oOption->unitdefaultoptions['numitems']; ?> <?php _e('Max', 'amazon-auto-links'); ?> : 10 )</font>	
			</td>
		</tr>							
	<?php
	}	
	function field_element_imagesize($numTabnum, $numValue="", $strWarning="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][imagesize]';	
		$numValue = $numValue !== '' ? $numValue : $this->oOption->unitdefaultoptions['imagesize'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Image Size', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666"><?php _e('in pixel.', 'amazon-auto-links');?> ( <?php _e('Accepts upto 500. Set 0 for no image.', 'amazon-auto-links');?> <?php _e('Default', 'amazon-auto-links');?> : <?php echo $this->oOption->unitdefaultoptions['imagesize']; ?> )</font>
			</td>
		</tr>	
	<?php
	}
	function field_element_sortorder($numTabnum, $strValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][sortorder]';
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['sortorder'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Sort Order', 'amazon-auto-links'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
					<option value="date" <?php echo $strValue == 'date' ? 'Selected' : '' ?> ><?php _e('Date', 'amazon-auto-links');?></option>
					<option value="title" <?php echo $strValue == 'title' ? 'Selected' : '' ?> ><?php _e('Title', 'amazon-auto-links');?></option>
					<option value="random" <?php echo $strValue == 'random' ? 'Selected' : '' ?> ><?php _e('Random', 'amazon-auto-links');?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('Defines how the product links are sorted.', 'amazon-auto-links');?> )</font>							
			</td>
		</tr>	
	<?php
	}
	function field_element_adtypes($numTabnum, $arrValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][feedtypes]';
		$arrValue = $arrValue ? $arrValue : $this->oOption->unitdefaultoptions['feedtypes'];
	?>
		<tr valign="top">
			<th scope="row">
				<?php _e('Ad Types', 'amazon-auto-links'); ?><br /><br />
				<span style="margin-left:1em; padding-right:2em; color:#666;"><?php _e('It is recommended to check only a few for faster page loading.', 'amazon-auto-links'); ?><br />
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>[bestsellers]" 		value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[hotnewreleases]"	value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[moverandshakers]"	value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[toprated]" 			value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[mostwishedfor]"		value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[giftideas]" 		value="0" />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[bestsellers]" 	value="1" <?php echo $arrValue['bestsellers'] ? 'Checked' : '' ?>> <?php _e('Best Sellers', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[hotnewreleases]"	value="1" <?php echo $arrValue['hotnewreleases'] ? 'Checked' : '' ?>> <?php _e('Hot New Releases', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[moverandshakers]" value="1" <?php echo $arrValue['moverandshakers'] ? 'Checked' : '' ?>> <?php _e('Mover & Shakers', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[toprated]" 		value="1" <?php echo $arrValue['toprated'] ? 'Checked' : '' ?>> <?php _e('Top Rated', 'amazon-auto-links');?>&nbsp;&nbsp;&nbsp;&nbsp;<font color="#666">( <?php _e('This may not be available in some countries.', 'amazon-auto-links'); ?>)</font><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[mostwishedfor]"	value="1" <?php echo $arrValue['mostwishedfor'] ? 'Checked' : '' ?>> <?php _e('Most Wished For', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[giftideas]" 		value="1" <?php echo $arrValue['giftideas'] ? 'Checked' : '' ?>> <?php _e('Gift Ideas', 'amazon-auto-links');?><br />
			</td>
		</tr>	
	<?php
	}
	function field_element_nosim($numTabnum, $bValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][nosim]';	
		$bValue = $bValue ? $bValue : $this->oOption->unitdefaultoptions['nosim'];
	?>
		<tr valign="top">
			<th scope="row">
				<?php _e('Direct Link Bonus', 'amazon-auto-links'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo $bValue ? 'Checked' : '' ?>> ref=nosim <span style="color:#666;">( <?php _e('Inserts ref=nosim in the link url. For more information, visit the following page:', 'amazon-auto-links'); ?> <a href='https://affiliate-program.amazon.co.uk/gp/associates/help/t5/a21'>amazon associates</a> )</span>
			</td>
		</tr>		
	<?php
	}
	function field_element_insert($numTabNum, $arrValues) {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabNum . '][insert]';
		$arrValues = $arrValues ? $arrValues : $this->oOption->unitdefaultoptions['insert'];

	?>
		<tr valign="top">
			<th scope="row">
				<?php _e('Where To Insert', 'amazon-auto-links'); ?><br /><br />
				<span style="margin-left:1em; padding-right:2em; color:#666;"><?php _e( 'Check where items should be inserted.', 'amazon-auto-links' ); ?><br />
			</th>
			<td>		
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>[postabove_static]"	 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[postbelow_static]"	 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[postabove]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[postbelow]"			 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[excerptabove]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[excerptbelow]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedabove]"			 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedbelow]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedexcerptabove]"	 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedexcerptbelow]"	 value="0" />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[postabove_static]" value="1" <?php echo isset( $arrValues['postabove_static'] ) && $arrValues['postabove_static'] ? 'Checked' : '' ?>> <?php _e('Above Post on Publish', 'amazon-auto-links');?>&nbsp;(<?php _e('this insters links into the database so they will be static.', 'amazon-auto-links');?>)<br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[postbelow_static]" value="1" <?php echo isset( $arrValues['postbelow_static'] ) && $arrValues['postbelow_static'] ? 'Checked' : '' ?>> <?php _e('Below Post on Publish', 'amazon-auto-links');?>&nbsp;(<?php _e('this insters links into the database so they will be static.', 'amazon-auto-links');?>)<br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[postabove]" 		 value="1" <?php echo $arrValues['postabove'] ? 		'Checked' : '' ?>> <?php _e('Above Post', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[postbelow]"		 value="1" <?php echo $arrValues['postbelow'] ? 		'Checked' : '' ?>> <?php _e('Below Post', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[excerptabove]" 	 value="1" <?php echo $arrValues['excerptabove'] ? 		'Checked' : '' ?>> <?php _e('Above Excerpt', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[excerptbelow]" 	 value="1" <?php echo $arrValues['excerptbelow'] ? 		'Checked' : '' ?>> <?php _e('Below Excerpt', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedabove]"		 value="1" <?php echo $arrValues['feedabove'] ? 		'Checked' : '' ?>> <?php _e('Above Feed Item', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedbelow]" 		 value="1" <?php echo $arrValues['feedbelow'] ? 		'Checked' : '' ?>> <?php _e('Below Feed Item', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedexcerptabove]" value="1" <?php echo $arrValues['feedexcerptabove'] ?  'Checked' : '' ?>> <?php _e('Above Feed Excerpt', 'amazon-auto-links');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedexcerptbelow]" value="1" <?php echo $arrValues['feedexcerptbelow'] ?  'Checked' : '' ?>> <?php _e('Below Feed Excerpt', 'amazon-auto-links');?><br />
			</td>
		</tr>	
	<?php	
	}
	function field_element_widget($numTabnum, $bValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][widget]';	
		$bValue = $bValue ? $bValue : $this->oOption->unitdefaultoptions['widget'];
	?>
		<tr valign="top">
			<th scope="row">
				<?php _e('Widget', 'amazon-auto-links'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo $bValue ? 'Checked' : '' ?>> <?php _e('Creates a sidebar/footer widget for this unit. It will be added via the Widget section.', 'amazon-auto-links'); ?>
			</td>
		</tr>
	<?php
	}	
	function field_element_titlelength($numTabnum, $numValue="") {
		
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][titlelength]';	
		$numValue = !empty($numValue) ? $numValue : $this->oOption->unitdefaultoptions['titlelength'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Title Length', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666"><?php _e('It is used to prevent a broken layout caused by a very long product title. Set -1 for no limit.', 'amazon-auto-links');?> <?php _e('Default', 'amazon-auto-links');?> : <?php echo $this->oOption->unitdefaultoptions['titlelength']; ?></font>
			</td>
		</tr>	
	<?php	
	}
	function field_element_linkstyle($numTabnum, $numValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][linkstyle]';	
		$numValue = !empty($numValue) ? $numValue : $this->oOption->unitdefaultoptions['linkstyle'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Link Style', 'amazon-auto-links'); ?></th>
			<td>
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 1 ? 'Checked' : '' ?> value="1"> http://www.amazon.[domain-suffix]/[product-name]/dp/[asin]/ref=[...]?tag=[associate-id] &nbsp;(<font color="#666"><?php _e('Default', 'amazon-auto-links');?></font> )<br />
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 2 ? 'Checked' : '' ?> value="2"> http://www.amazon.[domain-suffix]/exec/obidos/ASIN/[asin]/[associate-id]/ref=[...]<br />
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 3 ? 'Checked' : '' ?> value="3"> http://www.amazon.[domain-suffix]/gp/product/[asin]/?tag=[associate-id]&ref=[...]<br />
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 4 ? 'Checked' : '' ?> value="4"> http://www.amazon.[domain-suffix]/dp/ASIN/[asin]/ref=[...]?tag=[associate-id]<br />
			</td>
		</tr>	
	<?php		
	}
	function field_element_credit($numTabnum, $bValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][credit]';	
		$bValue = ($bValue != '') ? $bValue : $this->oOption->unitdefaultoptions['credit'];
	?>
		<tr valign="top">
			<th scope="row">
				<?php _e('Credit Link', 'amazon-auto-links'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo !empty($bValue) ? 'Checked' : '' ?>> <?php _e('Inserts the credit link at the end of the unit.', 'amazon-auto-links'); ?>
			</td>
		</tr>
	<?php
	}
	function field_element_urlcloaking($numTabnum, $bValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][urlcloak]';	
		$bValue = ($bValue != '') ? $bValue : $this->oOption->unitdefaultoptions['urlcloak'];
		$strCloakQuery = empty($this->oOption->arrOptions['general']['cloakquery']) ? $this->oOption->generaldefaultoptions['cloakquery'] : $this->oOption->arrOptions['general']['cloakquery'];
	?>
		<tr valign="top">
			<th scope="row">
				<?php _e('URL Cloak', 'amazon-auto-links'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo !empty($bValue) ? 'Checked' : '' ?> /> 
				<?php 
					_e('Obfuscates product links.', 'amazon-auto-links');
					echo ' e.g. ' . site_url('?' . rawurlencode($strCloakQuery) . '=' . $this->oAALfuncs->urlencrypt("http://www.michaeluno.jp"));
				?>
			</td>
		</tr>
	<?php	
	}
	function field_element_disableonhome( $numTabnum, $bValue="" ) { 
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][disableonhome]';	
		$bValue = ( $bValue != '' ) ? $bValue : $this->oOption->unitdefaultoptions['disableonhome'];
	?>
		<tr valign="top">
			<th scope="row">
				<?php _e( 'Where to be Disabled', 'amazon-auto-links' ); ?>						
			</th>
			<td>
				<!-- the hidden field before the checkbox is necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo !empty( $bValue ) ? 'Checked' : '' ?> /> 
				<?php _e( 'Disable on the home page.', 'amazon-auto-links' ); ?>			
			</td>
		</tr>
	<?php			
	}
	function field_element_poststobedisabled( $numTabnum, $strValue="" ) {
		// Product links in the posts set with this option will not be displayed.
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][poststobedisabled]';	
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['poststobedisabled'];
		?>
		<tr valign="top">
			<th scope="row"></th>
			<td>
				<input type="text" size="80" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  /><br />&nbsp;
				<font color="#666">( <?php _e( 'Enter post IDs, separated by commas so that the Unit will not be displayed in the posts/pages.', 'amazon-auto-links' ); ?> e.g. 123,135,235 )</font>
			</td>
		</tr>
		<?php		
	}
	/*
	 *  Grayed on the free version
	 */
	function field_element_cacheexpiration($numTabnum, $numValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][cacheexpiration]';	
		$numValue = $numValue ? $numValue : $this->oOption->unitdefaultoptions['cacheexpiration'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Cache Expiration', 'amazon-auto-links'); ?><br />
			</th>
			<td>
				<input disabled style="background-color: #eee; color: #999;" type="text" size="30" value="<?php echo $numValue ; ?>" />
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666">( <?php _e('in seconds.', 'amazon-auto-links'); ?> <?php _e('Default', 'amazon-auto-links'); ?> : <?php echo $this->oOption->unitdefaultoptions['cacheexpiration']; ?> )</font>	
			</td>
		</tr>		
	<?php
	}
	function field_element_containerformat($numTabnum, $strValue="") {
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][containerformat]';	
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['containerformat'];
	?>
		<tr valign="top">
			<th scope="row" rowspan="2">
			<?php _e('Container Format', 'amazon-auto-links'); ?><br /><br />
			<span style="margin-left:1em; color:#666;">%item% - <?php _e('item', 'amazon-auto-links'); ?><br />
			</th>
			<td>
				<textarea disabled style="background-color: #eee; color: #999;" rows="4" cols="80" ><?php echo $strValue; ?></textarea>
				<textarea style="display:none" name="<?php echo $strFieldName; ?>" rows="4" cols="80"><?php echo $strValue; ?></textarea>
			</td>
		</tr>			
		<tr valign="top">
			<td style="margin-top:0; padding-top:0;">
				<?php _e('Default Value', 'amazon-auto-links'); ?>:<br />
				<div style="margin-left:1em; color: #666;">
					<?php echo htmlspecialchars($this->oOption->unitdefaultoptions['containerformat']); ?>
				</div>
			</td>
		</tr>	
	<?php
	}
	function field_element_itemformat($numTabnum, $strValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][itemformat]';	
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['itemformat'];
	?>
		<tr valign="top">
			<th scope="row" rowspan="2">
				<?php _e('Item Format', 'amazon-auto-links'); ?><br /><br />
				<div style="margin-left:1em; color:#666;">
					%link% - <?php _e('link url', 'amazon-auto-links'); ?><br />
					%title% - <?php _e('title', 'amazon-auto-links'); ?><br />
					%img% - <?php _e('thumbnail', 'amazon-auto-links'); ?><br />
					%htmldescription%<br />- <?php _e('description with HTML tags', 'amazon-auto-links'); ?><br />							
					%textdescription%<br />- <?php _e('description without HTML tags', 'amazon-auto-links'); ?><br />							
				</div>
			</th>
			<td>
				<textarea disabled style="background-color: #eee; color: #999;" rows="4" cols="80" ><?php echo $strValue; ?></textarea>
				<textarea style="display:none" name="<?php echo $strFieldName; ?>" rows="4" cols="80"><?php echo $strValue; ?></textarea>
			</td>
		</tr>	
		<tr valign="top" >							
			<td style="margin-top:0; padding-top:0;">
				<?php _e('Default Valie', 'amazon-auto-links'); ?>:<br />
				<div style="margin-left:1em; color:#666;">
					<?php echo htmlspecialchars($this->oOption->unitdefaultoptions['itemformat']); ?>
				</div>
			</td>
		</tr>	
	<?php
	}		
	function field_element_imgformat($numTabnum, $strValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][imgformat]';	
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['imgformat'];
	?>
		<tr valign="top">
			<th scope="row" rowspan="2">
				<?php _e('Image Format', 'amazon-auto-links'); ?><br /><br />
				<div style="margin-left:1em; color:#666;">
					%imgurl% - <?php _e('imgurl', 'amazon-auto-links'); ?><br />
					%link% - <?php _e('link url', 'amazon-auto-links'); ?><br />
					%title% - <?php _e('title', 'amazon-auto-links'); ?><br />					
					%textdescription%<br />- <?php _e('description without tags', 'amazon-auto-links'); ?><br />						
				</div>
			</th>
			<td>
				<textarea disabled style="background-color: #eee; color: #999;" rows="4" cols="80"><?php echo $strValue; ?></textarea>
				<textarea style="display:none" name="<?php echo $strFieldName; ?>" rows="4" cols="80"><?php echo $strValue; ?></textarea>
			</td>
		</tr>	
		<tr valign="top" >							
			<td style="margin-top:0; padding-top:0;">
				<?php _e('Default Valie', 'amazon-auto-links'); ?>:<br />
				<div style="margin-left:1em; color:#666;">
					<?php echo htmlspecialchars($this->oOption->unitdefaultoptions['imgformat']); ?>
				</div>
			</td>
		</tr>	
	<?php
	}	
	
	/*------------------------------------ General Settings ----------------------------------------*/
	function form_generaloptions($numTabNum, $arrOptionsToDisplay="", $arrErrors="") {
	
		// called from admin_tab300()
		// if the option is not set, put the default value
		// it's premised that this method is called inside a form tag. e.g. <form> ..  $oClass->form_setunit() .. </form>
		if ( !is_array( $arrOptionsToDisplay ) ) $arrOptionsToDisplay = $this->oOption->generaldefaultoptions;
		if ( !is_array( $arrErrors ) ) $arrErrors = array();		
		?>	
		<table class="form-table" style="clear:left; width:auto;">
			<tbody>
				<?php
				$this->field_element_support( $numTabNum, $arrOptionsToDisplay['supportrate'] );
				$this->field_element_blacklist_by_ASIN( $numTabNum, $arrOptionsToDisplay['blacklist'] );
				$this->field_element_blacklist_by_title( $numTabNum, $arrOptionsToDisplay['blacklist_title'] );
				$this->field_element_blacklist_by_description( $numTabNum, $arrOptionsToDisplay['blacklist_description'] );
				$this->field_element_cloakquery( $numTabNum, $arrOptionsToDisplay['cloakquery'] );
				$this->field_element_prefetch( $numTabNum, $arrOptionsToDisplay['prefetch'] );
				$this->field_element_license( $numTabNum, isset( $arrOptionsToDisplay['license'] ) ? $arrOptionsToDisplay['license'] : '' ); // since v1.1.9			
				
				// for addons since v1.1.8
				$strAdditionalFormsFields = '';
				echo apply_filters( 'aalhook_admin_form_generaloptions_fields',  $strAdditionalFormsFields );
				?>
			</tbody>
		</table>
		
		<?php
		// for addons since v1.1.8
		do_action( 'aalhook_admin_form_generaloptions_table' );
		?>
		
		<p class="submit">
			<?php 
				$strFieldName = $this->pluginkey . '[tab' . $numTabNum . '][savebutton]';
				$this->field_submitbutton($strFieldName, __('Save', 'amazon-auto-links')); 
			?>
		</p>
		<?php
	}	
	function field_element_support($numTabnum, $strValue="") {
	
		// called from form_generaloptions()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][supportrate]';
		$strValue = $strValue != "" ? $strValue : $this->oOption->generaldefaultoptions['supportrate'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Support Rate', 'amazon-auto-links'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
				<option value="0" <?php echo $strValue == '0' ? 'Selected' : ''; ?>><?php _e('0%', 'amazon-auto-links'); ?></option>
				<option value="10" <?php echo $strValue == '10' ? 'Selected' : ''; ?>><?php _e('10%', 'amazon-auto-links'); ?></option>
				<option value="20" <?php echo $strValue == '20' ? 'Selected' : ''; ?>><?php _e('20%', 'amazon-auto-links'); ?></option>
				<option value="30" <?php echo $strValue == '30' ? 'Selected' : ''; ?>><?php _e('30%', 'amazon-auto-links'); ?></option>
				<option value="50" <?php echo $strValue == '50' ? 'Selected' : ''; ?>><?php _e('50%', 'amazon-auto-links'); ?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('The percentage that the associate ID is altered with the plugin developers\'.', 'amazon-auto-links'); ?> )</font>
			</td>
		</tr>
		<?php // syntax stylizing fixer '
	}	
	function field_element_donate($numTabnum, $strValue="") {
	
		// called from form_generaloptions()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][donate]';
		$strValue = $strValue ? $strValue : $this->oOption->generaldefaultoptions['donate'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Have you donated?', 'amazon-auto-links'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
				<option value="0" <?php echo $strValue == '0' ? 'Selected' : ''; ?>><?php _e('Not Yet', 'amazon-auto-links'); ?></option>
				<option value="1" <?php echo $strValue == '1' ? 'Selected' : ''; ?>><?php _e('Yes, I have', 'amazon-auto-links'); ?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('Please conside donation.', 'amazon-auto-links'); ?> )</font>							
				<?php if ($strValue == 0) _e('Please consider donation.', 'amazon-auto-links'); ?>
			</td>
		</tr>
		<?php
	}	
	function field_element_blacklist_by_ASIN($numTabnum, $strValue="", $strWarning="") {
	
		// called from form_generaloptions()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][blacklist]';
		$strValue = $strValue ? $strValue : $this->oOption->generaldefaultoptions['blacklist'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Black List', 'amazon-auto-links'); ?>&nbsp;<?php _e('by ASIN', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" size="80" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  /><br />&nbsp;
				<font color="#666">( <?php _e('Enter ASINs that are not to be displayed, separated by commas.', 'amazon-auto-links'); ?> e.g. 12345678901, B001AAAA0A )</font>							
			</td>
		</tr>
		<?php
	}
	function field_element_blacklist_by_title($numTabnum, $strValue="", $strWarning="") {
	
		// called from form_generaloptions()
		// since v1.1.6
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][blacklist_title]';
		$strValue = $strValue ? $strValue : $this->oOption->generaldefaultoptions['blacklist_title'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Black List', 'amazon-auto-links'); ?>&nbsp;<?php _e('by Title', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" size="80" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  /><br />&nbsp;
				<font color="#666">( <?php _e('Enter strings, separated by commas so that product links whose title contains them will not be displayed.', 'amazon-auto-links'); ?> e.g. adult, XXX )</font>
			</td>
		</tr>
		<?php
	}	
	function field_element_blacklist_by_description($numTabnum, $strValue="", $strWarning="") {
	
		// called from form_generaloptions()
		// since v1.1.6
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][blacklist_description]';
		$strValue = $strValue ? $strValue : $this->oOption->generaldefaultoptions['blacklist_description'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Black List', 'amazon-auto-links'); ?>&nbsp;<?php _e('by Description', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" size="80" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  /><br />&nbsp;
				<font color="#666">( <?php _e('Enter strings, separated by commas so that product links whose description text contains them will not be displayed.', 'amazon-auto-links'); ?> e.g. adult, XXX )</font>
			</td>
		</tr>
		<?php
	}		
	function field_element_cloakquery($numTabnum, $strValue="", $strWarning="") {
	
		// called from form_generaloptions()
		// since v1.0.9
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][cloakquery]';
		$strValue = !empty($strValue) ? $strValue : $this->oOption->generaldefaultoptions['cloakquery'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Cloak URL Query Parameter', 'amazon-auto-links'); ?></th>
			<td>
				<input type="text" size="20" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  />
				<br />&nbsp;<font color="#666">( 
				<?php _e('Define the query parameter for URL cloaking.', 'amazon-auto-links'); ?>
				&nbsp;
				<?php
				_e('Default: ', 'amazon-auto-links'); 
				echo $this->oOption->generaldefaultoptions['cloakquery']; 
				?>  
				)</font>	
			</td>
		</tr>
		<?php
	}
	function field_element_prefetch($numTabnum, $numValue="") {
	
		// called from form_generaloptions()
		// since v1.1.1
		// sets whether the prefetch category lists to on or off.
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][prefetch]';
		$numValue = $numValue == "" ? $this->oOption->generaldefaultoptions['prefetch'] : $numValue;
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Prefetch Category Lists', 'amazon-auto-links'); ?></th>
			<td>
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 1 ? 'Checked' : '' ?> value="1"> <?php _e('On' ,'amazon-auto-links'); ?> &nbsp;&nbsp;&nbsp;
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 0 ? 'Checked' : '' ?> value="0"> <?php _e('Off' ,'amazon-auto-links'); ?>&nbsp;&nbsp;
				(<font color="#666"><?php _e('Default: On.', 'amazon-auto-links');?>&nbsp;<?php _e('Set it off if links do not appear in the preview in some categories.', 'amazon-auto-links');?></font> )<br />
			</td>
		</tr>	
		<?php			
	}	
	function field_element_license( $numTabnum, $strValue="" ) {}	// since v1.1.9, for Pro
}
?>