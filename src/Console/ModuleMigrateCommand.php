<?php namespace Veemo\Modules\Console;

use Veemo\Core\Contracts\Modules\ModulesInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleMigrateCommand extends Command
{
    /**
     * @var string $name The console command name.
     */
    protected $name = 'veemo:module:migrate';

    /**
     * @var string $description The console command description.
     */
    protected $description = 'Run the database migrations for a specific module';

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
     * @param \Illuminate\Database\Migrations\Migrator $migrator
     * @param \Veemo\Core\Contracts\Modules\ModulesInterface $modules
     */
    public function __construct(Migrator $migrator, ModulesInterface $modules)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->modules   = $modules;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->prepareDatabase();

        $module = $this->argument('module');

        if (isset($module)) {
            return $this->migrate($module);

        } else {

            $this->error('To be able to migrate module, you need to specify module slug.');
            return false;
        }
    }

    /**
     * Run migrations for the specified module.
     *
     * @param  string $slug
     * @return mixed
     */
    protected function migrate($slug)
    {
        $moduleName = studly_case($slug);

        if ($this->modules->getManager()->exist($slug)) {
            $pretend = $this->option('pretend');
            $path    = $this->getMigrationPath($slug);


            $this->migrator->run($path, $pretend);

            // Once the migrator has run we will grab the note output and send it out to
            // the console screen, since the migrator itself functions without having
            // any instances of the OutputInterface contract passed into the class.
            foreach ($this->migrator->getNotes() as $note)
            {
                $this->output->writeln($note);
            }

            // Finally, if the "seed" option has been given, we will re-run the database
            // seed task to re-populate the database, which is convenient when adding
            // a migration and a seed at the same time, as it is only this command.
            if ($this->option('seed'))
            {
                $this->call('vemoo:module:seed', ['module' => $slug, '--force']);
            }
        } else {
            return $this->error("Module [$moduleName] does not exist.");
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
        $module = $this->modules->getManager()->info($slug);

        return $module['path'] . '/Database/Migrations/';
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
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
        ];
    }
}
