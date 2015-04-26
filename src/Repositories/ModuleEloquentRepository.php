<?php namespace Veemo\Modules\Repositories;


use Veemo\Core\Repositories\BaseRepository;
use Veemo\Modules\Models\ModuleInterface;


/**
 * Class ModuleEloquentRepository
 * @package Veemo\Modules\Repositories
 */
class ModuleEloquentRepository extends BaseRepository implements ModuleRepositoryInterface
{

    public function __construct(ModuleInterface $model)
    {
        $this->model = $model;
    }


}