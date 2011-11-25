<?php
/**
 * A helper class for generating standard HTML elements and links quicker
 */

class Html {

    /**
     * Document type definitions
     *
     * @var array
     * @access private
     *
     */
    private $docTypes = array(
            'html4-strict'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
            'html4-trans'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
            'html4-frame'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
            'xhtml-strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
            'xhtml-trans' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
            'xhtml-frame' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
            'xhtml11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
            'html5' => '<!DOCTYPE HTML>'
    );

    /**
     * Store the doctype for the whole helper
     *
     * @var string The shorthand doc type
     */
    private $doctype = 'xhtml-strict';

    /**
     * Store the preferred closing tag for this DTD
     *
     * @var string The closing tag type
     */
    private $closetag = '/>';

    /**
     * Append to css style tag
     * Allows for css to be fetched following updates
     *
     * @var string
     */
    private $cssVersion = '|v=1320855700';

    /**
     * Append to js script tag
     * Allows for js to be fetched following updates
     *
     * @var unknown_type
     */
    private $jsVersion = '|v=1320855700';

    /**
     * Set the default doc type for the html helper
     *
     * @param string $dtd A valid shorthand DTD
     */
    function  __construct($dtd) {
        if(isset($this->docTypes[$dtd])){
            $this->doctype = $dtd;
        }else{
            $this->doctype = 'xhtml-strict';
        }

        $this->setCloseTag();
    }

    /**
     * Read the DTD set in the class and set the correct closing tag
     */
    private function setCloseTag(){
        if(substr($this->doctype, 0, 5) == 'xhtml'){
            $this->closetag = " />";
        }else{
            $this->closetag = ">";
        }
    }

    /**
     * Returns a doctype string.
     *
     * Possible doctypes:
     *
     *  - html4-strict:  HTML4 Strict.
     *  - html4-trans:  HTML4 Transitional.
     *  - html4-frame:  HTML4 Frameset.
     *  - xhtml-strict: XHTML1 Strict.
     *  - xhtml-trans: XHTML1 Transitional.
     *  - xhtml-frame: XHTML1 Frameset.
     *  - xhtml11: XHTML1.1.
     *
     * @param string $type Doctype to use.
     * @return string Doctype string
     *
     */
    public function docType($dtd = '') {
        if(empty($dtd)){
            $dtd = $this->doctype;
        }
        if (isset($this->docTypes[$dtd])) {
                return $this->docTypes[$dtd];
        }
        return null;
    }

    /**
     * Create a <meta> tag to set the document encoding type - http://www.w3.org/TR/html4/charset.html#encodings
     * NB, If you change the charset from UTF-8 you should include an XML tag - http://www.w3.org/TR/xhtml1/#normative
     *
     * @param string $charset The character encoding you want to use
     * @return string The HTML snippet meta tag
     */
    public function charset($charset = 'utf-8'){
        $meta = "<meta http-equiv='content-type' content='text/html; charset=$charset'".$this->closetag;
        return $meta;
    }

    /**
     * Function to return a set of meta tags for various things, focused on returning tags based on name/content pairs
     *
     * @param array $tags An array of name and content pairs
     * @return string A formatted HTML snippet of <meta> tags
     */
    public function meta($tags){
        if(!is_array($tags)){
            return false;
        }

        $meta = '';
        foreach($tags as $name => $content){
            $meta .= "<meta name='$name' content='$content'".$this->closetag." \n";
        }

        return $meta;
    }

    /**
     * Create a DTD dependant doctype link
     *
     * @global array $_FCONF The global config settings array
     * @param string $path The css path from your site root, eg. css/style.css  !Remember no preceeding slash!
     * @param bool $ie6 True/False to wrap the css in IE6 specific tags
     * @return string The HTML css link snippet
     */
    public function css($path, $ie6 = false){
        global $_FCONF;

        $link = '';

        if($ie6){
            $link .= "<!--[if IE 6]>";
        }

        $link .= "<link type='text/css' rel='stylesheet' href='{$_FCONF['static_domain']}/css/{$path}{$this->cssVersion}'".$this->closetag;

        if($ie6){
            $link .= "<![endif]-->";
        }

        return $link;
    }

    /**
     * Create a valid link to a javascript file
     *
     * @global array $_FCONF The global config settings array
     * @param string $path The js path from your site root, eg. js/common.js  !Remember no preceeding slash!
     * @return string The JS link snippet
     */
    public function js($path){
        global $_FCONF;
        $link = "<script type='text/javascript' src='{$_FCONF['static_domain']}/js/{$path}{$this->jsVersion}'></script>";
        return $link;
    }

    /**
     * Return a link to a popular GoogleCode hosted framework
     *
     * @param string $fw The name of the framework
     * @return string The JS link snippet
     *
     * TODO: Convert this to have an array parameter of frameworks such as array('jquery','jqueryui')
     */
    public function framework($fw){
    	global $_FCONF;
    	$proto = isset($_FCONF['PROTOCOL']) ? $_FCONF['PROTOCOL'] : 'http://';
        switch ($fw){
            case 'chrome':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/chrome-frame/1.0.2/CFInstall.min.js'></script>";
                break;
            case 'dojo':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/dojo/1.5/dojo/dojo.xd.js'></script>";
                break;
            case 'extcore':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/ext-core/3.1.0/ext-core.js'></script>";
                break;
            case 'jquery':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js'></script>";
                break;
            case 'jqueryui':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js'></script>";
                break;
            case 'mootools':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/mootools/1.2.4/mootools-yui-compressed.js'></script>";
                break;
            case 'prototype':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/prototype/1.6.1.0/prototype.js'></script>";
                break;
            case 'scriptaculous':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/scriptaculous/1.8.3/scriptaculous.js'></script>";
                break;
            case 'swfobject':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js'></script>";
                break;
            case 'yui':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/yui/2.8.1/build/yuiloader/yuiloader-min.js'></script>";
                break;
            case 'webfont':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/webfont/1.0.4/webfont.js'></script>";
                break;
        }

        return $link;
    }

    /**
     * Function for generating float clear div's
     *
     * @param string $type A type of clear to create, both, left or right
     * @return string The HTML snippet clear div
     */
    public function clear($type = 'both'){
        switch ($type){
            case 'both':
            default:
                $clr = "<div class='clear-div' style='clear:both'><!--blank--></div>";
                break;
            case 'left':
                $clr = "<div style='clear:left'><!--blank--></div>";
                break;
            case 'right':
                $clr = "<div style='clear:right'><!--blank--></div>";
                break;
        }

        return $clr;
    }

    /**
     * Function for creating slugs from strings
     * Stolen from: http://stackoverflow.com/questions/2580581/best-way-to-escape-and-create-a-slug
     *
     * @param string $string The string to sluggify
     * @param string $space The character to replace spaces with
     * @return string The sluggified string
     */
     public function toSlug($string,$space="-") {
        if (function_exists('iconv')) {
            $string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        }
        $string = preg_replace("/[^a-zA-Z0-9 -]/", "", $string);
        $string = strtolower($string);
        $string = str_replace(" ", $space, $string);

        return $string;
     }

     /**
      * Function to encode an email all into entities to better prevent robots and spiders picking it up
      *
      * @param string $email The email address to encode
      * @return string An encoded email address
      */
     public function encodeEmail($email){
         return htmlentities($email, ENT_COMPAT, 'UTF-8', false);
     }

     /**
      * Function to generate a compatible XML style root tag
      *
      * @return <type>
      */
     public function roottag(){
         if(substr($this->doctype, 0, 5) == 'xhtml'){
             return "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>";
         }else{
             return "<html>";
         }
     }

}