<?php


namespace Veemo\Modules;

use Countable;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Veemo\Modules\Repositories\ModuleRepositoryInterface;



class ModuleManager implements Countable, ModuleManagerInterface
{

    protected $modules;


    protected $files;


    protected $config;


    protected $path = [];


    protected $collection;


    protected $conditions = [];


    public function __construct(ModuleRepositoryInterface $modules, Filesystem $files, Repository $config)
    {
        $this->modules = $modules;
        $this->files = $files;
        $this->config = $config;

    }


    public function isEnabled($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if($module)
        {
            return ($module->enabled === true) ?  true :  false;
        }

        return false;
    }

    public function isDisabled($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if($module)
        {
            return ($module->enabled === false) ?  true :  false;
        }

        return true;
    }

    public function isCore($slug)
    {

        if ($this->exist($slug)) {
            $modules = $this->all();

            $count = $modules->filter(function ($module) use ($slug) {
                if ($module['slug'] == $slug) {
                    if ($module['is_core'] == true) return true;
                }
            })->count();


            return ($count > 0) ? true : false;
        }

        return false;
    }

    public function exist($slug)
    {
        $modules = $this->all();

        return $modules->contains('slug', $slug);
    }

    public function all()
    {
        $core = $this->core_modules();
        $addons = $this->addon_modules();

        return $core->merge($addons);
    }

    public function getModules()
    {
        $modules = $this->all();

        if (is_array($this->conditions))
        {
            foreach($this->conditions as $condition => $value)
            {
                $result = $modules->filter(function ($module) use ($condition, $value) {
                    if ($module[$condition] == $value) {
                        return true;
                    }
                });

                $modules = $result;
            }
        }


        return $modules;
    }


    public function enabled()
    {
        $this->conditions['enabled'] = true;

        return $this;
    }

    public function disabled()
    {
        $this->conditions['enabled'] = false;

        return $this;
    }

    public function installed()
    {
        $this->conditions['installed'] = true;

        return $this;
    }


    protected function core_modules()
    {
        $modules = $this->scanModulesFolder('core');
        $modules->sortBy('order');

        return $modules;
    }

    protected function addon_modules()
    {
        $modules = $this->scanModulesFolder('addons');
        $modules->sortBy('order');

        return $modules;
    }

    protected function scanModulesFolder($which = null)
    {
        $modules = [];
        $path = $this->getPath($which);

        if (!is_dir($path))
            return $modules;

        $folders = $this->files->directories($path);

        foreach ($folders as $module) {

            $moduleConfigFile = $module . '/' . $this->config->get('veemo.modules.moduleConfigFilename');

            if ($this->files->exists($moduleConfigFile)) {

                if ($this->evaluateModuleConfig($moduleConfigFile)) {
                    $current_module = $this->getModuleInfoFromConfigFile($moduleConfigFile);
                    $current_module['path'] = $module;
                    $current_module['installed'] = $this->isInstalled($current_module['slug']);
                    $current_module['enabled'] = $this->isEnabled($current_module['slug']);

                    $modules[$current_module['slug']] = $current_module;
                }
            }
        }


        return new Collection($modules);
    }

    /**
     * Get modules path based on keyword (core/addons)
     * @param string
     * @return string
     */
    public function getPath($whichOne = null)
    {
        return isset($this->path[$whichOne]) ? $this->path[$whichOne] : $this->config->get('veemo.modules.path.' . $whichOne);
    }

    /**
     * Set modules path in "RunTime" mode.
     *
     * @param  string $whichOne
     * @param  string $path
     * @return object $this
     */
    public function setPath($whichOne = null, $path)
    {
        $this->path[$whichOne] = $path;

        return $this;
    }

    protected function evaluateModuleConfig($configFile)
    {

        $required = ['slug', 'order', 'version', 'name', 'description', 'author', 'frontend', 'backend', 'settings'];
        $config = $this->files->getRequire($configFile);

        if (is_array($config)) {
            foreach ($required as $requiredField) {
                if (!array_key_exists($requiredField, $config)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    protected function getModuleInfoFromConfigFile($configFile)
    {
        $config = $this->files->getRequire($configFile);

        $module = [
            'slug' => $config['slug'],
            'is_core' => isset($config['is_core']) ? $config['is_core'] : false,
            'order' => $config['order'],
            'version' => $config['version'],
            'name' => $config['name'],
            'description' => $config['description'],
            'author_name' => isset($config['author']['name']) ? $config['author']['name'] : null,
            'author_email' => isset($config['author']['email']) ? $config['author']['email'] : null,
            'author_web' => isset($config['author']['web']) ? $config['author']['web'] : null,

            'config' => [

                'frontend' => $config['frontend'],
                'backend' => $config['backend'],
                'settings' => $config['settings']

            ]
        ];


        return $module;
    }

    public function isInstalled($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if($module)
        {
            return ($module->installed === true) ?  true :  false;
        }

        return false;
    }



    public function enable($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if($module) {
            return $this->modules->updateById($module->id, ['enabled' => 1]);

        } else {

            if ($module = $this->info($slug)) {
                return $this->modules->create(['slug' => $module['slug'], 'enabled' => 1]);
            }
        }

        return null;
    }

    public function info($slug)
    {
        if ($this->exist($slug)) {
            $module = $this->all()->get($slug);

            return $module;
        }

        return null;
    }

    public function disable($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if($module) {
            return $this->modules->updateById($module->id, ['enabled' => 0]);

        } else {

            if ($module = $this->info($slug)) {
                return $this->modules->create(['slug' => $module['slug'], 'enabled' => 0]);
            }
        }

        return null;
    }

    public function install($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if($module) {
            return $this->modules->updateById($module->id, ['installed' => 1]);

        } else {

            if ($module = $this->info($slug)) {
                return $this->modules->create(['slug' => $module['slug'], 'installed' => 1]);
            }
        }

        return null;
    }


    public function uninstall($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if($module) {
            return $this->modules->updateById($module->id, ['installed' => 0]);

        } else {

            if ($module = $this->info($slug)) {
                return $this->modules->create(['slug' => $module['slug'], 'installed' => 0]);
            }
        }

        return null;
    }

    /**
     * Returns count of all modules.
     *
     * @return int
     */
    public function count()
    {
        return count($this->all());
    }

}