<?php
namespace Veemo\Modules;

use App;
use Countable;
use Veemo\Core\Contracts\Modules\ModulesInterface;
use Veemo\Modules\Exceptions\FileMissingException;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Modules implements ModulesInterface
{
	/**
	 * @var \Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * @var string $path Path to the defined modules directory
	 */
	protected $path;


    /**
     * @var \Veemo\Modules\ModuleManagerInterface
     */
    protected $manager;

	/**
	 * Constructor method.
	 *
	 * @param \Illuminate\Config\Repository                $config
	 * @param \Illuminate\Filesystem\Filesystem            $files
	 */
	public function __construct(ModuleManagerInterface $manager, Repository $config, Filesystem $files)
	{
        $this->manager = $manager;
		$this->config = $config;
		$this->files  = $files;
	}


    public function install($slug)
    {
        // TODO: Implement install() method.
    }

    public function uninstall($slug)
    {
        // TODO: Implement uninstall() method.
    }

    public function enable($slug)
    {
        // TODO: Implement enable() method.
    }

    public function disable($slug)
    {
        // TODO: Implement disable() method.
    }

    public function register($slug)
    {
        // TODO: Implement register() method.
    }

    public function registerCoreModules()
    {
        // TODO: Implement registerCoreModules() method.
    }

    public function registerAddonModules()
    {
        // TODO: Implement registerAddonModules() method.
    }

    public function registerModules()
    {
        // TODO: Implement registerModules() method.
    }
}
