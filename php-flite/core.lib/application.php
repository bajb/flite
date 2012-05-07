<?php
/**
 *
 * @author     Brooke Bryan <brooke@bajb.net>
 * @copyright  Copyright (c) 2007 through 2012
 * @version    2.0
 *
 */

class FliteApplication extends FliteConfig
{
    private $branding_enabled=true;
    private $controller;
    private $default_view;
    private $template;
    private $site_view;
    private $frontend_root;
    private $render_files;
    private $page_data = array();
    protected $routes = array();
    public $pieces = array();

    /*
    Set Branding to False to disable attempts to include branding replacements
    */
    public function __construct($site_view=null, $branding=null, $html_doctype='html5')
    {
        $_FLITE = Flite::Base();

        if(is_null($site_view)) $site_view = $_FLITE->sub_domain;
        if(empty($site_view)) $site_view = 'www';

        $this->site_view = $site_view;
        $this->branding_enabled = !($branding === false);
        $this->template = is_null($branding) ? $_FLITE->domain : $branding;
        $this->frontend_root = $_FLITE->GetConfig('site_root') . 'php-flite/frontend/' . $this->site_view . '/';

        $this->html = new FCHTML($_FLITE->GetConfig('static_sub_domain',empty($_FLITE->sub_domain) ? 'www' : $_FLITE->sub_domain),$html_doctype);
        $lasthour = mktime(date("H"),0,0);
        $this->html->SetCssVersion(';v=' . $lasthour);
        $this->html->SetJsVersion(';v=' . $lasthour);

        if(file_exists($_FLITE->GetConfig('site_root') . 'php-flite/config/'. $this->template .'.php')) include_once($_FLITE->GetConfig('site_root') . 'php-flite/config/'. $this->template .'.php');
        if(file_exists($this->frontend_root . 'config/base.php')) include_once($this->frontend_root . 'config/base.php');

        if($this->branding_enabled && file_exists($this->frontend_root . 'config/'. $this->template .'.php')) include_once($this->frontend_root . 'config/'. $this->template .'.php');
    }

    /* Basic Routing */

    public function GetRoute($route)
    {
        $this->route_count = 0;
        $process_route = false;
        $route = rtrim($route, '/');
        if(stristr($route,'/'))
        {
            $this->pieces = $routes = explode('/',$route);
            if($routes)
            {
                $process_route = $this->routes;
                foreach ($routes as $croute)
                {
                    if(isset($process_route[$croute]))
                    {
                        $process_route = $process_route[$croute];
                        $this->route_count++;
                    }
                    else if(isset($process_route['*']))
                    {
                        $this->route_count++;
                        $process_route = $process_route['*'];
                    }
                    else break;
                }
                return $process_route;
            }
        }
        else
        {
            $this->pieces[0] = $route;
            $process_route = isset($this->routes[$route]) ? $this->routes[$route] : (isset($this->routes['*']) ? $this->routes['*'] : false);
            if($process_route) $this->route_count++;
        }

        if(isset($process_route['controller']) || isset($process_route['view'])) return $process_route;
        else return false;
    }

    public function SetRoute($route,$data)
    {
        $this->routes[$route] = $data;
        return true;
    }

    public function SetRoutes($route_data)
    {
        return $this->routes = $route_data;
    }

    /* Basic Routing */



    /* Page Data */

    public function SetFullPD($page_data)
	{
	    if(!is_array($page_data)) $page_data = array($page_data);
	    return $this->page_data = array_merge($this->page_data, $page_data);
	}

	public function SetPD($key,$value)
	{
	    return $this->page_data[$key] = $value;
	}

	public function GetPD($key,$default=false)
    {
        return isset($this->page_data[$key]) ? $this->page_data[$key] : $default;
	}

	public function PDIsEmpty($key)
	{
	    return empty($this->page_data[$key]);
	}

	/* Page Data */



    /*  The View */

    public function RenderFiles()
    {
        return $this->render_files;
    }

    public function SetRenderFiles($rfs)
    {
        $this->render_files = $rfs;
    }

    public function RenderHeader()
    {
        $this->Render(array('header'));
    }

    public function RenderFooter()
    {
        $this->Render(array('footer'));
    }

    public function Render($file, $ext = 'php')
	{
	    $included = false;
	    if(empty($file)) return false;
		if(is_array($file))
		{
			foreach($file as $v_file)
			{
			    $finc = $this->Render($v_file, $ext);
			    if(!$included && $finc) $included = true;
			}
			return $included;
		}

		$_FLITE = Flite::Base();
		if(is_array($this->page_data)) extract($this->page_data);

		/* Brand Specific Prepend */
		if($this->branding_enabled && file_exists($this->frontend_root . 'views/_' . $this->template . '/' . $file . '.pre.' . $ext))
		{
		    include_once($this->frontend_root . 'views/_' . $this->template . '/' . $file . '.pre.' . $ext);
		}
		/* Brand Specific Prepend */

		/* Main Include */
		if($this->branding_enabled && file_exists($this->frontend_root . 'views/_' . $this->template . '/' . $file . '.' . $ext))
		{
		    include_once($this->frontend_root . 'views/_' . $this->template . '/' . $file . '.' . $ext);
		    $included = true;
		}
		else if(file_exists($this->frontend_root . 'views/' . $file . '.' . $ext))
		{
		    include_once($this->frontend_root . 'views/' . $file . '.' . $ext);
		    $included = true;
		}
		/* Main Include */

		/* Brand Specific Append */
		if($this->branding_enabled && file_exists($this->frontend_root . 'views/_' . $this->template . '/' . $file . '.post.' . $ext))
		{
		    include_once($this->frontend_root . 'views/_' . $this->template . '/' . $file . '.post.' . $ext);
		}
		/* Brand Specific Append */

		return $included;
	}

	public function LoadViewToString($files)
	{
		if(is_array($files) && !empty($files))
		{
		    $output = '';
			foreach($files as $v_file) $output .= $this->LoadFileContent($v_file);
			return $output;
		}
		return false;
	}

	public function LoadFileContent($file,$data=false)
	{
		ob_start();
		$_FLITE = Flite::Base();
		if(!$data) $data = $this->page_data;
		if(is_array($data)) extract($data);
		@include($this->frontend_root . 'views/' . $file);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/*  The View */


	/* Run Page */

	public function RunPage($controller=null,$default_view=null,$render_header_footer=true)
	{
	    $_FLITE = Flite::Base();
	    $this->render_header_footer = $render_header_footer;

	    if(is_null($controller) && isset($_SERVER['REDIRECT_URL']) && strlen($_SERVER['REDIRECT_URL']) > 1) $this->controller = strtolower(substr($_SERVER['REDIRECT_URL'],1));
	    if(REL_PATH != '/') $this->controller = str_replace(REL_PATH,'','/' . $this->controller);
        if(is_null($controller) && empty($this->controller)) $this->controller = 'default';

        if(is_null($default_view)) $this->default_view = $this->controller;
        else $this->default_view = $default_view;

        $route = $this->GetRoute($this->controller);
        if($route)
        {
            if(isset($route['controller'])) $this->controller = $route['controller'];
            if(isset($route['view'])) $this->default_view = $route['view'];
            else $this->default_view = $this->controller;
        }

        if($this->branding_enabled && file_exists($this->frontend_root . 'controllers/_' . $this->template . '/precontrol.php'))
		{
		    include_once($this->frontend_root . 'controllers/_' . $this->template . '/precontrol.php');
		}
		else if(file_exists($this->frontend_root . 'controllers/precontrol.php'))
		{
		    include_once($this->frontend_root . 'controllers/precontrol.php');
		}

        if($this->branding_enabled && file_exists($this->frontend_root . 'controllers/_' . $this->template . '/' . $this->controller . '.php'))
		{
		    include_once($this->frontend_root . 'controllers/_' . $this->template . '/' . $this->controller . '.php');
		}
		else if(file_exists($this->frontend_root . 'controllers/' . $this->controller . '.php'))
		{
		    include_once($this->frontend_root . 'controllers/' . $this->controller . '.php');
		}

		if($this->branding_enabled && file_exists($this->frontend_root . 'controllers/_' . $this->template . '/postcontrol.php'))
		{
		    include_once($this->frontend_root . 'controllers/_' . $this->template . '/postcontrol.php');
		}
		else if(file_exists($this->frontend_root . 'controllers/postcontrol.php'))
		{
		    include_once($this->frontend_root . 'controllers/postcontrol.php');
		}

		if(!isset($this->no_render))
		{
    		ob_start();
            if($this->render_header_footer) $this->RenderHeader();
            $page_found = $this->Render(empty($this->render_files) ? $this->default_view : $this->render_files);
            if(!$page_found)
            {
                $this->Render404();
            }
            if($this->render_header_footer) $this->RenderFooter();
            ob_flush();
		}
	}

	/* Run Page */


	/* Render 404 */

	public function Render404()
	{
	    if(ob_get_level()) while(@ob_end_clean());
	    $_FLITE = Flite::Base();
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");
        @include_once($_FLITE->GetConfig('site_root') . 'php-flite/frontend/_errors/404.php');
        exit;
	}

	/* Render 404 */

	public function RelativePath()
    {
        $_FLITE = Flite::Base();
        return $_FLITE->GetConfig('relative_path','/');
    }
}
