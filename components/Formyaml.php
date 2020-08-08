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
       
        $formName = post('form_name');
       
        $data = post($formName, []);
        
        /**
         * Тут к ключу добавляю префикс из за того что при валидаци возникает ошибка если ключь состоит из цифр
         * И удаляю старый элемент массива 
         */
        foreach ($data as $key => $val) {
            $data['field_'.$key] = $val;
            unset($data[$key]);
        }

        $user = Auth::getUser();
        $eventBefore = Event::fire('formyaml.before.'.$formName, [post(), $user]);   
        if(isset($eventBefore[0]) && $eventBefore[0]) return $eventBefore[0];

        $fb = new FormBuilder();
        $fb->parseYaml($formName);
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
            $attributes[$keyModifid] = '';
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
                    'type' => $field['type'],
                    'label' => $field['label'], 
                    'value' => is_array($data[$keyModifid])?json_encode($data[$keyModifid]):$data[$keyModifid], 
                ];
            }
        }

        $eventBeforeValidate = Event::fire('formyaml.before.validate.'.$formName, [$data,$rules, $attributes ]);   
        if(isset($eventBeforeValidate[0]) && $eventBeforeValidate[0]) return $eventBeforeValidate[0];


        $validation = Validator::make($data, $rules, [], $attributes);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $eventAfterValidate = Event::fire('formyaml.after.validate.'.$formName, [$data, $user]);   
        if(isset($eventAfterValidate[0]) && $eventAfterValidate[0]) return $eventAfterValidate[0];


        if(isset($form['multistep']) && $form['multistep']){
            if( $sessionData =  session()->get($formName)){
                $clearData = array_merge($clearData, $sessionData);
            }
            session()->put($formName,$clearData);
        }

        if(isset($form['multistep']) && !post('multistep-finish', false)){
            return;
        }
       
        $submit = Submit::create([
            'event' => $formName,
            'user_id' => (!is_null($user))?$user->id:null,
            'content' => array_values($clearData),
            'info' => $info
        ]);

        session()->forget($formName);

        if(isset($form['multistep'])){
            $eventAfter = Event::fire('formyaml.after.store.multistep.'.$formName,  [post(), $user, $submit]);
        }else{
            $eventAfter = Event::fire('formyaml.after.store.'.$formName,  [post(), $user, $submit]);
        }   

        if(isset($eventAfter[0]) && $eventAfter[0]) return $eventAfter[0];


        return $submit; 
    }
}
