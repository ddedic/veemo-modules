<?php

return [
	
	/*
	|--------------------------------------------------------------------------
	| Path to Modules
	|--------------------------------------------------------------------------
	|
	| Define the path where you'd like to store your modules. Note that if you
	| choose a path that's outside of your public directory, you will need to
	| copy your module assets (CSS, images, etc.) to your public directory.
	|
	*/

	'path' => [
        'core'      =>  app_path('Modules/Core'),
        'addons'    =>  app_path('Modules/Addons'),
    ],

	/*
	|--------------------------------------------------------------------------
	| Modules Base Namespace
	|--------------------------------------------------------------------------
	|
	| Define the base namespace for your modules. Be sure to update this value
	| if you move your modules directory to a new path. This is primarily used
	| by the module:make Artisan command.
	|
	*/	

	'namespace' => [
        'core'      => 'App\Modules\Core\\',
        'addons'    =>  'App\Modules\Addons\\'
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules Config Filename
    |--------------------------------------------------------------------------
    |
    | Filename of the module configuration
    |
    */

    'moduleConfigFilename' => 'config.php'
	
];
