<?php namespace Veemo\Modules\Models;


use Veemo\Core\Models\BaseEloquentModel;


class Module extends BaseEloquentModel implements ModuleInterface
{

    /**
     * The attributes that are fillable via mass assignment.
     *
     * @var array
     */
    protected $fillable = ['slug', 'installed', 'enabled'];

    /**
     * The attributes included in the model's JSON form.
     *
     * @var array
     */
    protected $visible = ['slug', 'installed', 'enabled'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'modules';


    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
        'installed' => 'boolean'
    ];


}