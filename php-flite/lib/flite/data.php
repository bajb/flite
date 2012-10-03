<?php
/**
 * User: brooke.bryan
 * Date: 11/09/12
 * Time: 10:20
 * Description: Data Handlers
 */

class Flite_DataCollection
{

    private $_handlers;
    private $_failed;
    private $_populated;

    /**
     * @param array $handlers
     * @param mixed $populate_data
     */
    public function __construct($handlers = array(), $populate_data = null)
    {
        $this->AddHandlers($handlers);
        if(is_array($populate_data)) $this->PopulateData($populate_data);
    }

    public function AddHandlers($handlers = array())
    {
        foreach($handlers as $handler)
        {
            if($handler instanceof Flite_DataHandler)
            {
                $this->_handlers[$handler->GetName()] = $handler;
            }
        }
    }

    public function AddHandler(Flite_DataHandler $handler)
    {
        if($handler instanceof Flite_DataHandler)
        {
            $this->_handlers[$handler->GetName()] = $handler;
        }
    }

    public function GetHandler($name)
    {
        return isset($this->_handlers[$name]) ? $this->_handlers[$name] : new Flite_DataHandler($name);
    }

    public function Handler($name)
    {
        return $this->GetHandler($name);
    }

    public function GetHandlers(array $array)
    {
        $return = array();
        $return = array();
        foreach($array as $handler)
        {
            if(isset($this->_handlers[$handler]))
            {
                $return[$handler] = $this->_handlers[$handler];
            }
            else $return[$handler] = new Flite_DataHandler($handler);
        }

        return $return;
    }

    public function Handlers($array = null)
    {
        if(is_null($array)) return $this->_handlers;
        else return $this->GetHandlers($array);
    }

    public function Valid($process_all = false)
    {
        $valid = true;
        foreach($this->_handlers as $handler)
        {
            if($handler instanceof Flite_DataHandler)
            {
                if(!$handler->Valid($process_all))
                {
                    $this->_failed[$handler->GetName()] = $handler;
                    $valid                              = false;
                }
            }
        }

        return $valid;
    }

    public function PopulateData($keyvalue = array())
    {
        foreach($this->_handlers as $handler)
        {
            if($handler instanceof Flite_DataHandler)
            {
                if(isset($keyvalue[$handler->GetName()]))
                {
                    $handler->SetData($keyvalue[$handler->GetName()]);
                    $this->_populated = true;
                }
            }
        }
    }

    public function Populated()
    {
        return $this->_populated ? true : false;
    }

    public function FailedHandlers()
    {
        return FC::arr($this->_failed);
    }
}

class Flite_DataHandler
{

    private $_name;
    private $_required;
    private $_validators;
    private $_filters;
    private $_options;
    private $_data;
    private $_exceptions;
    private $_populated = false;

    public function __construct($name,
                                $required = false,
                                $validators = null,
                                $filters = null,
                                $options = null,
                                $data = null)
    {
        $this->Name($name);
        $this->Required($required ? true : false);
        $this->AddValidators($validators);
        $this->AddFilters($filters);
        $this->SetData($data);
        $this->SetOptions($options);
    }

    public function __toString()
    {
        return $this->_name;
    }

    public function Populated()
    {
        return $this->_populated ? true : false;
    }

    public function GetName()
    {
        return $this->_name;
    }

    public function Name($name = null)
    {
        if(is_string($name))
        {
            $this->_name = $name;

            return $this;
        }

        return $this->GetName();
    }

    public function ID()
    {
        return str_replace('_', '-', $this->Name());
    }

    public function Required($set = null)
    {
        if(is_bool($set))
        {
            $this->_required = $set;

            return $this;
        }

        return $this->_required ? true : false;
    }

    public function SetData($data)
    {
        $this->_populated = !is_null($data);
        $this->_data      = $data;

        return $this;
    }

    public function RawData()
    {
        return $this->_data;
    }

    public function Data()
    {
        if(!is_array($this->_filters)) return $this->_data;

        $data = $this->_data;
        foreach($this->_filters as $filter)
        {
            if($filter instanceof Flite_Callback)
            {
                $data = $filter->Process($this->Data());
            }
        }

        return $data;
    }

    public function SetOptions($options)
    {
        $this->_options = $options;

        return $this;
    }

    public function AddOption($option)
    {
        $this->_options[] = $option;

        return $this;
    }

    public function Options()
    {
        return $this->_options;
    }

    public function AddFilter(Flite_Callback $filter)
    {
        $this->_filters[] = $filter;

        return $this;
    }

    public function AddFilters($filters)
    {
        if(is_array($filters))
        {
            foreach($filters as $filter)
            {
                if(is_string($filter))
                {
                    $this->AddFilter(Flite_Callback::_($filter, array(), 'filter'));
                }
                else if(is_array($filter))
                {
                    if(isset($filter[0]) && is_array($filter[0]))
                    {
                        $this->AddFilter(Flite_Callback::_($filter[0], $filter[1], 'filter'));
                    }
                }
                else if($filter instanceof Flite_Callback)
                {
                    $this->AddFilter($filter);
                }
            }
        }
        else if(is_string($filters))
        {
            $this->AddFilter(Flite_Callback::_($filters, array(), 'filter'));
        }
        else if($filters instanceof Flite_Callback)
        {
            $this->AddFilter($filters);
        }
    }

    public function Filters($replace_filters)
    {
        if(!is_null($replace_filters) && is_array($replace_filters))
        {
            $this->_filters = array();
            $this->AddFilters($replace_filters);

            return $this;
        }

        return $this->_filters;
    }

    public function AddValidator(Flite_Callback $validator)
    {
        $this->_validators[] = $validator;

        return $this;
    }

    public function AddValidators($validators)
    {
        if(is_array($validators))
        {
            foreach($validators as $validator)
            {
                if(is_string($validator))
                {
                    $this->AddValidator(Flite_Callback::_($validator, array(), "validator"));
                }
                else if(is_array($validator))
                {
                    if(isset($validator[0]) && is_array($validator[0]))
                    {
                        $this->AddValidator(Flite_Callback::_($validator[0], $validator[1], "validator"));
                    }
                }
                else if($validator instanceof Flite_Callback)
                {
                    $this->AddValidator($validator);
                }
            }
        }
        else if(is_string($validators))
        {
            $this->AddValidator(Flite_Callback::_($validators, array(), "validator"));
        }
        else if($validators instanceof Flite_Callback)
        {
            $this->AddValidator($validators);
        }
    }

    public function Validators($replace_validators = null)
    {
        if(!is_null($replace_validators) && is_array($replace_validators))
        {
            $this->_validators = array();
            $this->AddValidators($replace_validators);

            return $this;
        }

        return $this->_validators;
    }

    public function Valid($process_all = false)
    {
        if($this->Required() && !$this->Populated())
        {
            $this->_exceptions[] = new Exception("Required Field " . $this->Name());

            return false;
        }
        if(!is_array($this->_validators)) return true;

        $valid = true;
        foreach($this->_validators as $validator)
        {
            if($validator instanceof Flite_Callback)
            {
                $passed = false;
                try
                {
                    $passed = $validator->Process($this->Data());
                    if(!$passed)
                    {
                        throw new Exception("Validation failed");
                    }
                }
                catch(Exception $e)
                {
                    $this->_exceptions[] = $e;
                }
                if(!$passed)
                {
                    $valid = false;
                    if(!$process_all) break;
                }
            }
        }

        return $valid;
    }

    public function Exceptions()
    {
        return FC::arr($this->_exceptions);
    }
}

class Flite_Callback
{

    private $_method;
    private $_options;
    private $_type;

    public function __construct($method, $options = array(), $callback_type = null)
    {
        $this->_method  = $method;
        $this->_options = $options;
        $this->_type    = $callback_type;
    }

    public static function _($method, $options = array(), $callback_type = null)
    {
        return new Flite_Callback($method, $options, $callback_type);
    }

    public function Process($input = null)
    {
        if($this->_type == 'filter' && is_string($this->_method))
        {
            if(!function_exists($this->_method) && method_exists("Flite_Filter", $this->_method))
            {
                $this->_method = array("Flite_Filter", $this->_method);
            }
        }

        if($this->_type == 'validator' && is_string($this->_method))
        {
            if(!function_exists($this->_method) && method_exists("Flite_Validate", $this->_method))
            {
                $this->_method = array("Flite_Validate", $this->_method);
            }
        }

        return call_user_func_array($this->_method, FC::array_merge(array($input), $this->_options));
    }
}
