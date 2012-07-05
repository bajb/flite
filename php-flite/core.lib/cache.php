<?php
class FliteCache extends FliteConfig
{
    public function GetCache($file)
    {
        $key = 'fliteconfig_'.md5($file);
        $cache = $this->GetConfig($key, null);
        if(is_null($cache))
        {
            $_FLITE = Flite::Base();
            if(file_exists(FLITE_DIR . '/cache/'.$file))
            {
                $cache = file_get_contents(FLITE_DIR . '/cache/'.$file);
                $this->SetConfig($key, $cache);
            }
        }
        return is_null($cache) ? false : unserialize($cache);
    }

    public function CreateCache($file,$data)
    {
        $_FLITE = Flite::Base();
        return file_put_contents(FLITE_DIR . '/cache/'.$file, serialize($data));
    }
}
