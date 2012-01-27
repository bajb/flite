<?php
/**
 * A helper class for generating standard HTML elements and links quicker
 */

class FCHTML {

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

    private $static_domain = 'static';

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
    private $cssVersion = '|v=488419200';

    /**
     * Append to js script tag
     * Allows for js to be fetched following updates
     *
     * @var unknown_type
     */
    private $jsVersion = '|v=488419200';

    /**
     * Set the default doc type for the html helper
     *
     * @param string $dtd A valid shorthand DTD
     */
    function  __construct($static_domain='static',$dtd='html5') {
        $this->static_domain = $static_domain;
        if(isset($this->docTypes[$dtd])){
            $this->doctype = $dtd;
        }else{
            $this->doctype = 'html5';
        }

        $this->setCloseTag();
    }

    public function SetCssVersion($version)
    {
        $this->cssVersion = $version;
    }

    public function SetJsVersion($version)
    {
        $this->jsVersion = $version;
    }

    /**
     * Read the DTD set in the class and set the correct closing tag
     */
    private function setCloseTag(){
        if(substr($this->doctype, 0, 5) == 'xhtml' || substr($this->doctype, 0, 5) == 'html5'){
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
    	if($this->doctype == 'html5'){
    		$meta = "    <meta charset='$charset'".$this->closetag;
    	}else{
    		$meta = "    <meta http-equiv='content-type' content='text/html; charset=$charset'".$this->closetag;
    	}
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
            $meta .= "    <meta name='$name' content='$content'".$this->closetag." \n";
        }

        return $meta;
    }

    public function StaticDomain()
    {
        global $_FLITE;
        return $_FLITE->GetConfig('protocol') . $this->static_domain . "." . $_FLITE->GetConfig('site_domain');
    }

    /**
     * Create a DTD dependant doctype link
     *
     * @param string $path The css path from your site root, eg. css/style.css  !Remember no preceeding slash!
     * @param bool $cssif True/False to wrap the css in IF specific tags
     * @return string The HTML css link snippet
     */
    public function css($path, $cssif = false)
    {
        global $_FLITE;
        $link = '    ';
        if($cssif) $link .= "<!--[if $cssif]>";
        $link .= "<link type='text/css' rel='stylesheet' href='".$this->StaticDomain()."/css/{$path}{$this->cssVersion}'".$this->closetag;
        if($cssif) $link .= "<![endif]-->";
        return $link . "\n";
    }

    /**
     * Create a valid link to a javascript file
     *
     * @param string $path The js path from your site root, eg. js/common.js  !Remember no preceeding slash!
     * @return string The JS link snippet
     */
    public function js($path)
    {
        global $_FLITE;
        $link = "<script type='text/javascript' src='".$this->StaticDomain()."/js/{$path}{$this->jsVersion}'></script>";
        return $link . "\n";
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
                $clr = "<div class='clear' style='clear:both'><!--blank--></div>";
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

     /**
      * Returns a URL to be used in anchor tags
      *
      * @param string $route
      * @return string
      */
     public function anchorUrl($route, $static = false)
     {
         global $_FLITE;
         $url = $_FLITE->GetConfig('full_domain');
         if($static) $url = $_FLITE->GetConfig('static_sub_domain', 'static').'.'.$_FLITE->GetConfig('site_domain').'/';
         if(is_scalar($route)) return $url . ltrim($route, '/');
         return $url;
     }

}