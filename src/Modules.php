<?php
namespace Veemo\Modules;

use App;
use Countable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Veemo\Core\Contracts\Modules\ModulesInterface;
use Veemo\Modules\Exceptions\FileMissingException;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;


class Modules implements ModulesInterface
{
	/**
	 * @var \Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * @var string $path Path to the defined modules directory
	 */
	protected $path;


    /**
     * @var \Veemo\Modules\ModuleManagerInterface
     */
    protected $manager;




	/**
	 * Constructor method.
	 *
     * @param \Veemo\Modules\ModuleManagerInterface        $manager
	 * @param \Illuminate\Config\Repository                $config
	 * @param \Illuminate\Filesystem\Filesystem            $files
	 */
	public function __construct(ModuleManagerInterface $manager, Repository $config, Filesystem $files)
	{
        $this->manager = $manager;
		$this->config = $config;
		$this->files  = $files;
	}


    public function getManager()
    {
        return $this->manager;
    }


    public function install($slug, $enable = false)
    {
        if ($module = $this->manager->info($slug)) {

            if (!$module['installed']) {

                $permissions = $this->installPermissions($module);
                $settings = $this->installSettings($module);
                $installed = false;

                if ($permissions && $settings)
                {
                    if ($this->manager->install($slug))
                    {
                        if ($enable) {
                            $this->manager->enable($slug);
                        }

                        // Migrate and Seed module

                        $installed = true;
                    }
                }

                return ($installed == true) ? 'Module ' . $module['name'] .' installed succesfully.' : 'Module installation failed.';


            } else {
                return 'Module is already installed.';
            }

        } else {
            return 'Module you are trying to install doesnt exist';
        }

    }

    protected function installPermissions($module)
    {
        $permissions = app('App\Modules\Core\Users\Repositories\PermissionRepositoryInterface');
        $roles = app('App\Modules\Core\Users\Repositories\RoleRepositoryInterface');

        if (is_array($module['config']['permissions']) && (count($module['config']['permissions']) > 0))
        {
            foreach($module['config']['permissions'] as $type => $value)
            {
                if (is_array($value))
                {

                    try {

                        foreach ($value as $permission)
                        {
                            $new_permission = $permissions->create([
                                'slug'          => $type . '.' . $permission['slug'],
                                'name'          => $permission['name'],
                                'description'   => $permission['description'],

                                'module'        => $module['slug'],
                                'type'          => $type
                            ]);

                            // Assign created permission to default admin user
                            if ($new_permission)
                            {
                                $defaultAdminUser = $this->config->get('veemo.auth.users_default_admin_role');
                                $adminRole = $roles->where('slug', $defaultAdminUser)->first();

                                $adminRole->assignPermission($new_permission->id);
                            }


                        }

                    } catch (ModelNotFoundException $e) {
                        // skip
                    } catch (QueryException $e) {
                        // skip is exist
                    }

                }
            }

        }

        return true;
    }

    protected function installSettings($module)
    {
        return true;
    }


    public function uninstall($slug)
    {
        // TODO: Implement uninstall() method.
    }

    protected function uninstallPermissions($module)
    {
        $permissions = app('App\Modules\Core\Users\Repositories\PermissionRepositoryInterface');

        $permissions->where('module', $module['slug'])->delete();

        return true;
    }

    protected function uninstallSettings($module)
    {
        return true;
    }

    public function enable($slug)
    {
        if ($module = $this->manager->info($slug)) {

            if (!$module['enabled'])
            {
                $enabled = false;

                if ($this->manager->enable($slug))
                {
                    $enabled = true;
                }

                return ($enabled == true) ? 'Module ' . $module['name'] .' succesfully enabled.' : 'Failed to enable module.';

            } else {
                return 'Module is already enabled.';
            }

        } else {
            return 'Module you are trying to enable doesnt exist';
        }
    }

    public function disable($slug)
    {
        if ($module = $this->manager->info($slug)) {

            if ($module['enabled'])
            {
                $enabled = true;

                if ($this->manager->disable($slug))
                {
                    $enabled = false;
                }

                return ($enabled == false) ? 'Module ' . $module['name'] .' succesfully disabled.' : 'Failed to disable module. Maybe it is core module?';

            } else {
                return 'Module is already disabled.';
            }

        } else {
            return 'Module you are trying to disable doesnt exist';
        }
    }

    public function register($module)
    {
        // TODO: Implement register() method.
    }


    public function registerModules()
    {
        $modules = $this->manager->installed()->enabled()->getModules();

        foreach ($modules as $module)
        {
            // Register Module Service Provider
            $this->registerServiceProvider($module);

            // Build Backend Menu
            //

            // Build Frontend Menu
            //
        }

    }


    protected function registerServiceProvider($module)
    {
        $module_name    = studly_case($module['slug']);
        $file           = $module['path'] . "/Providers/{$module_name}ServiceProvider.php";
        $namespace      = $module['namespace'] ."\\Providers\\{$module_name}ServiceProvider";
        if (! $this->files->exists($file)) {
            $message = "Module [{$module_name}] must have a \"{$module_name}/Providers/{$module_name}ServiceProvider.php\" file for bootstrapping purposes.";
            throw new FileMissingException($message);
        }

        App::register($namespace);
    }






}
