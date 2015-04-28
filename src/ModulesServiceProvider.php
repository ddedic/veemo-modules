<?php
namespace Veemo\Modules;

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
        $this->registerModuleListCommand();
        $this->registerModuleInstallCommand();
        $this->registerModuleUnInstallCommand();
        $this->registerModuleEnableCommand();
        $this->registerModuleDisableCommand();

        $this->registerModuleMigrateCommand();
        $this->registerModuleMigrateRollbackCommand();
        $this->registerModuleMigrateRefreshCommand();
        $this->registerModuleMigrateResetCommand();
        $this->registerModuleSeedCommand();


        $this->commands([
            'veemo.module.list',
            'veemo.module.install',
            'veemo.module.uninstall',
            'veemo.module.enable',
            'veemo.module.disable',
            'veemo.module.migrate',
            'veemo.module.migrate.rollback',
            'veemo.module.migrate.refresh',
            'veemo.module.migrate.reset',
            'veemo.module.seed'
        ]);
	}


    /**
     * Register the "veemo:module:migrate" console command.
     *
     * @return Console\ModuleMigrateCommand
     */
    protected function registerModuleMigrateCommand()
    {
        $this->app->bindShared('veemo.module.migrate', function($app) {
            return new Console\ModuleMigrateCommand($app['migrator'], $app['veemo.modules']);
        });
    }


    /**
     * Register the "veemo:module:seed" console command.
     *
     * @return Console\ModuleSeedCommand
     */
    protected function registerModuleSeedCommand()
    {
        $this->app->bindShared('veemo.module.seed', function($app) {
            return new Console\ModuleSeedCommand($app['veemo.modules'], $app['files'], $app['migrator']);
        });
    }


    /**
     * Register the "veemo:module:migrate:rollback" console command.
     *
     * @return Console\ModuleMigrateRollbackCommand
     */
    protected function registerModuleMigrateRollbackCommand()
    {
        $this->app->bindShared('veemo.module.migrate.rollback', function($app) {
            return new Console\ModuleMigrateRollbackCommand($app['veemo.modules'], $app['migrator']);
        });
    }


    /**
     * Register the "veemo:module:migrate:refresh" console command.
     *
     * @return Console\ModuleMigrateRefreshCommand
     */
    protected function registerModuleMigrateRefreshCommand()
    {
        $this->app->bindShared('veemo.module.migrate.refresh', function() {
            return new Console\ModuleMigrateRefreshCommand;
        });
    }


    /**
     * Register the "veemo:module:migrate:reset" console command.
     *
     * @return Console\ModuleMigrateResetCommand
     */
    protected function registerModuleMigrateResetCommand()
    {
        $this->app->bindShared('veemo.module.migrate.reset', function($app) {
            return new Console\ModuleMigrateResetCommand($app['veemo.modules'], $app['files'], $app['migrator']);
        });
    }


    /**
     * Register the "veemo:module:list" console command.
     *
     * @return Console\ModuleListCommand
     */
    protected function registerModuleListCommand()
    {
        $this->app->bindShared('veemo.module.list', function($app) {
            return new Console\ModuleListCommand($app['veemo.modules']);
        });
    }


    /**
     * Register the "veemo:module:install" console command.
     *
     * @return Console\ModuleInstallCommand
     */
    protected function registerModuleInstallCommand()
    {
        $this->app->bindShared('veemo.module.install', function($app) {
            return new Console\ModuleInstallCommand($app['veemo.modules']);
        });
    }

    /**
     * Register the "veemo:module:uninstall" console command.
     *
     * @return Console\ModuleUnInstallCommand
     */
    protected function registerModuleUnInstallCommand()
    {
        $this->app->bindShared('veemo.module.uninstall', function($app) {
            return new Console\ModuleUnInstallCommand($app['veemo.modules']);
        });
    }

    /**
     * Register the "veemo:module:enable" console command.
     *
     * @return Console\ModuleEnableCommand
     */
    protected function registerModuleEnableCommand()
    {
        $this->app->bindShared('veemo.module.enable', function($app) {
            return new Console\ModuleEnableCommand($app['veemo.modules']);
        });
    }

    /**
     * Register the "veemo:module:disable" console command.
     *
     * @return Console\ModuleEnableCommand
     */
    protected function registerModuleDisableCommand()
    {
        $this->app->bindShared('veemo.module.disable', function($app) {
            return new Console\ModuleDisableCommand($app['veemo.modules']);
        });
    }

}
