<?php namespace Veemo\Modules\Console;

use Veemo\Core\Contracts\Modules\ModulesInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleDisableCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string $name The console command name.
     */
    protected $name = 'veemo:module:disable';

    /**
     * @var string $description The console command description.
     */
    protected $description = 'Disable specific module from artisan console';

    /**
     * @var \Veemo\Core\Contracts\Modules\ModulesInterface
     */
    protected $modules;



    /**
     * Create a new command instance.
     *
     * @param \Veemo\Core\Contracts\Modules\ModulesInterface $modules
     */
    public function __construct(ModulesInterface $modules)
    {
        parent::__construct();

        $this->modules = $modules;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) return null;

        $module = $this->argument('module');

        if (isset($module)) {
            return $this->disable($module);

        } else {

            $this->error('To disable module, you need to specify module slug.');
            return false;
        }
    }

    /**
     * Disable specified module
     *
     * @param  string $slug
     * @return mixed
     */
    protected function disable($slug)
    {
        $output = $this->modules->disable($slug);

        $this->line($output);
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
        ];
    }
}
