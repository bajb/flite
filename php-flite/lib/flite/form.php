<?php
/**
 * User: brooke.bryan
 * Date: 10/09/12
 * Time: 19:38
 * Description: Quick Forms
 */

class Flite_Form extends Flite_DataCollection
{
    private $name;

    public function __construct($name='Flite_Form',$inputs=array(),$form_data=array())
    {
        $this->Name($name);
        if(!empty($inputs)) $this->AddHandlers($inputs);
        if(!empty($form_data)) $this->PopulateData($form_data);
        return $this;
    }

    public function Name($name=null)
    {
        if(is_null($name)) return $this->name;

        $this->name = $name;
        return $this;
    }

    public function ID()
    {
        return str_replace('_','-',$this->Name());
    }

    public function Submitted()
    {
        return true;
    }

    public function Errors($field=null)
    {
        $messages = array();
        foreach($this->FailedHandlers() as $handler)
        {
            if(is_null($field) || $field == $handler->Name())
            {
                foreach($handler->Exceptions() as $exception)
                {
                    $messages[] = $exception->getMessage();
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
            $fields[] = $handler->Name();
        }
        return $fields;
    }

    public function ErrorMessage($field=null)
    {
        $messages = $this->Errors($field);
        $return = implode(', ',$messages);
        return empty($return) ? false : $return;
    }

    public function __toString()
    {
        return $this->Render();
    }

    public function Render()
    {
        $html = $this->RenderFormOpen();
        $html .= $this->RenderElements();
        $html .= $this->RenderFormClose();
        return $html;
    }

    public function RenderFormOpen()
    {
        return '<form id="'. $this->ID() .'" name="'. $this->Name() .'" method="post">';
    }

    public function RenderFormClose()
    {
        return $this->RenderSubmit() .
            '<input type="hidden" name="flite_submitted_form" value="'. $this->Name() .'" /></form>';
    }

    public function RenderSubmit()
    {
        return '<input type="submit" value="Submit"/>';
    }

    public function RenderElements($handlers=null)
    {
        if(!is_array($handlers)) $handlers = $this->Handlers();
        $html = '';
        foreach($handlers as $handler)
        {
            $html .= Flite_FormRender::Dynamic($handler);
        }
        return $html;
    }
}


class Flite_FormRender
{
    public static function Dynamic(Flite_DataHandler $handler)
    {
        if(FC::count($handler->Options()) == 0)
        {
            if($handler->Name() == 'password') return Flite_FormRender::Password($handler);
            return Flite_FormRender::Input($handler);
        }
        else return Flite_FormRender::Select($handler);
    }

    public static function PreRender(Flite_DataHandler $handler)
    {
        $html = '<div id="element-'. $handler->Name() .'">';
        $html .= Flite_FormRender::Label($handler);
        return $html;
    }

    public static function PostRender(Flite_DataHandler $handler)
    {
        $html = '</div>';
        return $html;
    }

    public static function Label(Flite_DataHandler $handler)
    {
        $html = '<label for="'. $handler->ID() .'">'. $handler->Name() .'</label>';
        return $html;
    }

    public static function Input(Flite_DataHandler $handler)
    {
        return
            Flite_FormRender::PreRender($handler) .
            '<input id="'. $handler->ID() .'" name="'. $handler->Name() .'" type="text" value="'. $handler->Data() .'">' .
            Flite_FormRender::PostRender($handler);
    }

    public static function Password(Flite_DataHandler $handler)
    {
        return
            Flite_FormRender::PreRender($handler) .
            '<input id="'. $handler->ID() .'" name="'. $handler->Name() .'" type="password" value="'. $handler->Data() .'">' .
            Flite_FormRender::PostRender($handler);
    }

    public static function Select(Flite_DataHandler $handler)
    {
        $html = Flite_FormRender::PreRender($handler);
        $html .= '<select id="'. $handler->ID() .'" name="'. $handler->Name() .'">';
        foreach($handler->Options() as $key => $option)
        {
            $html .= '<option value="'. $key .'"'.
                (in_array($handler->Data(),array($key,$option)) ? ' selected="selected"' : '')
                .'>'. $option .'</option>';
        }
        $html .= '</select>';
        $html .= Flite_FormRender::PostRender($handler);
        return $html;
    }
}