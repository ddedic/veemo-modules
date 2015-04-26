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


        //\Debugbar::info('Veemo Core/Modules Service Provider loaded');
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
		return ['modules'];
	}


	protected function registerServices()
	{
        // Bind ModuleRepository
        $this->app->bind('Veemo\Modules\Repositories\ModuleRepositoryInterface', function()
        {
            return $this->app['veemo.modules.moduleRepository'] = new Repositories\ModuleEloquentRepository(
                new Models\Module()
            );
        });


        //Setup ModuleManager
       $this->app->bindShared('veemo.modules.manager', function ($app) {
            return new ModuleManager($app['veemo.modules.moduleRepository'], $app['files'], $app['config']);
        });


        // Setup Modules
		$this->app->bindShared('veemo.modules', function ($app) {
			return new Modules($app['veemo.modules.manager'], $app['config'],	$app['files']);
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
		$this->registerMakeCommand();
		$this->registerEnableCommand();
		$this->registerDisableCommand();
		$this->registerMakeMigrationCommand();
		$this->registerMakeRequestCommand();
		$this->registerMigrateCommand();
		$this->registerMigrateRefreshCommand();
		$this->registerMigrateResetCommand();
		$this->registerMigrateRollbackCommand();
		$this->registerSeedCommand();
		$this->registerListCommand();

		$this->commands([
			'veemo.modules.make',
			'veemo.modules.enable',
			'veemo.modules.disable',
			'veemo.modules.makeMigration',
			'veemo.modules.makeRequest',
			'veemo.modules.migrate',
			'veemo.modules.migrateRefresh',
			'veemo.modules.migrateReset',
			'veemo.modules.migrateRollback',
			'veemo.modules.seed',
			'veemo.modules.list'
		]);
	}

	/**
	 * Register the "module:enable" console command.
	 *
	 * @return Console\ModuleEnableCommand
	 */
	protected function registerEnableCommand()
	{
		$this->app->bindShared('veemo.modules.enable', function() {
			return new Console\ModuleEnableCommand;
		});
	}

	/**
	 * Register the "module:disable" console command.
	 *
	 * @return Console\ModuleDisableCommand
	 */
	protected function registerDisableCommand()
	{
		$this->app->bindShared('veemo.modules.disable', function() {
			return new Console\ModuleDisableCommand;
		});
	}

	/**
	 * Register the "module:make" console command.
	 *
	 * @return Console\ModuleMakeCommand
	 */
	protected function registerMakeCommand()
	{
		$this->app->bindShared('veemo.modules.make', function($app) {
			$handler = new Handlers\ModuleMakeHandler($app['modules'], $app['files']);

			return new Console\ModuleMakeCommand($handler);
		});
	}

	/**
	 * Register the "module:make-migration" console command.
	 *
	 * @return Console\ModuleMakeMigrationCommand
	 */
	protected function registerMakeMigrationCommand()
	{
		$this->app->bindShared('veemo.modules.makeMigration', function($app) {
			$handler = new Handlers\ModuleMakeMigrationHandler($app['modules'], $app['files']);

			return new Console\ModuleMakeMigrationCommand($handler);
		});
	}

	/**
	 * Register the "module:make-request" console command.
	 *
	 * @return Console\ModuleMakeRequestCommand
	 */
	protected function registerMakeRequestCommand()
	{
		$this->app->bindShared('veemo.modules.makeRequest', function($app) {
			$handler = new Handlers\ModuleMakeRequestHandler($app['modules'], $app['files']);

			return new Console\ModuleMakeRequestCommand($handler);
		});
	}

	/**
	 * Register the "module:migrate" console command.
	 *
	 * @return Console\ModuleMigrateCommand
	 */
	protected function registerMigrateCommand()
	{
		$this->app->bindShared('veemo.modules.migrate', function($app) {
			return new Console\ModuleMigrateCommand($app['migrator'], $app['modules']);
		});
	}

	/**
	 * Register the "module:migrate-refresh" console command.
	 *
	 * @return Console\ModuleMigrateRefreshCommand
	 */
	protected function registerMigrateRefreshCommand()
	{
		$this->app->bindShared('veemo.modules.migrateRefresh', function() {
			return new Console\ModuleMigrateRefreshCommand;
		});
	}

	/**
	 * Register the "module:migrate-reset" console command.
	 *
	 * @return Console\ModuleMigrateResetCommand
	 */
	protected function registerMigrateResetCommand()
	{
		$this->app->bindShared('veemo.modules.migrateReset', function($app) {
			return new Console\ModuleMigrateResetCommand($app['modules'], $app['files'], $app['migrator']);
		});
	}

	/**
	 * Register the "module:migrate-rollback" console command.
	 *
	 * @return Console\ModuleMigrateRollbackCommand
	 */
	protected function registerMigrateRollbackCommand()
	{
		$this->app->bindShared('veemo.modules.migrateRollback', function($app) {
			return new Console\ModuleMigrateRollbackCommand($app['modules']);
		});
	}

	/**
	 * Register the "module:seed" console command.
	 *
	 * @return Console\ModuleSeedCommand
	 */
	protected function registerSeedCommand()
	{
		$this->app->bindShared('veemo.modules.seed', function($app) {
			return new Console\ModuleSeedCommand($app['modules']);
		});
	}

	/**
	 * Register the "module:list" console command.
	 *
	 * @return Console\ModuleListCommand
	 */
	protected function registerListCommand()
	{
		$this->app->bindShared('veemo.modules.list', function($app) {
			return new Console\ModuleListCommand($app['modules']);
		});
	}
}
