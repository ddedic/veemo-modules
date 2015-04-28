<?php namespace Veemo\Modules\Console;

use Veemo\Core\Contracts\Modules\ModulesInterface;
use Illuminate\Database\Migrations\Migrator;
use Veemo\Modules\Traits\MigrationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleMigrateRollbackCommand extends Command
{
    use MigrationTrait, ConfirmableTrait;

    /**
     * @var string $name The console command name.
     */
    protected $name = 'veemo:module:migrate:rollback';

    /**
     * @var string $description The console command description.
     */
    protected $description = 'Rollback the last database migrations for a specific module';

    /**
     * @var \Veemo\Core\Contracts\Modules\ModulesInterface
     */
    protected $modules;

    /**
     * @var \Illuminate\Database\Migrations\Migrator $migrator The migrator instance.
     */
    protected $migrator;

    /**
     * Create a new command instance.
     *
     * @param \Veemo\Core\Contracts\Modules\ModulesInterface $modules
     * @param \Illuminate\Database\Migrations\Migrator $migrator
     */
    public function __construct(ModulesInterface $modules, Migrator $migrator)
    {
        parent::__construct();

        $this->modules = $modules;
        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->prepareDatabase();

        if (! $this->confirmToProceed()) return null;

        $module = $this->argument('module');

        if (isset($module)) {
            return $this->rollback($module);

        } else {

            $this->error('To be able to rollback module migration, you need to specify module slug.');
            return false;
        }
    }

    /**
     * Run the migration rollback for the specified module.
     *
     * @param  string $slug
     * @return mixed
     */
    protected function rollback($slug)
    {
        $moduleName = studly_case($slug);

        if ($this->modules->getManager()->exist($slug)) {

            $this->requireMigrations($slug);

            $this->call('migrate:rollback', [
                '--database' => $this->option('database'),
                '--force'    => $this->option('force'),
                '--pretend'  => $this->option('pretend'),
            ]);

        } else {
            return $this->error("Module [$moduleName] does not exist.");
        }

    }


    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        $this->migrator->setConnection($this->option('database'));

        if ( ! $this->migrator->repositoryExists())
        {
            $options = array('--database' => $this->option('database'));

            $this->call('migrate:install', $options);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [['module', InputArgument::OPTIONAL, 'Module slug.']];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.']
        ];
    }
}
