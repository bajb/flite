<?php
/**
 * User: brooke.bryan
 * Date: 10/09/12
 * Time: 19:38
 * Description: Quick Forms
 */

class Flite_Form extends Flite_DataCollection
{

    private $_name;

    public function __construct($name = 'Flite_Form', $inputs = array(), $form_data = array())
    {
        $this->Name($name);
        if(!empty($inputs)) $this->AddHandlers($inputs);
        if(!empty($form_data)) $this->PopulateData($form_data);

        return $this;
    }

    public function __toString()
    {
        return $this->View()->Render();
    }

    public function View()
    {
        return new Flite_FormView($this);
    }

    public function Name($name = null)
    {
        if(is_null($name)) return $this->_name;
        $this->_name = $name;

        return $this;
    }

    public function ID()
    {
        return str_replace('_', '-', $this->Name());
    }

    public function Submitted()
    {
        return isset($_REQUEST['flite_submitted_form']) && $_REQUEST['flite_submitted_form'] == $this->Name();
    }

    public function Errors($field = null)
    {
        $messages = array();
        foreach($this->FailedHandlers() as $handler)
        {
            if($handler instanceof Flite_DataHandler)
            {
                if(is_null($field) || $field == $handler->Name())
                {
                    foreach($handler->Exceptions() as $exception)
                    {
                        if($exception instanceof Exception)
                        {
                            $messages[] = $exception->getMessage();
                        }
                    }
                }
            }
        }

        return $messages;
    }

    public function ErrorFields()
    {
        $fields = array();
        foreach($this->FailedHandlers() as $handler)
        {
            if($handler instanceof Flite_DataHandler)
            {
                $fields[] = $handler->Name();
            }
        }

        return $fields;
    }

    public function ErrorMessage($field = null)
    {
        $messages = $this->Errors($field);
        $return   = implode(', ', $messages);

        return empty($return) ? false : $return;
    }


    public function RenderFormOpen($attributes = array())
    {
        return '<form ' . $this->AttributesToString($attributes) . ' id="' . $this->ID() . '" name="' . $this->Name() . '" method="post">'
             . '<input type="hidden" name="flite_submitted_form" value="' . $this->Name() . '" />';
    }

    protected function AttributesToString($attributes)
    {
        $output = array();
        foreach($attributes as $key => $value)
            $output[] = sprintf('%s="%s"', $key, $value);
        return implode(" ", $output);
    }

    public function RenderFormClose()
    {
        return '</form>';
    }

    public function RenderSubmit()
    {
        return '<input type="submit" value="Submit"/>';
    }
}

class Flite_FormField
{

    private $_handler;
    private $_type;
    private $_attributes;
    private $_label = '';
    private $_label_position = 'before';

    public function __construct(Flite_DataHandler $handler, $type = 'dynamic')
    {
        $this->_handler = $handler;
        if($type == 'dynamic')
        {
            if(FC::count($handler->Options()) == 0)
            {
                if($handler->Name() == 'password') $type = 'password';
                else $type = 'text';
            }
            else $type = 'select';
        }
        $this->_type = $type;
    }

    public function __toString()
    {
        return $this->Render();
    }

    public function Name()
    {
        return $this->_handler->GetName();
    }

    public function Handler()
    {
        return $this->_handler;
    }

    public function Type($type = null)
    {
        if(is_null($type)) return $this->_type;
        else
        {
            $this->_type = $type;

            return $this;
        }
    }

    public function Label($label = null, $position = null)
    {
        if(!is_null($position)) $this->LabelPosition($position);
        if(is_null($label))
        {
            if($this->_label === '') $this->_label = ucwords(str_replace('_', ' ', $this->Name()));

            return $this->_label;
        }
        else
        {
            $this->_label = $label;

            return $this;
        }
    }

    public function LabelPosition($position = 'before')
    {
        if(in_array($position, array('before', 'after', 'none')))
        {
            $this->_label_position = $position;

            return $this;
        }
        else return $this->_label_position;
    }

    public function DisableLabel()
    {
        $this->_label = false;
    }

    public function Attributes()
    {
        return $this->_attributes;
    }

    public function Attr($attribute, $value = null)
    {
        if(is_null($value)) return $this->_attributes[$attribute];
        else
        {
            $this->_attributes[$attribute] = $value;

            return $this;
        }
    }

    public function Config($config)
    {
        foreach($config as $key => $value)
        {
            switch(strtolower($key))
            {
                case 'type':
                    $this->Type($value);
                    break;
                case 'class':
                    $this->Attr('class', $value);
                    break;
                case 'style':
                    $this->Attr('style', $value);
                    break;
            }
        }
    }


    public function Render()
    {
        $full = $this->RenderOpen();
        if($this->_label_position == 'before') $full .= $this->RenderLabel();
        $full .= $this->RenderElement();
        if($this->_label_position == 'after') $full .= $this->RenderLabel();
        $full .= $this->RenderClose();

        return $full;
    }

    public function RenderElement()
    {
        $attributes = '';

        $attr = FC::array_merge(
            $this->_attributes,
            array(
                 'name' => $this->Name(),
                 'id'   => $this->Handler()->ID()
            )
        );
        foreach($attr as $k => $v) $attributes .= " $k=\"$v\" ";

        $replacements = array(
            'attr'  => $attributes,
            'value' => $this->Handler()->Data()
        );

        switch($this->Type())
        {
            case 'select':
                $replacements['value'] = $this->SelectOptions();
                $html                  = $this->ElementHTML('select', $replacements);
                break;
            case 'radio':
                $html = $this->InputOptions($attributes, "radio");
                break;
            case 'checkbox':
                $html = $this->InputOptions($attributes, "checkbox");
                break;
            case 'password':
                $html = $this->ElementHTML('password', $replacements);
                break;
            case 'textarea':
                $html = $this->ElementHTML('textarea', $replacements);
                break;
            case 'text':
            default:
                $html = $this->ElementHTML('text', $replacements);
                break;
        }


        $field_errors = $this->Handler()->Exceptions();
        if(FC::count($field_errors) > 0)
        {
            $errors = array();
            foreach($field_errors as $error)
                $errors[] = $error->getMessage();

            $html .= '<div class="errors">'.implode(',', $errors).'</div>';
        }

        return $html;
    }

    public function RenderOpen()
    {
        $html = '<div class="element type-'.$this->_type.'" id="element-' . $this->Name() . '">';

        return $html;
    }

    public function RenderClose()
    {
        $html = '</div>';

        return $html;
    }

    public function RenderLabel()
    {
        if($this->Label() === false) return '';
        $html = '<label for="' . $this->Handler()->ID() . '">' . $this->Label() . '</label>';

        return $html;
    }

    public function SelectOptions()
    {
        $html = '';
        foreach($this->Handler()->Options() as $key => $option)
        {
            $html .= '<option value="' . $key . '"';
            if(in_array($this->Handler()->Data(), array($key, $option)))
            {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $option . '</option>';
        }

        return $html;
    }

    public function InputOptions($attributes, $type = 'radio')
    {
        $attributes = str_replace('id="' . $this->Handler()->ID() . '"', '', $attributes);
        if($type == 'checkbox')
        {
            $attributes = str_replace('name="' . $this->Name() . '"', 'name="' . $this->Name() . '[]"', $attributes);
        }
        $html = '';
        foreach($this->Handler()->Options() as $key => $option)
        {
            if($this->Handler()->Populated())
            {
                $selected = in_array($this->Handler()->Data(), array($key, $option));
                if(is_array($this->Handler()->Data()))
                {
                    foreach($this->Handler()->Data() as $k => $v)
                    {
                        if($v == $key) $selected = true;
                    }
                }
            }
            else $selected = false;
            $replacements = array(
                'attr'     => $attributes,
                'value'    => $key,
                'label'    => $option,
                'selected' => $selected ? ' checked="checked"' : '',
                'elid'     => md5($this->Handler()->ID() . $key)
            );
            $html .= $this->ElementHTML($type, $replacements);
        }

        return $html;
    }

    private function ElementHTML($type, $replacements)
    {
        $html = '';
        switch($type)
        {
            case 'text':
                $html = '<input type="text" value="##VALUE##" ##ATTR## />';
                break;
            case 'radio':
                $html = '<input type="radio" value="##VALUE##" ##ATTR## ##SELECTED## id="##ELID##"/><label for="##ELID##">##LABEL##</label>';
                break;
            case 'checkbox':
                $html = '<input type="checkbox" value="##VALUE##" ##ATTR## ##SELECTED## id="##ELID##"/><label for="##ELID##">##LABEL##</label>';
                break;
            case 'password':
                $html = '<input type="password" value="##VALUE##" ##ATTR## />';
                break;
            case 'textarea':
                $html = '<textarea ##ATTR##>##VALUE##</textarea>';
                break;
            case 'select':
                $html = '<select ##ATTR##>##VALUE##</select>';
                break;
        }

        foreach($replacements as $k => $v)
        {
            $html = str_replace('##' . strtoupper($k) . '##', $v, $html);
        }

        return $html;
    }
}

class Flite_FormView
{

    /**
     * @var $_form Flite_Form
     */
    private $_attributes;
    private $_form;
    private $_fields;
    private $_order;

    public function __construct(Flite_Form $form, $fields = array())
    {
        $this->_form   = $form;
        $this->_fields = array();
        $this->_order  = array();
        if(empty($fields)) $fields = $form->Handlers();
        foreach($fields as $field)
        {
            $this->AddField($field);
        }
    }

    public function __get($key)
    {
        return $this->Field($key);
    }

    public function __toString()
    {
        return $this->Render();
    }

    public function Render()
    {
        $return = '';
        $return .= $this->_form->RenderFormOpen();
        foreach($this->_order as $key)
        {
            if(isset($this->_fields[$key]))
            {
                $field = $this->_fields[$key];
                if($field instanceof Flite_FormField)
                {
                    $return .= $field->Render() . "\n\n";
                }
            }
        }
        $return .= $this->_form->RenderSubmit();
        $return .= $this->_form->RenderFormClose();

        return $return;
    }

    public function AddAttributes($attributes)
    {
        $this->_attributes = FC::arr($attributes);
        return $this;
    }
    public function OpenForm()
    {
        return $this->_form->RenderFormOpen($this->_attributes);
    }

    public function CloseForm()
    {
        return $this->_form->RenderFormClose();
    }

    public function SubmitButton()
    {
        return $this->_form->RenderSubmit();
    }

    public function SetOrder($order)
    {
        $this->_order = $order;

        return $this;
    }

    public function Types($fieldtype = array())
    {
        foreach($fieldtype as $field => $type)
        {
            $this->Field($field)->Type($type);
        }

        return $this;
    }


    /**
     * @param $name
     * @return Flite_FormField
     * @throws Exception
     */
    public function Field($name, $config = null)
    {
        if(isset($this->_fields[$name]))
        {
            $field = $this->_fields[$name];
            if($field instanceof Flite_FormField)
            {
                if(!is_null($config))
                {
                    if(is_string($config))
                    {
                        $field->Type($config);
                    }
                    else if(is_array($config))
                    {
                        $field->Config($config);
                    }
                }

                return $field;
            }
        }
        throw new Exception("Invalid field '$name'");
    }

    public function Label($name)
    {
        return $this->Field($name)->Label();
    }

    public function AddField($handler, $type = 'dynamic')
    {
        if($handler instanceof Flite_FormField)
        {
            $field = $handler;
        }
        else if(is_string($handler))
        {
            $field = new Flite_FormField($this->_form->Handler($handler), $type);
        }
        else if($handler instanceof Flite_DataHandler)
        {
            $field = new Flite_FormField($handler, $type);
        }
        else
        {
            throw new Exception("Invalid handler");
        }

        $this->_fields[$field->Name()] = $field;
        $this->_order[]                = $field->Name();

        return $this;
    }

    public function Password($name)
    {
        return $this->Field($name)->Type('password');
    }

    public function Text($name)
    {
        return $this->Field($name)->Type('text');
    }

    public function Select($name)
    {
        return $this->Field($name)->Type('select');
    }

    public function Textarea($name)
    {
        return $this->Field($name)->Type('textarea');
    }
}
