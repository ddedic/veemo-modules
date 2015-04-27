<?php


namespace Veemo\Modules;

use Countable;
use Illuminate\Support\Str;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Veemo\Modules\Repositories\ModuleRepositoryInterface;


class ModuleManager implements Countable, ModuleManagerInterface
{

    /**
     * @var ModuleRepositoryInterface
     */
    protected $modules;


    /**
     * @var Filesystem
     */
    protected $files;


    /**
     * @var Repository
     */
    protected $config;


    /**
     * @var array
     */
    protected $path = [];


    /**
     * @var
     */
    protected $collection;


    /**
     * @var array
     */
    protected $conditions = [];


    /**
     * @param ModuleRepositoryInterface $modules
     * @param Filesystem $files
     * @param Repository $config
     */
    public function __construct(ModuleRepositoryInterface $modules, Filesystem $files, Repository $config)
    {
        $this->modules = $modules;
        $this->files = $files;
        $this->config = $config;

    }

    /**
     * @param $slug
     * @return bool
     */
    public function isDisabled($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if ($module) {
            return ($module->enabled === false) ? true : false;
        }

        return true;
    }

    /**
     * @param $slug
     * @return bool
     */
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

    /**
     * @param $slug
     * @return bool
     */
    public function exist($slug)
    {
        $modules = $this->all();

        return $modules->contains('slug', $slug);
    }

    /**
     * @return static
     */
    public function all()
    {
        $core = $this->core_modules();
        $addons = $this->addon_modules();

        return $core->merge($addons);
    }

    /**
     * @return Collection
     */
    protected function core_modules()
    {
        $modules = $this->scanModulesFolder('core');
        $modules->sortBy('order');

        return $modules;
    }

    /**
     * @param null $which
     * @return Collection
     */
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
                    $current_module['namespace'] = $this->config->get('veemo.modules.namespace.' . $which) . Str::studly($current_module['slug']);

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

    /**
     * @param $configFile
     * @return bool
     */
    protected function evaluateModuleConfig($configFile)
    {

        $required = ['slug', 'order', 'version', 'name', 'description', 'author', 'frontend', 'backend', 'permissions', 'settings'];
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

    /**
     * @param $configFile
     * @return array
     */
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
                'permissions' => $config['permissions'],
                'settings' => $config['settings']

            ]
        ];


        return $module;
    }

    /**
     * @param $slug
     * @return bool
     */
    public function isInstalled($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if ($module) {
            return ($module->installed === true) ? true : false;
        }

        return false;
    }

    /**
     * @param $slug
     * @return bool
     */
    public function isEnabled($slug)
    {
        $module = $this->modules->where('slug', $slug)->first();

        if ($module) {
            return ($module->enabled === true) ? true : false;
        }

        return false;
    }

    /**
     * @return Collection
     */
    protected function addon_modules()
    {
        $modules = $this->scanModulesFolder('addons');
        $modules->sortBy('order');

        return $modules;
    }

    /**
     * @return ModuleManager
     */
    public function getModules()
    {
        $modules = $this->all();

        if (is_array($this->conditions)) {
            foreach ($this->conditions as $condition => $value) {
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

    /**
     * @return $this
     */
    public function enabled()
    {
        $this->conditions['enabled'] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disabled()
    {
        $this->conditions['enabled'] = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function installed()
    {
        $this->conditions['installed'] = true;

        return $this;
    }

    /**
     * @param $slug
     * @return null
     */
    public function enable($slug)
    {
        if ($module = $this->info($slug)) {


            $result = $this->modules->where('slug', $slug)->first();

            if ($result) {
                return $this->modules->updateById($result->id, ['enabled' => 1]);

            } else {

                return $this->modules->create(['slug' => $module['slug'], 'enabled' => 1]);
            }

        }

        return null;
    }

    /**
     * @param $slug
     * @return null
     */
    public function info($slug)
    {
        if ($this->exist($slug)) {
            $module = $this->all()->get($slug);

            return $module;
        }

        return null;
    }

    /**
     * @param $slug
     * @return null
     */
    public function disable($slug)
    {
        if ($check = $this->info($slug)) {
            if (!$check['is_core']) {

                $module = $this->modules->where('slug', $slug)->first();

                if ($module) {
                    return $this->modules->updateById($module->id, ['enabled' => 0]);

                } else {

                    return $this->modules->create(['slug' => $check['slug'], 'enabled' => 0]);
                }
            }
        }


        return null;
    }

    /**
     * @param $slug
     * @return null
     */
    public function install($slug)
    {
        if ($module = $this->info($slug)) {

            $result = $this->modules->where('slug', $slug)->first();

            if ($result) {
                return $this->modules->updateById($result->id, ['installed' => 1]);

            } else {

                return $this->modules->create(['slug' => $module['slug'], 'installed' => 1]);
            }

        }

        return null;
    }


    /**
     * @param $slug
     * @return null
     */
    public function uninstall($slug)
    {
        if ($check = $this->info($slug)) {
            if (!$check['is_core']) {

                $module = $this->modules->where('slug', $slug)->first();

                if ($module) {
                    return $this->modules->updateById($module->id, ['installed' => 0]);

                } else {

                    return $this->modules->create(['slug' => $check['slug'], 'installed' => 0]);
                }
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