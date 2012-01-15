<?php
/**
 *
 * @author     Gareth Evans <gareth@bajb.net>
 * @copyright  Copyright (c) 2007 through 2012
 * @version    1.0
 *
 */

class View
{
	//config options
	private $root;
	private $dir = 'public_html/views/';
	private $page_data = array();

	//variable for storing save content (rather than echo)
	public $content = NULL;

	public function __construct($config = array())
	{
		global $_FLITE;
		$this->root = $_FLITE->GetConfig('site_root');

		if(isset($config['dir'])) {
			$this->dir = $config['dir'];
			if(substr($config['dir'], -1) !== '/') $this->dir .= '/';
		}

		if(isset($config['page_data'])) $this->page_data = $config['page_data'];
	}

	public function addPageData($page_data)
	{
	    if(!is_array($page_data)) $page_data = array($page_data);
	    $this->page_data = array_merge($this->page_data, $page_data);
	}

	public function Load($file, $echo = true, $ext = 'php')
	{
		global $_FLITE;
		$dir = $this->dir;

		if(is_array($file))
		{
			foreach($file as $v_file) $this->load($v_file, $echo);
			return;
		}
		if(strrpos($file, $ext) !== strlen($file)-1) $file .= ".$ext";
		if($echo === false)
		{
			$this->SaveString($file);
			return;
		}

		extract($this->page_data);

		$included = @include_once($this->root . $dir . $file);
		if(!$included)
		{
		    ?><script type="text/javascript">window.location = '<?php echo $_FLITE->GetConfig('protocol') . $_FLITE->sub_domain . '.' . $_FLITE->domain . '.' . $_FLITE->tld . '/404'; ?>';</script><?php
		}
		return (bool)$included;
	}

	private function SaveString($file)
	{
		ob_start();
		@include_once($this->root . $this->dir . $file);
		$this->content .= ob_get_contents();
		ob_end_clean();
		return;
	}
}