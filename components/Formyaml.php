<?php namespace Uit\Formyaml\Components;

use Cms\Classes\ComponentBase;
use System\Models\File;
use Validator;
use ValidationException;
use Input;
use Auth;
use Event;
use Uit\Formyaml\Classes\FormBuilder;
use Uit\Formyaml\Models\Submit;


class Formyaml extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Formyaml Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->addCss('/plugins/uit/formyaml/assets/css/formyaml.css');
        $this->addJs('/plugins/uit/formyaml/assets/js/formyaml.js');
    }

    public function onUpload()
    {        
        if (post('type') == 'image') {
            $rules = [
                'file' => 'image|max:5120',
            ];
        } else {
            $rules = [
                'file' => 'max:5120',
            ];
        }

        $validation = Validator::make($form = Input::all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }


        $image = (new File())->fromPost($form['file']);
        $image->save();
        //Сохраняем в сессию загруженные id
        $sessionFilesString = session()->get('uploaded_files');
        $sessionFiles = explode(',', $sessionFilesString);
        $sessionFiles[] = $image->disk_name;
        session()->put('uploaded_files', implode(',', array_filter($sessionFiles)));
        $image->thumb = $image->getThumb(100,100);
        return $image;

    }

    public function onDeleteFile()
    {
        $sessionFilesString = session()->get('uploaded_files');
        $rules = [
            'name' => 'required'
        ];
        $validation = Validator::make($form = Input::all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $file = File::where('disk_name', post('name'))->first();
        return $file->delete();

        $sessionFiles = explode(',', $sessionFilesString);
        $sessionFiles = array_filter($sessionFiles, function ($e) {
            return ($e != post('name'));
        });
        session()->put('uploaded_files', implode(',', array_filter($sessionFiles)));
    }

    public function onSubmit(){      
       
        $eventName = post('event');
        $time = time();
        $data = post($eventName, []);
        foreach ($data as $key => $val) {
            $data['field_'.$key] = $val;
            unset($data[$key]);
        }
       
        $user = Auth::getUser();
        Event::fire('before-store.'.$eventName, [$data, $user, $time]);

        $fb = new FormBuilder();
        $fb->parseYaml($eventName);
        $form = $fb->form;

        if(!isset($form['fields']) || !count($form['fields'])) return;

        $fields = $form['fields'];     
        
        $rules = [];
        $attributes = [];
        $clearData = [];
        $info = [];

        foreach($fields as $key => $field){
            $keyModifid = 'field_'.$key;
            $required = '';
            $attributes[$keyModifid] = $field['label'];
            if(isset($field['required']) && $field['required']){
                $required = 'required';
            }
            if(isset($field['rules']) && !empty($field['rules'])){
                
                $rules[$keyModifid] = [$required] + explode('|',$field['rules']);
              
            }elseif(!empty($required)){
                $rules[$keyModifid] = [$required];
            }
            
            if(isset($data[$keyModifid])){              
                $clearData[$keyModifid] = [
                    'key' => $key,
                    'label' => $field['label'], 
                    'value' => $data[$keyModifid], 
                ];
            }
        }

       
        // dd(session()->get($form['name']));
        $validation = Validator::make($data, $rules, [], $attributes);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        if(isset($form['multistep']) && $form['multistep']){
            if( $sessionData =  session()->get($form['name'])){
                $clearData = array_merge($clearData, $sessionData);
            }
            session()->put($form['name'],$clearData);
        }

        if(isset($form['multistep']) && !post('multistep-finish', false)){
            return;
        }

       
        $submit = Submit::create([
            'event' => $eventName,
            'user_id' => (!is_null($user))?$user->id:null,
            'content' => array_values($clearData),
            'info' => $info
        ]);
        if(isset($form['multistep']) && post('multistep-finish', false)){
            Event::fire('after-store-multistep.'.$eventName,  [$data, $user, $submit, $time]);
        }else{
            Event::fire('after-store.'.$eventName,  [$data, $user, $submit, $time]);
        }   
        return $submit; 
    }
}
