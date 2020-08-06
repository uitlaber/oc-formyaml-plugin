<?php namespace Uit\Formyaml\Classes;


use File;
use October\Rain\Parse\Yaml;
use RainLab\Translate\Models\Message;
use System\Traits\ViewMaker;
use Uit\Formyaml\Models\Settings;
use ValidationException;
use Validator;
use Cms\Classes\Theme;

class FormBuilder
{
    use ViewMaker;
    protected $pluginName;
    protected $layoutPath;
    protected $settings;
    public $form;

    public function __construct()
    {
        $this->settings = Settings::instance();
        $this->pluginName = 'uit/formyaml';
        $this->viewPath = plugins_path('uit/formyaml/partials/');

    }

    public function generate($eventName,  $params = [], $values = [])
    {
        $yaml = new Yaml();
        $form = $yaml->parseFile($this->fieldPath() . $eventName . '.yaml');
        $theme_path = $this->getActiveThemePath();
        return $this->makePartial('form', compact('form', 'eventName',  'params', 'values','theme_path'));
    }


    public function getActiveThemePath()
    {
        $theme = Theme::getActiveTheme();
        return $theme->getPath();
    }

    public function parseYaml($eventName)
    {
        $yaml = new Yaml();
        $this->form = $yaml->parseFile($this->fieldPath() . $eventName . '.yaml');

    }

    public function fieldPath()
    {
        return plugins_path($this->pluginName . '/forms/');
    }

    public function fieldSetting($type)
    {
        $setting = null;

        if(is_array($this->settings->field) && count($this->settings->field)){
            foreach ($this->settings->field as $field) {
                if ($field['type'] == $type) {
                    $setting = $field;
                }
            }
        }
        return $setting;
    }

    public function globalSetting()
    {
        return $this->settings;
    }

    public function getTypePartial($type){

        $user_type_path = $this->getActiveThemePath() . '/partials/types/_' . strtolower(basename($type)).'.htm';
        $user_type = $this->getActiveThemePath().'/partials/types/'.$type;
        $default_type = 'types/'.$type;
        return   (File::exists($user_type_path))?$user_type:$default_type;

    }

    public function trans($string, $params = [])
    {
        if(class_exists('Message')){
            return Message::trans($string, $params);
        }else{
            return $string;
        }
        
    }


    public function validate($data, $messages = [], $attributes = [])
    {
        if (isset($this->form['rules']) && is_array($this->form['rules'])) {
            $validation = Validator::make($data, $this->form['rules'], $messages, $attributes);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
        } else {
            return false;
        }
    }
}

