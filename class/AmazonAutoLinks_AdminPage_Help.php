<?php
/**
 * Deals with the plugin admin pages.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.5
 * 
 */
abstract class AmazonAutoLinks_AdminPage_Help extends AmazonAutoLinks_AdminPage_About {

	/*
	 * The Help Page
	 */
	public function do_before_aal_help() {	// do_before_ + {page slug}
		
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/library/wordpress-plugin-readme-parser/parse-readme.php' );
		$this->oWPReadMe = new WordPress_Readme_Parser;
		$this->arrWPReadMe = $this->oWPReadMe->parse_readme( AmazonAutoLinks_Commons::$strPluginDirPath . '/readme.txt' );
		
	}
	public function do_aal_help_install() {		// do_ + page slug + _ + tab slug
		echo $this->arrWPReadMe['sections']['installation'];
	}	
	public function do_aal_help_faq() {		// do_ + page slug + _ + tab slug
		echo $this->arrWPReadMe['sections']['frequently_asked_questions'];
	}
	public function do_aal_help_notes() {		// do_ + page slug + _ + tab slug
		
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/library/simple_html_dom.php' ) ;

		$html = str_get_html( $this->arrWPReadMe['remaining_content'] );
		
		$html->find( 'h3', 0 )->outertext = '';
		$html->find( 'h3', 1 )->outertext = '';
		
		$toc = '';
		$last_level = 0;

		foreach($html->find( 'h4,h5,h6' ) as $h){	// original: foreach($html->find('h1,h2,h3,h4,h5,h6') as $h
			$innerTEXT = trim($h->innertext);
			$id =  str_replace(' ','_',$innerTEXT);
			$h->id= $id; // add id attribute so we can jump to this element
			$level = intval($h->tag[1]);

			if($level > $last_level)
				$toc .= "<ol>";
			else{
				$toc .= str_repeat('</li></ol>', $last_level - $level);
				$toc .= '</li>';
			}

			$toc .= "<li><a href='#{$id}'>{$innerTEXT}</a>";

			$last_level = $level;
		}

		$toc .= str_repeat('</li></ol>', $last_level);
		$html_with_toc = $toc . "<hr>" . $html->save();		
		
		echo $html_with_toc;
		
	}	
		
}