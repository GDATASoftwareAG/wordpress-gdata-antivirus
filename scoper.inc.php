<?php

declare(strict_types=1);
use Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

// Example of collecting files to include in the scoped build but to not scope
// leveraging the isolated finder.
// $excludedFiles = array_map(
//     static fn (SplFileInfo $fileInfo) => $fileInfo->getPathName(),
//     iterator_to_array(
//         Finder::create()->files()->in(__DIR__),
//         false,
//     ),
// );
$excludedFiles = [];
$outputDir = "scoped-code";

return [
    // The prefix configuration. If a non-null value is used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => "GDAVSCOPE",
    // The base output directory for the prefixed files.
    // This will be overridden by the 'output-dir' command line option if present.
    'output-dir' => $outputDir,
    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
			->ignoreDotFiles(true)
            ->notName([
                'test.php',
                'scoper.inc.php',
                '*\.ini',
                '*\.sh',
                '*\.exe',
                '*\.zip',
                '*\.h',
                '*\.yml',
                'Makefile',
                'renovate.json',
                'psalm.xml',
                'Dockerfile*',
                'info.php',
                'xdebug.php',
                'composer-require-check.json',
                'phpunit\.xml*'
            ])
            ->append([
                '.zipignore'
            ])
            ->exclude([
                'examples',
                'tests',
                'test',
                'docs',
                'wordpress',
                'svn',
                $outputDir
            ])
            ->in(__DIR__)
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
        // 'src/an-excluded-file.php',
        ...$excludedFiles,
    ],

    // PHP version (e.g. `'7.2'`) in which the PHP parser and printer will be configured into. This will affect what
    // level of code it will understand and how the code will be printed.
    // If none (or `null`) is configured, then the host version will be used.
    'php-version' => null,

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            if (str_ends_with($filePath, 'vendor/netresearch/jsonmapper/src/JsonMapper.php') === true) {
                $contents = str_replace("namespace $prefix;", "namespace $prefix\\JsonMapper;", $contents);
            }
            if (str_ends_with($filePath, 'vendor/netresearch/jsonmapper/src/JsonMapper/Exception.php') === true) {
                $contents = str_replace("namespace $prefix;", "namespace $prefix\\JsonMapper;", $contents);
            }
            if (str_ends_with($filePath, 'vendor/gdata/vaas/Vaas.php') === true) {
                $contents = str_replace("use $prefix\JsonMapper", "use $prefix\JsonMapper\JsonMapper", $contents);
            }
            return $contents;
        },
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        "Composer",
        "WP_Filesystem_Base",
        "Gdatacyberdefenseag\GdataAntivirus"
    ],
    'exclude-classes' => [
        "RecursiveIteratorIterator",
        "RecursiveDirectoryIterator",
        "FilesystemIterator",
        "SplFileInfo",
        "WP_Filesystem_Direct",
        "WP_Filesystem_Base",
        "GdataAntivirusPlugin"
    ],
    'exclude-functions' => [
        // 'mb_str_split',
    ],
    'exclude-constants' => [
        'GDATACYBERDEFENCEAG_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__',
        'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG',
        'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_SLUG',
        'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME',
        'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG',
        'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME',
        'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG'
    ],

    // List of symbols to expose.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'expose-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
    ],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
