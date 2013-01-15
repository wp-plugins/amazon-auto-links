<?php
class AmazonAutoLinks_Forms_ {

	/*  
		Warning: Never use update_option() in this class.
		this class is to just display form elements, not manipulating option values.
		
		Todo: do not instantiate the option object but make a parameter to the constructor so that we save more memory
	*/
	
	public $classver = 'standard';
	protected $pluginkey = 'amazonautolinks';

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
	function verifynonce_in_tab($numTabNumber, $action='', $name='') {
	
		// veryfies nonce with the given options and also checks the specified hidden tag field is sent
		// the $_POST array's format is specifically designed for this plugin, $_POST[pluginkey][tabNNN][tabNNN_submitted], where NNN is the tab number.
		if (!$action) 
			$action = $this->pluginkey;	
		if (!name)
			$name = 'nonce';
			
		if ( function_exists('wp_verify_nonce') ) {
			if(isset($_POST[$this->pluginkey]['tab' . $numTabNumber]['tab' . $numTabNumber . '_submitted']) && !wp_verify_nonce($_POST[$name], $action)){
				return false;
			} else  {
				return true;
			}	
		} else 
			return null;
	}
	function embedhiddenfield($pluginkey, $tabnum) {
	
		// embeds a hidden input field with the given options, specifically formatted to the plugin, Amazon Auto Links
		// the format of the name :  [$pluginkey][tabNNN][tabNNN_submitted]  
		// NNN is the tab number
		?>
			<input type="hidden" name="<?php echo $pluginkey; ?>[tab<?php echo $tabnum; ?>][tab<?php echo $tabnum; ?>_submitted]" value="1" />
		<?php
	}	
	function field_submitbutton($fieldname, $dislayedvalue) {
	
		// note that this does not have a form tag.
		?>
		<input type="submit" class="button-primary" name="<?php echo $fieldname; ?>" value="<?php echo $dislayedvalue; ?>" />
		<?php
	}	
	function form_submitbutton($numTab, $strNameKey, $strDisplayValue="", $strNonceKey="nonce", $bFormTag=True) {
	
		// this is a single form button which links to the specified tab numbered page.
		// note that it includes a form tab 
		$strDisplayValue = $strDisplayValue ? $strDisplayValue : __("Go Back", 'amazonautolinks');
		if ($bFormTag)
			echo '<form method="post" action="?page=' . $this->pageslug . '&tab=' . $numTab . '" >';
		$this->embednonce($this->pluginkey, 'nonce');
		$this->embedhiddenfield($this->pluginkey, $numTab); 
		$this->field_submitbutton($this->pluginkey . '[tab' . $numTab . '][' . $strNameKey . ']', $strDisplayValue);
		if ($bFormTag)
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
			$arrErrors['unitlabel'] .= ' ' . __('The unit label cannot be empty.', 'amazonautolinks');
			$bInvalid = true;	
		}			

		// if the method is called from the 'Edit Unit' page and the prior unit label is the same
		if ($mode != 'new' && trim($arrOptions['unitlabel']) == trim($arrOptions['prior_unitlabel'])) {	
			// no need to check because the unit label is not edited.
		} else {
		// otherwise, check if the same unit label already exists
			$arrRootOptions = get_option($this->pluginkey);
			foreach($arrRootOptions['units'] as $strUnitID => $arrUnitOption) {
				$unitlabel = $arrUnitOption['unitlabel'];
				if ($unitlabel == $arrOptions['unitlabel']) {
					$arrErrors['unitlabel'] .= trim(' ' . __('The unit label already exists:', 'amazonautolinks') . $unitlabel) . ' ' ; 
					$bInvalid = true;
					break;
				}
			}		
		}

		// check: tab100 -> associateid
		if (strlen(trim($arrOptions['associateid'])) == 0) {
			$arrErrors['associateid'] .= ' ' . __('Associate ID cannot be empty.', 'amazonautolinks');
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
		<p><?php _e('In order to set up the following options, please upgrade to the <a href="' . $strCurrURL . '">pro version</a>.', 'amazonautolinks'); ?></p>
	<?php
	}
	function form_setunit($numTabNum, $arrOptionsToDisplay="", $arrErrors="") {
	
		// called from admin_tab100() and admin_tab202()
		// if the option is not set, put the default value
		// it's premised that this method is called inside a form tag. e.g. <form> ..  $oClass->form_setunit() .. </form>
		if (!is_array($arrOptionsToDisplay)) 
			$arrOptionsToDisplay = $this->oOption->unitdefaultoptions;
		if (!is_array($arrErrors)) 
			$arrErrors = array();
					
		?>	
		<table class="form-table" style="clear:left; width:auto;">
			<tbody>
				<?php $this->field_element_unitlabel($numTabNum, $arrOptionsToDisplay['unitlabel'], $arrErrors['unitlabel']); ?>
				<?php $this->field_element_country($numTabNum, $arrOptionsToDisplay['country']); ?>
				<?php $this->field_element_associateid($numTabNum, $arrOptionsToDisplay['associateid'], $arrErrors['associateid']); ?>
				<?php $this->field_element_numberofitems($numTabNum, $arrOptionsToDisplay['numitems']); ?>
				<?php $this->field_element_imagesize($numTabNum, $arrOptionsToDisplay['imagesize']); ?>
				<?php $this->field_element_sortorder($numTabNum, $arrOptionsToDisplay['sortorder']); ?>
				<?php $this->field_element_adtypes($numTabNum, $arrOptionsToDisplay['feedtypes']); ?>
				<?php $this->field_element_nosim($numTabNum, $arrOptionsToDisplay['nosim']); ?>
				<?php $this->field_element_insert($numTabNum, $arrOptionsToDisplay['insert']); ?>
				<?php $this->field_element_titlelength($numTabNum, $arrOptionsToDisplay['titlelength']); ?>
				<?php $this->field_element_linkstyle($numTabNum, $arrOptionsToDisplay['linkstyle']); ?>
				<?php $this->field_element_credit($numTabNum, $arrOptionsToDisplay['credit']); ?>
				<?php $this->field_element_urlcloaking($numTabNum, $arrOptionsToDisplay['urlcloak']); ?>
				<?php // $this->field_element_widget($numTabNum, $arrOptionsToDisplay['widget']); // depricated ?>
			</tbody>
		</table>
		<?php $this->oUserAd->ShowTextAd(); // oUserAd must be instantiated prior to this method call ?>
		<p class="submit">
			<?php 
				$strFieldName = $this->pluginkey . '[tab' . $numTabNum . '][proceedbutton]';
				$this->field_submitbutton($strFieldName, __('Proceed', 'amazonautolinks')); 
				if ($numTabNum == 202) {
					$strFieldNameSaveButton = $this->pluginkey . '[tab' . $numTabNum . '][savebutton]';
					$this->field_submitbutton($strFieldNameSaveButton, __('Save', 'amazonautolinks')); 
				}
			?>
		</p>
		<h3><?php _e('Advanced Option', 'amazonautolinks'); ?></h3>
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
			$this->field_submitbutton($strFieldName, __('Proceed', 'amazonautolinks')); 
			if ($numTabNum == 202) 
				$this->field_submitbutton($strFieldNameSaveButton, __('Save', 'amazonautolinks')); 
			?>
		</p>
		<?php
	}
	
	/* Methods for $this->form_setunit() -- the parts of form fields */	
	function field_element_unitlabel($numTabnum, $strValue="", $strWarning="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][unitlabel]';
		$strPriorUnitLabel = $this->pluginkey . '[tab' . $numTabnum . '][prior_unitlabel]';
		$strValue = $strValue ? $strValue : $this->oOption->unitdefaultoptions['unitlabel'];
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Unit Label', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" size="30" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  />
				<input type="hidden" name="<?php echo $strPriorUnitLabel; ?>" value="<?php echo $strValue; ?>" />
				
				&nbsp;<font color="#666">( <?php _e('String value to identify the ad unit.', 'amazonautolinks'); ?> e.g. unit-1 )</font>							
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
			<th scope="row"><?php _e('Country', 'amazonautolinks'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
				<option value="AT" <?php echo $strValue == 'AT' ? 'Selected' : ''; ?>>AT - <?php _e('Austria', 'amazonautolinks'); ?></option>
				<option value="CA" <?php echo $strValue == 'CA' ? 'Selected' : ''; ?>>CA - <?php _e('Canada', 'amazonautolinks'); ?></option>
				<option value="CN" <?php echo $strValue == 'CN' ? 'Selected' : ''; ?>>CN - <?php _e('China', 'amazonautolinks'); ?></option>
				<option value="FR" <?php echo $strValue == 'FR' ? 'Selected' : ''; ?>>FR - <?php _e('France', 'amazonautolinks'); ?></option>
				<option value="DE" <?php echo $strValue == 'DE' ? 'Selected' : ''; ?>>DE - <?php _e('Germany', 'amazonautolinks'); ?></option>
				<option value="IT" <?php echo $strValue == 'IT' ? 'Selected' : ''; ?>>IT - <?php _e('Italy', 'amazonautolinks'); ?></option>
				<option value="JP" <?php echo $strValue == 'JP' ? 'Selected' : ''; ?>>JP - <?php _e('Japan', 'amazonautolinks'); ?></option>
				<option value="UK" <?php echo $strValue == 'UK' ? 'Selected' : ''; ?>>UK - <?php _e('United Kingdom', 'amazonautolinks'); ?></option>
				<option value="ES" <?php echo $strValue == 'ES' ? 'Selected' : ''; ?>>ES - <?php _e('Spain', 'amazonautolinks'); ?></option>
				<option value="US" <?php echo $strValue == 'US' ? 'Selected' : ''; ?>>US - <?php _e('United States', 'amazonautolinks'); ?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('Select the country for the associate ID.', 'amazonautolinks'); ?> )</font>							
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
			<th scope="row"><?php _e('Associate ID', 'amazonautolinks'); ?></th>
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
			<th scope="row"><?php _e('Number of Items to Show', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" size="30" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666">( <?php _e('Default', 'amazonautolinks');?>: <?php echo $this->oOption->unitdefaultoptions['numitems']; ?> <?php _e('Max', 'amazonautolinks'); ?> : 10 )</font>	
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
			<th scope="row"><?php _e('Image Size', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666"><?php _e('in pixel.', 'amazonautolinks');?> ( <?php _e('Accepts upto 500. Set 0 for no image.', 'amazonautolinks');?> <?php _e('Default', 'amazonautolinks');?> : <?php echo $this->oOption->unitdefaultoptions['imagesize']; ?> )</font>
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
			<th scope="row"><?php _e('Sort Order', 'amazonautolinks'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
					<option value="date" <?php echo $strValue == 'date' ? 'Selected' : '' ?> ><?php _e('Date', 'amazonautolinks');?></option>
					<option value="title" <?php echo $strValue == 'title' ? 'Selected' : '' ?> ><?php _e('Title', 'amazonautolinks');?></option>
					<option value="random" <?php echo $strValue == 'random' ? 'Selected' : '' ?> ><?php _e('Random', 'amazonautolinks');?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('Defines how the product links are sorted.', 'amazonautolinks');?> )</font>							
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
				<?php _e('Ad Types', 'amazonautolinks'); ?><br /><br />
				<span style="margin-left:1em; padding-right:2em; color:#666;"><?php _e('It is recommended to check only a few for faster page loading.', 'amazonautolinks'); ?><br />
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>[bestsellers]" 		value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[hotnewreleases]"	value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[moverandshakers]"	value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[toprated]" 			value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[mostwishedfor]"		value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[giftideas]" 		value="0" />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[bestsellers]" 	value="1" <?php echo $arrValue['bestsellers'] ? 'Checked' : '' ?>> <?php _e('Best Sellers', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[hotnewreleases]"	value="1" <?php echo $arrValue['hotnewreleases'] ? 'Checked' : '' ?>> <?php _e('Hot New Releases', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[moverandshakers]" value="1" <?php echo $arrValue['moverandshakers'] ? 'Checked' : '' ?>> <?php _e('Mover & Shakers', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[toprated]" 		value="1" <?php echo $arrValue['toprated'] ? 'Checked' : '' ?>> <?php _e('Top Rated', 'amazonautolinks');?>&nbsp;&nbsp;&nbsp;&nbsp;<font color="#666">( <?php _e('This may not be available in some countries.', 'amazonautolinks'); ?>)</font><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[mostwishedfor]"	value="1" <?php echo $arrValue['mostwishedfor'] ? 'Checked' : '' ?>> <?php _e('Most Wished For', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[giftideas]" 		value="1" <?php echo $arrValue['giftideas'] ? 'Checked' : '' ?>> <?php _e('Gift Ideas', 'amazonautolinks');?><br />
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
				<?php _e('Direct Link Bonus', 'amazonautolinks'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo $bValue ? 'Checked' : '' ?>> ref=nosim <span style="color:#666;">( <?php _e('Inserts ref=nosim in the link url. For more information, visit the following page:', 'amazonautolinks'); ?> <a href='https://affiliate-program.amazon.co.uk/gp/associates/help/t5/a21'>amazon associates</a> )</span>
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
				<?php _e('Where To Insert', 'amazonautolinks'); ?><br /><br />
				<span style="margin-left:1em; padding-right:2em; color:#666;"><?php _e('Check where items to be inserted.', 'amazonautolinks'); ?><br />
			</th>
			<td>		
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>[postabove]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[postbelow]"			 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[excerptabove]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[excerptbelow]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedabove]"			 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedbelow]" 		 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedexcerptabove]"	 value="0" />
				<input type="hidden" name="<?php echo $strFieldName; ?>[feedexcerptbelow]"	 value="0" />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[postabove]" 		 value="1" <?php echo $arrValues['postabove'] ? 		'Checked' : '' ?>> <?php _e('Above Post', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[postbelow]"		 value="1" <?php echo $arrValues['postbelow'] ? 		'Checked' : '' ?>> <?php _e('Below Post', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[excerptabove]" 	 value="1" <?php echo $arrValues['excerptabove'] ? 		'Checked' : '' ?>> <?php _e('Above Excerpt', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[excerptbelow]" 	 value="1" <?php echo $arrValues['excerptbelow'] ? 		'Checked' : '' ?>> <?php _e('Below Excerpt', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedabove]"		 value="1" <?php echo $arrValues['feedabove'] ? 		'Checked' : '' ?>> <?php _e('Above Feed Item', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedbelow]" 		 value="1" <?php echo $arrValues['feedbelow'] ? 		'Checked' : '' ?>> <?php _e('Below Feed Item', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedexcerptabove]" value="1" <?php echo $arrValues['feedexcerptabove'] ?  'Checked' : '' ?>> <?php _e('Above Feed Excerpt', 'amazonautolinks');?><br />
				<input type="checkbox" name="<?php echo $strFieldName; ?>[feedexcerptbelow]" value="1" <?php echo $arrValues['feedexcerptbelow'] ?  'Checked' : '' ?>> <?php _e('Below Feed Excerpt', 'amazonautolinks');?><br />
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
				<?php _e('Widget', 'amazonautolinks'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo $bValue ? 'Checked' : '' ?>> <?php _e('Creates a sidebar/footer widget for this unit. It will be added via the Widget section.', 'amazonautolinks'); ?>
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
			<th scope="row"><?php _e('Title Length', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666"><?php _e('It is used to prevent a broken layout caused by a very long product title. Set -1 for no limit.', 'amazonautolinks');?> <?php _e('Default', 'amazonautolinks');?> : <?php echo $this->oOption->unitdefaultoptions['titlelength']; ?></font>
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
			<th scope="row"><?php _e('Link Style', 'amazonautolinks'); ?></th>
			<td>
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 1 ? 'Checked' : '' ?> value="1"> http://www.amazon.[domain-suffix]/[product-name]/dp/[asin]/ref=[...]?tag=[associate-id] &nbsp;(<font color="#666"><?php _e('Default', 'amazonautolinks');?></font> )<br />
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
				<?php _e('Credit Link', 'amazonautolinks'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo !empty($bValue) ? 'Checked' : '' ?>> <?php _e('Inserts the credit link at the end of the unit.', 'amazonautolinks'); ?>
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
				<?php _e('URL Cloak', 'amazonautolinks'); ?>						
			</th>
			<td>
				<!-- the hidden fields before the checkboxes are necessary to send unchecked values -->
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="0" />			
				<input type="checkbox" name="<?php echo $strFieldName; ?>" value="1"  <?php echo !empty($bValue) ? 'Checked' : '' ?> /> 
				<?php 
					_e('Obfuscates product links.', 'amazonautolinks');
					echo ' e.g. ' . site_url('?' . rawurlencode($strCloakQuery) . '=' . $this->oAALfuncs->urlencrypt("http://www.michaeluno.jp"));
				?>
			</td>
		</tr>
	<?php	
	}
	function field_element_cacheexpiration($numTabnum, $numValue="") {
	
		// called from form_setunit()
		$strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][cacheexpiration]';	
		$numValue = $numValue ? $numValue : $this->oOption->unitdefaultoptions['cacheexpiration'];
	?>
		<tr valign="top">
			<th scope="row"><?php _e('Cache Expiration', 'amazonautolinks'); ?><br />
			</th>
			<td>
				<input disabled style="background-color: #eee; color: #999;" type="text" size="30" value="<?php echo $numValue ; ?>" />
				<input type="hidden" name="<?php echo $strFieldName; ?>" value="<?php echo $numValue; ?>" />
				&nbsp;<font color="#666">( <?php _e('in seconds.', 'amazonautolinks'); ?> <?php _e('Default', 'amazonautolinks'); ?> : <?php echo $this->oOption->unitdefaultoptions['cacheexpiration']; ?> )</font>	
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
			<?php _e('Container Format', 'amazonautolinks'); ?><br /><br />
			<span style="margin-left:1em; color:#666;">%item% - <?php _e('item', 'amazonautolinks'); ?><br />
			</th>
			<td>
				<textarea disabled style="background-color: #eee; color: #999;" rows="4" cols="80" ><?php echo $strValue; ?></textarea>
				<textarea style="display:none" name="<?php echo $strFieldName; ?>" rows="4" cols="80"><?php echo $strValue; ?></textarea>
			</td>
		</tr>			
		<tr valign="top">
			<td style="margin-top:0; padding-top:0;">
				<?php _e('Default Value', 'amazonautolinks'); ?>:<br />
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
				<?php _e('Item Format', 'amazonautolinks'); ?><br /><br />
				<div style="margin-left:1em; color:#666;">
					%link% - <?php _e('link url', 'amazonautolinks'); ?><br />
					%title% - <?php _e('title', 'amazonautolinks'); ?><br />
					%img% - <?php _e('thumbnail', 'amazonautolinks'); ?><br />
					%htmldescription%<br />- <?php _e('description with HTML tags', 'amazonautolinks'); ?><br />							
					%textdescription%<br />- <?php _e('description without HTML tags', 'amazonautolinks'); ?><br />							
				</div>
			</th>
			<td>
				<textarea disabled style="background-color: #eee; color: #999;" rows="4" cols="80" ><?php echo $strValue; ?></textarea>
				<textarea style="display:none" name="<?php echo $strFieldName; ?>" rows="4" cols="80"><?php echo $strValue; ?></textarea>
			</td>
		</tr>	
		<tr valign="top" >							
			<td style="margin-top:0; padding-top:0;">
				<?php _e('Default Valie', 'amazonautolinks'); ?>:<br />
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
				<?php _e('Image Format', 'amazonautolinks'); ?><br /><br />
				<div style="margin-left:1em; color:#666;">
					%imgurl% - <?php _e('imgurl', 'amazonautolinks'); ?><br />
					%link% - <?php _e('link url', 'amazonautolinks'); ?><br />
					%title% - <?php _e('title', 'amazonautolinks'); ?><br />					
					%textdescription%<br />- <?php _e('description without tags', 'amazonautolinks'); ?><br />						
				</div>
			</th>
			<td>
				<textarea disabled style="background-color: #eee; color: #999;" rows="4" cols="80"><?php echo $strValue; ?></textarea>
				<textarea style="display:none" name="<?php echo $strFieldName; ?>" rows="4" cols="80"><?php echo $strValue; ?></textarea>
			</td>
		</tr>	
		<tr valign="top" >							
			<td style="margin-top:0; padding-top:0;">
				<?php _e('Default Valie', 'amazonautolinks'); ?>:<br />
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
		if (!is_array($arrOptionsToDisplay)) 
			$arrOptionsToDisplay = $this->oOption->generaldefaultoptions;
		if (!is_array($arrErrors)) 
			$arrErrors = array();
		?>	
		<table class="form-table" style="clear:left; width:auto;">
			<tbody>
				<?php $this->field_element_support($numTabNum, $arrOptionsToDisplay['supportrate']); ?>
				<?php $this->field_element_blacklist_by_ASIN($numTabNum, $arrOptionsToDisplay['blacklist']); ?>
				<?php $this->field_element_blacklist_by_title($numTabNum, $arrOptionsToDisplay['blacklist_title']); ?>
				<?php $this->field_element_blacklist_by_description($numTabNum, $arrOptionsToDisplay['blacklist_description']); ?>
				<?php $this->field_element_cloakquery($numTabNum, $arrOptionsToDisplay['cloakquery']); ?>
				<?php $this->field_element_prefetch($numTabNum, $arrOptionsToDisplay['prefetch']); ?>
			</tbody>
		</table>
		<p class="submit">
			<?php 
				$strFieldName = $this->pluginkey . '[tab' . $numTabNum . '][savebutton]';
				$this->field_submitbutton($strFieldName, __('Save', 'amazonautolinks')); 
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
			<th scope="row"><?php _e('Support Rate', 'amazonautolinks'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
				<option value="0" <?php echo $strValue == '0' ? 'Selected' : ''; ?>><?php _e('0%', 'amazonautolinks'); ?></option>
				<option value="10" <?php echo $strValue == '10' ? 'Selected' : ''; ?>><?php _e('10%', 'amazonautolinks'); ?></option>
				<option value="20" <?php echo $strValue == '20' ? 'Selected' : ''; ?>><?php _e('20%', 'amazonautolinks'); ?></option>
				<option value="30" <?php echo $strValue == '30' ? 'Selected' : ''; ?>><?php _e('30%', 'amazonautolinks'); ?></option>
				<option value="50" <?php echo $strValue == '50' ? 'Selected' : ''; ?>><?php _e('50%', 'amazonautolinks'); ?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('The percentage that the associate ID is altered with the plugin developers\'.', 'amazonautolinks'); ?> )</font>
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
			<th scope="row"><?php _e('Have you donated?', 'amazonautolinks'); ?></th>
			<td>
				<select name="<?php echo $strFieldName; ?>">
				<option value="0" <?php echo $strValue == '0' ? 'Selected' : ''; ?>><?php _e('Not Yet', 'amazonautolinks'); ?></option>
				<option value="1" <?php echo $strValue == '1' ? 'Selected' : ''; ?>><?php _e('Yes, I have', 'amazonautolinks'); ?></option>
				</select>		
				&nbsp;<font color="#666">( <?php _e('Please conside donation.', 'amazonautolinks'); ?> )</font>							
				<?php if ($strValue == 0) _e('Please consider donation.', 'amazonautolinks'); ?>
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
			<th scope="row"><?php _e('Black List', 'amazonautolinks'); ?>&nbsp;<?php _e('by ASIN', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" size="80" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  /><br />&nbsp;
				<font color="#666">( <?php _e('Enter ASINs that are not to be displayed, separated by commas.', 'amazonautolinks'); ?> e.g. 12345678901, B001AAAA0A )</font>							
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
			<th scope="row"><?php _e('Black List', 'amazonautolinks'); ?>&nbsp;<?php _e('by Title', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" size="80" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  /><br />&nbsp;
				<font color="#666">( <?php _e('Enter strings, separated by commas so that product links whose title contains them will not be displayed.', 'amazonautolinks'); ?> e.g. adult, XXX )</font>
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
			<th scope="row"><?php _e('Black List', 'amazonautolinks'); ?>&nbsp;<?php _e('by Description', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" size="80" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  /><br />&nbsp;
				<font color="#666">( <?php _e('Enter strings, separated by commas so that product links whose description text contains them will not be displayed.', 'amazonautolinks'); ?> e.g. adult, XXX )</font>
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
			<th scope="row"><?php _e('Cloak URL Query Parameter', 'amazonautolinks'); ?></th>
			<td>
				<input type="text" size="20" name="<?php echo $strFieldName; ?>" value="<?php echo $strValue; ?>"  />
				<br />&nbsp;<font color="#666">( 
				<?php _e('Define the query parameter for URL cloaking.', 'amazonautolinks'); ?>
				&nbsp;
				<?php
				_e('Default: ', 'amazonautolinks'); 
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
			<th scope="row"><?php _e('Prefetch Category Lists', 'amazonautolinks'); ?></th>
			<td>
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 1 ? 'Checked' : '' ?> value="1"> <?php _e('On' ,'amazonautolinks'); ?> &nbsp;&nbsp;&nbsp;
				<input type="radio" name="<?php echo $strFieldName; ?>" <?php echo $numValue == 0 ? 'Checked' : '' ?> value="0"> <?php _e('Off' ,'amazonautolinks'); ?>&nbsp;&nbsp;
				(<font color="#666"><?php _e('Default: On.', 'amazonautolinks');?>&nbsp;<?php _e('Set it off if links do not appear in the preview in some categories.', 'amazonautolinks');?></font> )<br />
			</td>
		</tr>	
		<?php			
	}	
}
?>