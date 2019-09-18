<?php namespace Uit\Formyaml\Models;


use Model;

/**
 * Settings Model
 */
class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'uit_formyaml_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    public function getTypeOptions()
    {
        $options = [];
        $types = \File::allFiles(plugins_path().'/uit/formyaml/partials/types');
        foreach ($types as $type){
            $name =  substr($type->getFilename(), 1, -4);
            $options[$name] =  $name;
        }
        return $options;
    }
}
