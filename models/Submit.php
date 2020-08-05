<?php namespace Uit\Formyaml\Models;

use Model;
use RainLab\User\Models\User;

/**
 * Model
 */
class Submit extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'uit_formyaml_submits';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $jsonable = [
        'content', 'info'
    ];

    protected $fillable = [
        'event','user_id','content','info'
    ];

    public $belongsTo = [
        'user' => User::class
    ];
}
