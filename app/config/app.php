<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| When your application is in debug mode, detailed error messages with
	| stack traces will be shown on every error that occurs within your
	| application. If disabled, a simple generic error page is shown.
	|
	*/

	'debug'     => $_ENV[ 'DEBUG' ],

	/*
	|--------------------------------------------------------------------------
	| Application URL
	|--------------------------------------------------------------------------
	|
	| This URL is used by the console to properly generate URLs when using
	| the Artisan command line tool. You should set this to the root of
	| your application so that it is used when running Artisan tasks.
	|
	*/

	'url'       => 'http://localhost',

	/*
	|--------------------------------------------------------------------------
	| Application Timezone
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default timezone for your application, which
	| will be used by the PHP date and date-time functions. We have gone
	| ahead and set this to a sensible default for you out of the box.
	|
	*/

	'timezone'  => 'UTC',

	/*
	|--------------------------------------------------------------------------
	| Application Locale Configuration
	|--------------------------------------------------------------------------
	|
	| The application locale determines the default locale that will be used
	| by the translation service provider. You are free to set this value
	| to any of the locales which will be supported by the application.
	|
	*/

	'locale'    => 'en',

	/*
	|--------------------------------------------------------------------------
	| Encryption Key
	|--------------------------------------------------------------------------
	|
	| This key is used by the Illuminate encrypter service and should be set
	| to a random, 32 character string, otherwise these encrypted strings
	| will not be safe. Please do this before deploying an application!
	|
	*/

	'key'       => 'HgP6i2FFDH6Lx78dPMdTSL3H97fRzvwE',

	/*
	|--------------------------------------------------------------------------
	| Autoloaded Service Providers
	|--------------------------------------------------------------------------
	|
	| The service providers listed here will be automatically loaded on the
	| request to your application. Feel free to add your own services to
	| this array to grant expanded functionality to your applications.
	|
	*/

	'providers' => array(

		'Illuminate\Foundation\Providers\ArtisanServiceProvider',
		'Illuminate\Auth\AuthServiceProvider',
		'Illuminate\Cache\CacheServiceProvider',
		'Illuminate\Session\CommandsServiceProvider',
		'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
		'Illuminate\Routing\ControllerServiceProvider',
		'Illuminate\Cookie\CookieServiceProvider',
		'Illuminate\Database\DatabaseServiceProvider',
		'Illuminate\Encryption\EncryptionServiceProvider',
		'Illuminate\Filesystem\FilesystemServiceProvider',
		'Illuminate\Hashing\HashServiceProvider',
		'Illuminate\Html\HtmlServiceProvider',
		'Illuminate\Log\LogServiceProvider',
		'Illuminate\Mail\MailServiceProvider',
		'Illuminate\Database\MigrationServiceProvider',
		'Illuminate\Pagination\PaginationServiceProvider',
		'Illuminate\Queue\QueueServiceProvider',
		'Illuminate\Redis\RedisServiceProvider',
		'Illuminate\Remote\RemoteServiceProvider',
		'Illuminate\Auth\Reminders\ReminderServiceProvider',
		'Illuminate\Database\SeedServiceProvider',
		'Illuminate\Session\SessionServiceProvider',
		'Illuminate\Translation\TranslationServiceProvider',
		'Illuminate\Validation\ValidationServiceProvider',
		'Illuminate\View\ViewServiceProvider',
		'Illuminate\Workbench\WorkbenchServiceProvider',
		'Way\Generators\GeneratorsServiceProvider',
		'Rtablada\PackageInstaller\PackageInstallerServiceProvider',
		'LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider',
		'MobStar\Storage\StorageServiceProvider',
		'Rafasamp\Sonus\SonusServiceProvider',
		'Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider',
		'Intervention\Image\ImageServiceProvider',
		'GrahamCampbell\Flysystem\FlysystemServiceProvider'
	),

	/*
	|--------------------------------------------------------------------------
	| Service Provider Manifest
	|--------------------------------------------------------------------------
	|
	| The service provider manifest is used by Laravel to lazy load service
	| providers which are not needed for each request, as well to keep a
	| list of all of the services. Here, you may set its storage spot.
	|
	*/

	'manifest'  => storage_path() . '/meta',

	/*
	|--------------------------------------------------------------------------
	| Class Aliases
	|--------------------------------------------------------------------------
	|
	| This array of class aliases will be registered when this application
	| is started. However, feel free to register as many as you wish as
	| the aliases are "lazy" loaded so they don't hinder performance.
	|
	*/

	'aliases'   => array(

		'App'                 => 'Illuminate\Support\Facades\App',
		'Artisan'             => 'Illuminate\Support\Facades\Artisan',
		'Auth'                => 'Illuminate\Support\Facades\Auth',
		'Blade'               => 'Illuminate\Support\Facades\Blade',
		'Cache'               => 'Illuminate\Support\Facades\Cache',
		'ClassLoader'         => 'Illuminate\Support\ClassLoader',
		'Config'              => 'Illuminate\Support\Facades\Config',
		'Controller'          => 'Illuminate\Routing\Controller',
		'Cookie'              => 'Illuminate\Support\Facades\Cookie',
		'Crypt'               => 'Illuminate\Support\Facades\Crypt',
		'DB'                  => 'Illuminate\Support\Facades\DB',
		'Eloquent'            => 'Illuminate\Database\Eloquent\Model',
		'Event'               => 'Illuminate\Support\Facades\Event',
		'File'                => 'Illuminate\Support\Facades\File',
		'Form'                => 'Illuminate\Support\Facades\Form',
		'Hash'                => 'Illuminate\Support\Facades\Hash',
		'HTML'                => 'Illuminate\Support\Facades\HTML',
		'Input'               => 'Illuminate\Support\Facades\Input',
		'Lang'                => 'Illuminate\Support\Facades\Lang',
		'Log'                 => 'Illuminate\Support\Facades\Log',
		'Mail'                => 'Illuminate\Support\Facades\Mail',
		'Paginator'           => 'Illuminate\Support\Facades\Paginator',
		'Password'            => 'Illuminate\Support\Facades\Password',
		'Queue'               => 'Illuminate\Support\Facades\Queue',
		'Redirect'            => 'Illuminate\Support\Facades\Redirect',
		'Redis'               => 'Illuminate\Support\Facades\Redis',
		'Request'             => 'Illuminate\Support\Facades\Request',
		'Response'            => 'Illuminate\Support\Facades\Response',
		'Route'               => 'Illuminate\Support\Facades\Route',
		'Schema'              => 'Illuminate\Support\Facades\Schema',
		'Seeder'              => 'Illuminate\Database\Seeder',
		'Session'             => 'Illuminate\Support\Facades\Session',
		'SSH'                 => 'Illuminate\Support\Facades\SSH',
		'Str'                 => 'Illuminate\Support\Str',
		'URL'                 => 'Illuminate\Support\Facades\URL',
		'Validator'           => 'Illuminate\Support\Facades\Validator',
		'View'                => 'Illuminate\Support\Facades\View',
		'AuthorizationServer' => 'LucaDegasperi\OAuth2Server\Facades\AuthorizationServerFacade',
		'ResourceServer'      => 'LucaDegasperi\OAuth2Server\Facades\ResourceServerFacade',
		'Sonus'               => 'Rafasamp\Sonus\Facade',
		'Image'               => 'Intervention\Image\Facades\Image',
		'Flysystem'           => 'GrahamCampbell\Flysystem\Facades\Flysystem',
		'Creds'               => 'Aws\Common\Credentials\Credentials'
	),

	'bucket' => isset( $_ENV[ 'bucket' ] ) ? $_ENV[ 'bucket' ] : '',

	'home' => isset( $_ENV['PATH'] ) ? $_ENV['PATH'] : realpath( __DIR__.'/../../' ),

	'tmp' => isset( $_ENV['TMP'] ) ? $_ENV['TMP'] : sys_get_temp_dir(),

	'url_admin' => isset( $_ENV['URL_ADMIN'] ) ? $_ENV['URL_ADMIN'] : 'admin.mobstar.com',

	'bin_ffmpeg' => $_ENV['BIN_FFMPEG'],

	'bin_ffprobe' => $_ENV['BIN_FFPROBE'],

	'disable_sns' => empty( $_ENV['DISABLE_SNS'] ) ? false : true,

    'apple_arn' => isset( $_ENV['APPLE_ARN'] ) ? $_ENV['APPLE_ARN'] : '',

    'android_arn' => isset( $_ENV['ANDROID_ARN'] ) ? $_ENV['ANDROID_ARN'] : '',

    'updateTopic_arn' => isset( $_ENV['UPDATE_TOPIC_ARN'] ) ? $_ENV['UPDATE_TOPIC_ARN'] : '',

    'google_apikey' => isset( $_ENV['GOOGLE_API_KEY'] ) ? $_ENV['GOOGLE_API_KEY'] : '',

	'disable_youtube_upload' => empty( $_ENV['DISABLE_YOUTUBE_UPLOAD'] ) ? false : true,

    'keep_uploaded_entry_files' => empty( $_ENV['KEEP_UPLOADED_ENTRY_FILES'] ) ? false : true,

    'force_include_all_world' => empty( $_ENV['FORCE_INCLUDE_ALL_WORLD'] ) ? false : true,
);
