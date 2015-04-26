<?php
/**
 * Project: Veemo
 * User: ddedic
 * Email: dedic.d@gmail.com
 * Date: 16/03/15
 * Time: 23:46
 */

namespace Veemo\Modules;


/**
 * Interface ModuleManagerInterface
 * @package Veemo\Modules
 */
interface ModuleManagerInterface
{

    public function all();

    public function getModules();

    public function exist($slug);

    public function info($slug);

    public function enabled();

    public function disabled();

    public function installed();

    public function isEnabled($slug);

    public function isDisabled($slug);

    public function isCore($slug);

    public function isInstalled($slug);

    public function enable($slug);

    public function disable($slug);

    public function install($slug);

    public function uninstall($slug);

} 