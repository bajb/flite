<?php
class FliteCache extends FliteConfig
{
    const CACHE_DIR = '/cache/';
    const CACHE_KEY = 'fliteconfig_';

    public function GetCache($file)
    {
        $key = self::CACHE_KEY.md5($file);
        $cache = $this->GetConfig($key, null);
        if(is_null($cache))
        {
            $cache = $this->_LoadFile($file);
            if(!is_null($cache)) $this->SetConfig($key, $cache);
        }
        return is_null($cache) ? false : $this->_UnserializeDate($cache);
    }

    public function CreateCache($file,$data)
    {
        if(!$this->_FileChanged($file, $data)) return null;
        return file_put_contents(FLITE_DIR . self::CACHE_DIR . $file, $this->_SerializeData($data));
    }

    private function _LoadFile($file)
    {
        if(file_exists(FLITE_DIR . self::CACHE_DIR . $file))
        {
            return file_get_contents(FLITE_DIR . self::CACHE_DIR . $file);
        }
        return null;
    }

    private function _FileChanged($file, $data)
    {
        $cache = $this->_LoadFile($file);
        if(!is_null($cache))
        {
            return md5($cache) !== md5($this->_SerializeData($data));
        }
        return true;
    }

    private function _SerializeData($data)
    {
        return serialize($data);
    }

    private function _UnserializeDate($data)
    {
        return unserialize($data);
    }
}
