<?php


namespace Veemo\Modules;

use Countable;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Veemo\Modules\Repositories\ModuleRepositoryInterface;


class ModuleManager implements Countable, ModuleManagerInterface
{

    protected $modules;


    protected $files;


    protected $config;


    protected $path = [];




    public function __construct(ModuleRepositoryInterface $modules, Filesystem $files, Repository $config)
    {
        $this->modules = $modules;
        $this->files = $files;
        $this->config = $config;

    }


    public function all()
    {
        $core = $this->coreModules();
        $addons = $this->addonModules();

        $modules = $core->merge($addons);

        return $modules;
    }

    public function coreModules()
    {
        $modules = $this->scanModulesFolder('core');
        $modules->sortBy('order');

        return $modules;
    }

    public function addonModules()
    {
        $modules = $this->scanModulesFolder('addons');
        $modules->sortBy('order');

        return $modules;
    }

    public function exist($slug)
    {
        $modules = $this->all();

        $count = $modules->filter(function($module) use ($slug) {
            return ($module['slug'] == $slug) ? true : false;
        })->count();

        return ($count > 0) ? true : false;
    }

    public function enabled()
    {
        // TODO: Implement enabled() method.
    }

    public function disabled()
    {
        // TODO: Implement disabled() method.
    }

    public function installed()
    {
        // TODO: Implement installed() method.
    }

    public function isEnabled($slug)
    {
        // TODO: Implement isEnabled() method.
    }

    public function isDisabled($slug)
    {
        // TODO: Implement isDisabled() method.
    }

    public function isCore($slug)
    {

        if ($this->exist($slug))
        {
            $modules = $this->all();

            $count = $modules->filter(function($module) use ($slug)
            {
                if ($module['slug'] == $slug) {
                    if($module['is_core'] == true) return true;
                }
            })->count();


            return ($count > 0) ? true : false;
        }

        return false;
    }

    public function isInstalled($slug)
    {
        // TODO: Implement isInstalled() method.
    }

    public function enable($slug)
    {
        // TODO: Implement enable() method.
    }

    public function disable($slug)
    {
        // TODO: Implement disable() method.
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



    protected function scanModulesFolder($which = null)
    {
        $modules = [];
        $path    = $this->getPath($which);

        if ( ! is_dir($path))
            return $modules;

        $folders = $this->files->directories($path);

        foreach ($folders as $module) {

            $moduleConfigFile = $module . '/' . $this->config->get('veemo.modules.moduleConfigFilename');

            if ($this->files->exists($moduleConfigFile)) {

                if ($this->evaluateModuleConfig($moduleConfigFile))
                {
                    $current_module = $this->getModuleInfo($moduleConfigFile);
                    $current_module['path'] = $module;

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

        if (is_array($config))
        {
            foreach($required as $requiredField)
            {
                if (!array_key_exists($requiredField, $config))
                {
                    return false;
                }
            }

            return true;
        }

        return false;
    }


    protected function getModuleInfo($configFile)
    {
        $config = $this->files->getRequire($configFile);

        $module = [
            'slug'          =>  $config['slug'],
            'is_core'       =>  isset($config['is_core']) ? $config['is_core'] : false,
            'order'         =>  $config['order'],
            'version'       =>  $config['version'],
            'name'          =>  $config['name'],
            'description'   =>  $config['description'],
            'author_name'   =>  isset($config['author']['name']) ? $config['author']['name'] : null,
            'author_email'  =>  isset($config['author']['email']) ? $config['author']['email'] : null,
            'author_web'    =>  isset($config['author']['web']) ? $config['author']['web'] : null,

            'config'        =>  [

                'frontend'  =>  $config['frontend'],
                'backend'  =>  $config['backend'],
                'settings'  =>  $config['settings']

            ]
        ];


        return $module;
    }

}