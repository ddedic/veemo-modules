<?php
namespace Veemo\Modules;

use Veemo\Modules\Handlers\ModulesHandler;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
	/**
	 * @var bool $defer Indicates if loading of the provider is deferred.
	 */
	protected $defer = false;

	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/Config/modules.php' => config_path('veemo/modules.php'),
		]);

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(
			__DIR__.'/Config/modules.php', 'veemo.modules'
		);


		$this->registerServices();

		$this->registerRepository();

		// Once we have registered the migrator instance we will go ahead and register
		// all of the migration related commands that are used by the "Artisan" CLI
		// so that they may be easily accessed for registering with the consoles.
		$this->registerMigrator();

		$this->registerConsoleCommands();
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return string
	 */
	public function provides()
	{
		return ['veemo.modules'];
	}


	protected function registerServices()
	{

        // Bind ModuleRepository
        $this->app->bind('Veemo\Modules\Repositories\ModuleRepositoryInterface', function()
        {
            return  new Repositories\ModuleEloquentRepository(
                new Models\Module()
            );
        });


        //Setup ModuleManager
       $this->app->bindShared('veemo.modules.manager', function ($app) {
           $moduleRepo = $app->make('Veemo\Modules\Repositories\ModuleRepositoryInterface');

           return new ModuleManager($moduleRepo, $app['files'], $app['config']);
        });


        // Setup Modules
		$this->app->bindShared('veemo.modules', function ($app) {
			return new Modules($app['veemo.modules.manager'], $app['config'], $app['files']);
		});




        // Register Modules
		$this->app->booting(function ($app) {
			//$app['modules']->register();
		});
	}

	/**
	 * Register the migration repository service.
	 *
	 * @return void
	 */
	protected function registerRepository()
	{
		$this->app->singleton('migration.repository', function($app)
		{
			$table = $app['config']['database.migrations'];

			return new DatabaseMigrationRepository($app['db'], $table);
		});
	}

	/**
	 * Register the migrator service.
	 *
	 * @return void
	 */
	protected function registerMigrator()
	{
		// The migrator is responsible for actually running and rollback the migration
		// files in the application. We'll pass in our database connection resolver
		// so the migrator can resolve any of these connections when it needs to.
		$this->app->singleton('migrator', function($app)
		{
			$repository = $app['migration.repository'];

			return new Migrator($repository, $app['db'], $app['files']);
		});
	}

	/**
	 * Register the package console commands.
	 *
	 * @return void
	 */
	protected function registerConsoleCommands()
	{

	}





}
