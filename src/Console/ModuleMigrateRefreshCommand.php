<?php namespace Veemo\Modules\Console;
/**
 * Project: veemo.dev
 * User: ddedic
 * Email: dedic.d@gmail.com
 * Date: 28/04/15
 * Time: 02:46
 */


use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ModuleMigrateRefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string $name The console command name.
     */
    protected $name = 'veemo:module:migrate:refresh';

    /**
     * @var string $description The console command description.
     */
    protected $description = 'Reset and re-run all migrations for a specific module';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) return null;

        $module     = $this->argument('module');
        $moduleName = Str::studly($module);

        $this->call('veemo:module:migrate:reset', [
            'module'     => $module,
            '--database' => $this->option('database'),
            '--force'    => $this->option('force'),
            '--pretend'  => $this->option('pretend'),
        ]);

        $this->call('veemo:module:migrate', [
            'module'     => $module,
            '--database' => $this->option('database')
        ]);

        if ($this->needsSeeding()) {
            $this->runSeeder($module, $this->option('database'));
        }

        if (isset($module)) {
            $this->info("Module [$moduleName] has been refreshed.");
        } else {
            $this->error("To be able to refresh module migration, you need to specify module slug.");
        }
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed');
    }

    /**
     * Run the module seeder command.
     *
     * @param  string $database
     * @return void
     */
    protected function runSeeder($module = null, $database = null)
    {
        $this->call('veemo:module:seed', [
            'module'     => $this->argument('module'),
            '--database' => $database
        ]);
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
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.']
        ];
    }
}