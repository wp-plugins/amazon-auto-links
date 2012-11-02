<?php
class AmazonAutoLinks_Options_ {

	/*
		this class is for handling form subbmittion and plugin options.
	*/
	
	public $classver = 'standard';
	public $arrOptions = array();
	protected $pluginname = 'Amazon Auto Links';
	protected $pluginkey = 'amazonautolinks';
	protected $pageslug = 'amazonautolinks';
	protected $textdomain = 'amazonautolinks';	
	public $unitdefaultoptions = array(
		'id'		 		=> '',	 // uniqid() will be inserted when creating the unit. This is the save value as the key string of the unit option element.
		'unitlabel' 		=> '',	 // this is used with the main function, shortcode, and background events in order to fetch feeds.
		'country' 			=> 'US', // <--- TODO: this should be dynamic corresponding to the user's locale or the previous input value
		'associateid' 		=> '',	 // <--  TODO: needs to investigate a way to remember user's previous input
		'containerformat'	=> '<div class="amazon-auto-links">%items%</div>',
		'itemformat' 		=> '<a href="%link%" title="%title%: %textdescription%" rel="nofollow">%img%</a><h5><a href="%link%" title="%title%: %textdescription%" rel="nofollow">%title%</a></h5><p>%htmldescription%</p>',
		'imgformat'			=> '<img src="%imgurl%" alt="%textdescription%" />',
		'imagesize' 		=> 160,  // up to 500. 0 means no image.
		'sortorder' 		=> 'random',	// random / title / date
		'feedtypes' 		=> array(	'bestsellers' => True, 
										'hotnewreleases' => False,
										'moverandshakers' => False,
										'toprated' => False,
										'mostwishedfor' => False,
										'giftideas' => False),
		'cacheexpiration'	=> 43200,
		'numitems' 			=> 10,
		'nosim'				=> false,
		'mblang'			=> 'uni',
		'countryurl'		=> 'http://www.amazon.com/gp/bestsellers/',
		'insert'			=> array(	'postabove' 		=> False,
										'postbelow'			=> False,
										'excerptabove'		=> False,
										'excerptbelow'		=> False,
										'feedabove'			=> False,
										'feedbelow'			=> False,
										'feedexcerptabove'	=> False,
										'feedexcerptbelow'	=> False	),
		'modifieddate'		=> '',	// used in AmazonAutoLinks_UserAds to store the creation/modified date of the unit
		'feedurls'			=> '',	// used in AmazonAutoLinks_UserAds to store temporary feed urls 
		'titlelength'		=> -1,
		'linkstyle'			=> 1,
		'credit'			=> True,
		'urlcloak'			=> False	// sinve v1.0.9
	);	
	public $generaldefaultoptions = array(
		'supportrate'		=> 10,
		'blacklist'			=> '',
		'donate'			=> 0,
		'cloakquery'		=> 'productlink',
		'prefetch'			=> 1
	);		
	public $arrCountryURLs = array(
		'AT'	=> 'http://www.amazon.de/gp/bestsellers/',
		'CA'	=> 'http://www.amazon.ca/gp/bestsellers/',
		'CN'	=> 'http://www.amazon.cn/gp/bestsellers/',
		'FR'	=> 'http://www.amazon.fr/gp/bestsellers/',
		'DE'	=> 'http://www.amazon.de/gp/bestsellers/',
		'IT'	=> 'http://www.amazon.it/gp/bestsellers/',
		'JP'	=> 'http://www.amazon.co.jp/gp/bestsellers/',
		'UK'	=> 'http://www.amazon.co.uk/gp/bestsellers/',
		'ES'	=> 'http://www.amazon.es/gp/bestsellers/',
		'US'	=> 'http://www.amazon.com/gp/bestsellers/',
	);
	public $arrCountryLang = array(
		'AT'	=> 'uni',
		'CA'	=> 'uni',
		'CN'	=> 'uni',
		'FR'	=> 'uni',
		'DE'	=> 'uni',
		'IT'	=> 'uni',
		'JP'	=> 'ja',
		'UK'	=> 'en',
		'ES'	=> 'uni',
		'US'	=> 'en',	
	);	
	protected $arrTokens = array(
		'AT' => '[+GV7Kld1CT12Pkq5SCF+EjKpxRM499p5hmcN3URWLPM=]',
		'CA' => '[QcHLqKhNIev5ZPNyYvcMiv+KwAdWikcIPUKVIsermRA=]',
		'CN' => '[+4AoPmHEQJZnnbCMSnFTGZHU8kkOZR5+dIS7Z0PgzRk=]',
		'FR' => '[4DRwWKjxuHK9PFZgsBaImVJl6Ab/tC1ciHARMgtjhaI=]',
		'DE' => '[+GV7Kld1CT12Pkq5SCF+EjKpxRM499p5hmcN3URWLPM=]',
		'IT' => '[eKrg+Z0tCshDSBIBrLkZoUj7PBVSp6uW5THU4UMBphI=]',
		'JP' => '[sQfjvJ1G66k0xnlG8frmcTnkgIEBzL3sjWbiRQ6QPAw=]',
		'UK' => '[zcnWGGKqeX71eM74JBAyyo+z0QtaKFhuFGit5Kt9bqA=]',
		'ES' => '[NiN6GUQ/AvRkTbtgl4zD6FiSNRpeygeJHgRpJPIBiGo=]',
		'US' => '[fRmuq3rruO3Tw8y29lU1m6mxwAZ1XxxyDOD1L2UvIU4=]'
	);
	function __construct($pluginkey) {
	
		// Include classes
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($pluginkey);
			
		
		// set up properties
		$this->pluginkey = $pluginkey;
		$this->textdomain = $pluginkey;	
		$this->pageslug = $pluginkey;	
		
		$this->load_settings();
	}
	function get_token($country) {
		if (isset($this->arrTokens[$country]))
			return $this->oAALfuncs->decrypt($this->arrTokens[$country]);
	}
	function load_settings() {
	
		// create an option array, if it is the first time of loading this plugin
		$this->arrOptions = get_option( $this->pluginkey );	
		$this->arrOptions = is_array($this->arrOptions) ? $this->arrOptions : array();
		$arrOption_default = array(
			"tab100"	=> array(),		// for tab 100
			"tab101"	=> array(),		// for tab 101
			"tab200"	=> array(),		// for tab 200
			"tab201"	=> array(),		// for tab 201; almost not used, it's just a preview page and has one Go Back button
			"tab202"	=> array(),		// for tab 202
			"tab203"	=> array(),		// for tab 203
			"tab300"	=> array(),		// for tab 300
			"newunit" 	=> array(),		// creating unit page: tab 100, tab 101
			"editunit" 	=> array(),		// editing unit page: tab 202, tab 203
			"units"		=> array(),		// stores created unit info.
			"general"	=> $this->generaldefaultoptions,		// stores the general options
		);
		$this->arrOptions = array_merge($arrOption_default, $this->arrOptions);
		$this->set_support_rate();
		$this->update();
	}		
	function set_support_rate() {
		return;	// do nothing for the standard version
	}
	/* Used in Admin Pages */
	function set_new_unit($arrOptions='') {	
		if (!$arrOptions) {
			$this->arrOptions['newunit'] = $this->unitdefaultoptions;			
			$this->arrOptions['tab101']['cameback'] = False;		// this flag is used for pseudo session.
			$this->arrOptions['tab100']['errors'] = False;	
		}
		$this->update();
	}	
	
	/* Used in the Category Selection Page */
	function insert_unit($strUnitID, $NewOrEdit) {		// since v1.0.7 $strUnitLabel was changed to $strUnitID
		$this->arrOptions['units'][$strUnitID] = $this->arrOptions[$NewOrEdit];
		return $this->update();
	}
	function add_category($NewOrEdit, $strCatName, $arrCatInfo) {
	
		// adds category to the preview option array, either $options['newunit'] or $options['editunit'].
		// This is called from the preceeding page of create/edit unit.
		// returns the number of current added categories
		
		$numCategories = count($this->arrOptions[$NewOrEdit]['categories']);
		if ($numCategories >= 3) 
			return -1;
		$this->arrOptions[$NewOrEdit]['categories'][$strCatName] = $arrCatInfo;
		$this->update();
		return count($this->arrOptions[$NewOrEdit]['categories']);
	}		
	function delete_categories($NewOrEdit, $arrCategories) {
	
		// deletes categories passed as an array, $arrCategories
		// this method is called from the select category page. Whether the option key, EditUnit or NewUnit must be specified.
		// returns the count of the remaining categories
		foreach ($arrCategories as $key => $value ) {
			if (isset($key)) 	// if the check box of category breadcrumb is checked
				unset($this->arrOptions[$NewOrEdit]['categories'][$key]);			
		}				
		$this->update();
		return count($this->arrOptions[$NewOrEdit]['categories']);
	}		
	function get_category_links($NewOrEdit) {
		
		// returns an array containing feed urls of added categories
		$arrLinks = array();
		if (is_array($this->arrOptions[$NewOrEdit]['categories'])) {
			foreach($this->arrOptions[$NewOrEdit]['categories'] as $cat => $catinfo) {
				array_push($arrLinks, $catinfo['feedurl']);
			}		
		}
		return $arrLinks;
	}

	/* New Unit */
	function set_newunit($arrUnitOptions) {
		
		//since v1.0.7, migrated from the AmazonAutoLinks_Admin_ class
		$this->arrOptions['newunit'] = $arrUnitOptions;
		$this->update();
	}	
	
	/* Edit Unit -- used in admin page to save edited unit options */
	function update_editunit($arrUnitOptions) {
	
		// since v1.0.7
		$this->arrOptions['editunit'] = $arrUnitOptions;
		$this->update();
	}
	
	function save_submitted_unitoption_edit($arrSubmittedFormValues) {
		
		// since v1.0.7
		
		// get the Unit ID
		if (empty($this->arrOptions['editunit']['id'])) $this->arrOptions['editunit']['id'] = uniqid(); //for backward compatiblity for v1.0.6 or below
		$strUnitID = $this->arrOptions['editunit']['id'];
		
		// backward compatibility for v1.0.6 or below; in case the unit option key is not saved as its ID 
		if (!is_array($this->arrOptions['units'][$strUnitID])) $this->arrOptions['units'][$strUnitID] = array(); 
		
		// has to merge with the previous options because they have data which the submitted ones don't have such as categories
		$this->arrOptions['units'][$strUnitID] = array_merge($this->arrOptions['units'][$strUnitID], $this->arrOptions['editunit'], $arrSubmittedFormValues);
		
		// for backward compatiblity for v1.0.6 or below, delete the option key of unit label since it is saved as ID in the above line
		$strUnitLabel = $this->arrOptions['units'][$strUnitID]['unitlabel'];
		if (is_array($this->arrOptions['units'][$strUnitLabel])) unset($this->arrOptions['units'][$strUnitLabel]);				
	
		// save it
		$this->update();
	}	

	function store_temporary_editunit_option($strUnitLabel) {
	
		// since v1.0.7, migrated from AmazonAutoLinks_Admin_
		// called from the AmazonAutoLinks_Admin class to store the temporay editing data in the options['editunit'] array.
		// the user modifies this temprorary copied data and saves it if it is validated after pressing the form submit button.
	
		if (is_array($this->arrOptions['units'][$strUnitLabel])) 
			$this->arrOptions['editunit'] =  $this->arrOptions['units'][$strUnitLabel];	// backward compatibility for v1.0.6 or below
		else {
			$strUnitID = $this->get_unitid_from_unitlabel($strUnitLabel);
			$this->arrOptions['editunit'] =  $this->arrOptions['units'][$strUnitID];
		}
		$this->update();
		
	}	
	function delete_unnamed_key($strOptionKey) { 
		
		// since v1.0.7, migrated from AmazonAutoLinks_Admin_
		// deleted unnamed key element from the given option element
		if (array_key_exists('', $this->arrOptions[$strOptionKey])) {	 // if empty key element exists, remove it
			unset($this->arrOptions[$strOptionKey]['']);	
			$this->update();
		}	
	}
	function unset_error_flags($numTab) {
	
		// since v1.0.7, migrated from AmazonAutoLinks_Admin_
		unset($this->arrOptions['tab' . $numTab]['errors']);
		$this->update();
	}
	
	/* Delete Units at Manage Option */
	function delete_units($arrUnitIDs) {
		
		// since v1.0.7, migrated from AmazonAutoLinks_Admin_
		// the passed array has to have key names as the unit option key e.g. Array ( [506382d8377bd] => 1 )
		if (!is_array($arrUnitIDs)) return false;
		
		$i = 0;
		ForEach( $arrUnitIDs as $strUnitID => $v) {			
			unset($this->arrOptions['units'][$strUnitID]);
			$i++;
		}
	
		if ($i > 0) $this->update();
		return $i;
	}
	function clean_unlabeled_units() {
	
		// since v1.0.7, migrated from AmazonAutoLinks_Admin_
		// deletes units with no labels; this occured a few times in debbuging.
		
		$i = 0;
		foreach ($this->arrOptions['units'] as $strUnitID => $arrOptions) {
			if (!$arrOptions['unitlabel']) {
				unset($this->arrOptions['units'][$strUnitID]);
				$i++;
			}
		}
		
		if ($i > 0) $this->update();
		return $i;
	}		
	
	/* for general usage */		
	function set_id($strUnitID) {
		if (empty($this->arrOptions['units'][$strUnitID]['id'])) $this->arrOptions['units'][$strUnitID]['id'] = uniqid();
		$this->update();
	}
	function update() {
	
		// wrap the WordPress function so that this method can be called outside the class
		return update_option($this->pluginkey, $this->arrOptions);	
	}
	
	function get_unitid_from_unitlabel($strUnitLabel, $arrOptions='') {
		
		// since v1.0.7, retrieves the unit option id from the given unit label.
		if (empty($arrOptions)) $arrOptions = $this->arrOptions;
		foreach($arrOptions['units'] as $strUnitID => $arrUnitOption) 
			if ($arrUnitOption['unitlabel'] == $strUnitLabel) return $strUnitID;
			
		// this line is read when there is no ID retrieved
		/*
		$arrDebug = array();
		foreach($arrOptions['units'] as $strUnitID => $arrUnitOption) {

			$arrDebug[$strUnitID] = $arrUnitOption['id'];
		}
		print_r($arrDebug);
		*/
	}
}
?>