<?php
namespace Veemo\Modules\Traits;

trait MigrationTrait
{
	/**
	 * Require (once) all migration files for the supplied module.
	 *
	 * @param  string $slug
	 * @return void
	 */
	protected function requireMigrations($slug)
	{
		$path = $this->getMigrationPath($slug);

		$migrations = $this->laravel['files']->glob($path.'*_*.php');


		foreach ($migrations as $migration) {
			$this->laravel['files']->requireOnce($migration);
		}
	}

    /**
     * Require (once) all migration files for the ALL modules.
     *
     * @return void
     */
    protected function requireAllMigrations()
    {
        $installed_modules = $this->laravel['veemo.modules']->getManager()->installed()->getModules();

        foreach($installed_modules as $module)
        {
            $current_module = $this->laravel['veemo.modules']->getManager()->info($module['slug']);
            $path = $current_module['path'] . '/Database/Migrations/';

            $migrations = $this->laravel['files']->glob($path.'*_*.php');


            foreach ($migrations as $migration) {
                $this->laravel['files']->requireOnce($migration);
            }

        }

    }

	/**
	 * Get migration directory path.
	 *
	 * @param  string $slug
	 * @return string
	 */
	protected function getMigrationPath($slug)
	{
        $module = $this->laravel['veemo.modules']->getManager()->info($slug);
		$path = $module['path'];

		return $path.'/Database/Migrations/';
	}
}
