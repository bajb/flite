<?php
/**
 * User: brooke.bryan
 * Date: 25/09/12
 * Time: 11:48
 * Description: Basic Data Filtering
 */
class Flite_Filter
{


    public static function SingleSpaces($string)
    {
        return preg_replace('!\s+!', ' ', $string);
    }

    public static function Email($email)
    {
        return strtolower(trim($email));
    }

    public static function Trim($string, $charlist = null)
    {
        return trim($string, $charlist);
    }

    public static function LeftTrim($string, $charlist = null)
    {
        return ltrim($string, $charlist);
    }

    public static function RightTrim($string, $charlist = null)
    {
        return rtrim($string, $charlist);
    }

    public static function Lower($string)
    {
        return strtolower($string);
    }

    public static function Upper($string)
    {
        return strtoupper($string);
    }

    public static function UpperWords($string)
    {
        return ucwords(strtolower($string));
    }

    public static function Clean($string)
    {
        return trim(strip_tags($string));
    }

    public static function Boolean($string)
    {
        return in_array($string, array('true', '1', 1, true), true);
    }

    public static function Int($string)
    {
        return intval($string);
    }

    public static function Float($string)
    {
        return floatval($string);
    }

    public static function Arr($string)
    {
        if(is_array($string)) return $string;
        if(is_object($string)) return FC::object_to_array($string);
        if(stristr($string, ',')) return explode(',', $string);
        else return array($string);
    }

    /**
     * Returns a name object
     * @param $full_name
     * @return stdClass
     */
    public static function SplitName($full_name)
    {
        $full_name = preg_replace('!\s+!', ' ', $full_name); // Make multiple spaces single
        $name             = new stdClass();
        $parts            = explode(' ', trim($full_name));
        $name->first_name = $name->middle_name = $name->last_name = '';
        switch(count($parts))
        {
            case 1:
                $name->first_name = $parts[0];
                break;
            case 2:
                $name->first_name = $parts[0];
                $name->last_name  = $parts[1];
                break;
            default:
                $name->first_name  = array_shift($parts);
                $name->last_name   = array_pop($parts);
                $name->middle_name = implode(' ', $parts);
                break;
        }

        return $name;
    }
}
