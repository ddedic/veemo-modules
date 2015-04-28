<?php
/**
 * Project: veemo.dev
 * User: ddedic
 * Email: dedic.d@gmail.com
 * Date: 28/04/15
 * Time: 01:24
 */

namespace Veemo\Modules\Console;

use Illuminate\Filesystem\Filesystem;
use Veemo\Core\Contracts\Modules\ModulesInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ModuleSeedCommand extends Command {
    /**
     * @var string $name The console command name.
     */
    protected $name = 'veemo:module:seed';

    /**
     * @var string $description The console command description.
     */
    protected $description = 'Seed the database with records for a specific module';

    /**
     * @var \Veemo\Core\Contracts\Modules\ModulesInterface
     */
    protected $modules;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Database\Migrations\Migrator $migrator The migrator instance.
     */
    protected $migrator;

    /**
     * Create a new command instance.
     *
     * @param \Veemo\Core\Contracts\Modules\ModulesInterface $modules
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Illuminate\Database\Migrations\Migrator $migrator
     */
    public function __construct(ModulesInterface $modules, Filesystem $files, Migrator $migrator)
    {
        parent::__construct();

        $this->modules = $modules;
        $this->files = $files;
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

        $module = $this->argument('module');

        if (isset($module)) {
            return $this->seed($module);

        } else {

            $this->error('To be able to seed module, you need to specify module slug.');
            return false;
        }

    }

    /**
     * Seed the specific module.
     *
     * @param  string $slug
     * @return array
     */
    protected function seed($slug)
    {
        $moduleName = studly_case($slug);

        if ($module = $this->modules->getManager()->info($slug)) {

            $params     = array();
            $path       = $module['path'];
            $namespace  = $module['namespace'];

            $rootSeeder = $moduleName.'DatabaseSeeder';
            $fullPath   = $path.'/Database/Seeds/'.$rootSeeder . '.php';
            $fullNamespace = $namespace . '\\Database\\Seeds\\' . $rootSeeder;

            if ($this->files->exists($fullPath)) {
                if ($this->option('class')) {
                    $params['--class'] = $this->option('class');
                } else {
                    $params['--class'] = $fullNamespace;
                }


                if ($option = $this->option('database')) {
                    $params['--database'] = $option;
                }


                $this->call('db:seed', $params);
            }

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
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the module\'s root seeder.'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.']
        ];
    }
} 