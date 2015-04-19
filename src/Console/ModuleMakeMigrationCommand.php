<?php
namespace Veemo\Modules\Console;

use Veemo\Modules\Handlers\ModuleMakeMigrationHandler;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class ModuleMakeMigrationCommand extends Command
{
	/**
	 * @var string $name The console command name.
	 */
	protected $name = 'veemo:module:make-migration';

	/**
	 * @var string $description The console command description.
	 */
	protected $description = 'Create a new module migration file';

	/**
	 * @var \Veemo\Modules\Handlers\ModuleMakeMigrationHandler
	 */
	protected $handler;

	/**
	 * Create a new command instance.
	 *
	 * @param \Veemo\Modules\Handlers\ModuleMakeMigrationHandler $handler
	 */
	public function __construct(ModuleMakeMigrationHandler $handler)
	{
		parent::__construct();

		$this->handler = $handler;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		return $this->handler->fire($this, $this->argument('module'), $this->argument('table'));
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['module', InputArgument::REQUIRED, 'Module slug.'],
			['table', InputArgument::REQUIRED, 'Table name.']
		];
	}
}
