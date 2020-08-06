<?php namespace Uit\Formyaml;

use Backend;
use System\Classes\PluginBase;
use Uit\Formyaml\Classes\FormBuilder;
use Uit\Formyaml\Components\Formyaml;
use Uit\Formyaml\Models\Settings;

/**
 * formyaml Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'formyaml',
            'description' => 'No description provided yet...',
            'author'      => 'uit',
            'icon'        => 'icon-leaf'
        ];
    }

    public function registerComponents()
    {
        return [
            Formyaml::class => 'Formyaml'
        ];
    }


    public function registerSettings()
    {
        return [
            'formyaml' => [
                'label'       => 'Генератор форм',
                'description' => 'Класы и настройки полей',
                'category'    => 'CMS',
                'icon'        => 'icon-globe',
                'class'       => Settings::class,
                'order'       => 500,
                'permissions' => ['uit.formyaml.manage_plugins'],
            ]
        ];
    }

    public function registerPermissions()
    {
        return [
            'uit.formyaml.manage_plugins' => [
                'tab' => 'Генератор форм',
                'label' => 'Доступ к плагину Генератор форм']
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'functions' => [
                'makeForm'  => [$this, 'makeForm'],
            ]
        ];
    }

    public function makeForm($eventName ,  $params = [], $values = [])
    {
        return (new FormBuilder())->generate($eventName,  $params, $values);
    }

}
