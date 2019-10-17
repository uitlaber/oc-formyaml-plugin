<?php namespace Uit\Formyaml\Classes;



use October\Rain\Parse\Yaml;
use RainLab\Translate\Models\Message;
use System\Traits\ViewMaker;
use Uit\Formyaml\Models\Settings;
use ValidationException;
use Validator;


class FormBuilder
{
    use ViewMaker;
    protected $pluginName;
    protected $layoutPath;
    protected $settings;
    protected $form;

    public function __construct($pluginName)
    {
        $this->settings = Settings::instance();
        $this->pluginName = $pluginName;
        $this->viewPath = plugins_path('uit/formyaml/partials/');

    }

    public function generate($formName, $requestName = '', $params = [], $values = [])
    {
        $yaml = new Yaml();
        $form = $yaml->parseFile($this->fieldPath() . $formName . '.yaml');
        return $this->makePartial('form', compact('form', 'formName', 'requestName', 'params', 'values'));
    }

    public function parseYaml($formName)
    {
        $yaml = new Yaml();
        $this->form = $yaml->parseFile($this->fieldPath() . $formName . '.yaml');

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

    public function trans($string, $params = [])
    {
//        return $string;
        return Message::trans($string, $params);
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

