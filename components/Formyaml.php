<?php namespace Uit\Formyaml\Components;

use Cms\Classes\ComponentBase;
use System\Models\File;
use ValidationException;
use Validator;
use Input;
use Auth;

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
        $user = Auth::getUser();
        
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
}
