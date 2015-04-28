<?php namespace Veemo\Modules\Console;

use Veemo\Core\Contracts\Modules\ModulesInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleListCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string $name The console command name.
     */
    protected $name = 'veemo:module:list';

    /**
     * @var string $description The console command description.
     */
    protected $description = 'List all modules from artisan console';

    /**
     * @var \Veemo\Core\Contracts\Modules\ModulesInterface
     */
    protected $modules;


    /**
     * @var array $header The table headers for the command.
     */
    protected $headers = ['Name', 'Slug', 'Description', 'Core Module', 'Installed', 'Status'];


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
        $modules = $this->modules->getManager()->all();

        if (count($modules) == 0)
        {
            return $this->error("Your application doesn't have any modules.");
        }

        $this->displayModules();
    }



    /**
     * Display the module information on the console.
     *
     * @return void
     */
    protected function displayModules()
    {
        $result = [];
        $modules = $this->modules->getManager()->all();


        foreach($modules as $module)
        {
            $result[] = [
                'name'        => $module['name'],
                'slug'        => $module['slug'],
                'description' => $module['description'],
                'is_core'     => ($module['is_core']) ? 'Yes' : 'No',
                'installed'   => ($module['installed']) ? 'Yes' : 'No',
                'status'      => ($module['enabled']) ? 'Enabled' : 'Disabled'
            ];
        }


        $this->table($this->headers, $result);
    }


}
