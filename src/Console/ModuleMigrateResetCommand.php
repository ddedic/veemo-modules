<?php
/**
 * Project: veemo.dev
 * User: ddedic
 * Email: dedic.d@gmail.com
 * Date: 28/04/15
 * Time: 02:52
 */

namespace Veemo\Modules\Console;


use Veemo\Core\Contracts\Modules\ModulesInterface;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ModuleMigrateResetCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string $name The console command name.
     */
    protected $name = 'veemo:module:migrate:reset';

    /**
     * @var string $description The console command description.
     */
    protected $description = 'Rollback all database migrations for a specific or all modules';

    /**
     * @var \Veemo\Core\Contracts\Modules\ModulesInterface;

     */
    protected $module;

    /**
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param \Veemo\Core\Contracts\Modules\ModulesInterface $modules
     * @param \Illuminate\Filesystem\Filesystem        $files
     * @param \Illuminate\Database\Migrations\Migrator $migrator
     */
    public function __construct(ModulesInterface $modules, Filesystem $files, Migrator $migrator)
    {
        parent::__construct();

        $this->modules   = $modules;
        $this->files    = $files;
        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) return null;

        $this->prepareDatabase();

        $module = $this->argument('module');

        if (isset($module)) {
            return $this->reset($module);

        } else {

            $this->error('To be able to reset module migration, you need to specify module slug.');
            return false;
        }
    }

    /**
     * Run the migration reset for the specified module.
     *
     * Migrations should be reset in the reverse order that they were
     * migrated up as. This ensures the database is properly reversed
     * without conflict.
     *
     * @param  string $slug
     * @return mixed
     */
    protected function reset($slug)
    {

        $pretend       = $this->input->getOption('pretend');
        $migrationPath = $this->getMigrationPath($slug);
        $migrations    = array_reverse($this->migrator->getMigrationFiles($migrationPath));

        if (count($migrations) == 0) {
            return $this->error('Nothing to rollback.');
        }

        foreach ($migrations as $migration) {
            $this->info('Migration: '. $migration . ' pending to be reseted.');
            $this->runDown($slug, $migration, $pretend);
        }
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  string $slug
     * @param  object $migration
     * @param  bool   $pretend
     * @return void
     */
    protected function runDown($slug, $migration, $pretend)
    {
        $migrationPath = $this->getMigrationPath($slug);
        $file          = (string) $migrationPath.$migration.'.php';
        $classFile     = implode('_', array_slice(explode('_', str_replace('.php', '', $file)), 4));
        $class         = studly_case($classFile);
        $table         = $this->laravel['config']['database.migrations'];

        include ($file);

        $instance = new $class;
        $instance->down();

        $this->laravel['db']->table($table)
            ->where('migration', $migration)
            ->delete();
    }

    /**
     * Get the console command parameters.
     *
     * @param  string $slug
     * @return array
     */
    protected function getParameters($slug)
    {
        $params = [];

        $params['--path'] = $this->getMigrationPath($slug);

        if ($option = $this->option('database')) {
            $params['--database'] = $option;
        }

        if ($option = $this->option('pretend')) {
            $params['--pretend'] = $option;
        }

        if ($option = $this->option('seed')) {
            $params['--seed'] = $option;
        }

        return $params;
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
            ['pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run.'],
            ['seed', null, InputOption::VALUE_OPTIONAL, 'Indicates if the seed task should be re-run.']
        ];
    }
}
