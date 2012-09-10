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
		'unitlabel' 		=> '',
		'country' 			=> 'US', // <--- this should be dynamic depanding to the user's locale or the previous input value
		'associateid' 		=> '',	 // <--  needs to investigate a way to remember user's previous input
		'containerformat'	=> '<div class="amazon-auto-links">%items%</div>',
		'itemformat' 		=> '<a href="%link%" title="%title%: %textdescription%">%img%</a><h5><a href="%link%" title="%title%: %textdescription%">%title%</a></h5><p>%htmldescription%</p>',
		'imgformat'			=> '<img src="%imgurl%" alt="%textdescription%" />',
		'imagesize' 		=> 160,
		'sortorder' 		=> 'random',
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
		'widget'			=> false
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
	
		// Include helper classes
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
		$arrOption_new = array(
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
			"general"	=> array()
		);
		$this->arrOptions = array_merge($arrOption_new, $this->arrOptions);
		$this->set_support_rate();
		update_option($this->pluginkey, $this->arrOptions);
	}		
	function set_support_rate() {
		return;
	}
	/* Used in Admin Pages */
	function set_new_unit($arrOptions='') {	
		if (!$arrOptions) {
			$this->arrOptions['newunit'] = $this->unitdefaultoptions;			
			$this->arrOptions['tab101']['cameback'] = False;		// this flag is used for pseudo session.
			$this->arrOptions['tab100']['errors'] = False;	
		}
		update_option($this->pluginkey, $this->arrOptions);
	}	
	
	/* Used in the Category Selection Page */
	function update_unit($strUnitLabel, $NewOrEdit) {	
		$this->arrOptions['units'][$strUnitLabel] = $this->arrOptions[$NewOrEdit];
		$fSuccess = update_option($this->pluginkey, $this->arrOptions);
	}
	function add_category($NewOrEdit, $strCatName, $arrCatInfo) {
	
		// adds category to the preview option array, either $options['newunit'] or $options['editunit'].
		// This is called from the preceeding page of create/edit unit.
		// returns the number of current added categories
		
		$numCategories = count($this->arrOptions[$NewOrEdit]['categories']);
		if ($numCategories >= 3) 
			return -1;
		$this->arrOptions[$NewOrEdit]['categories'][$strCatName] = $arrCatInfo;
		update_option($this->pluginkey, $this->arrOptions);		
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
		update_option($this->pluginkey, $this->arrOptions);
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

	/* for general usage */		
	function set_id($strUnitLabel) {
		if (empty($this->arrOptions['units'][$strUnitLabel]['id']))
			$this->arrOptions['units'][$strUnitLabel]['id'] = uniqid();
		update_option($this->pluginkey, $this->arrOptions);
	}
}
?>