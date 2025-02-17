<?php

include(__DIR__ . "/../vendor/autoload.php");

global $_GET;
$_GET['load'] = array("foobar");

$GLOBALS['_global_function_handler_e'] = 'my_global_function_handler_e';
// phpcs:ignore
$GLOBALS['wp_plugin_paths'] = array();

require_once ABSPATH . WPINC . '/version.php';
require_once ABSPATH . WPINC . '/compat.php';
require_once ABSPATH . WPINC . '/load.php';
require_once ABSPATH . WPINC . '/functions.php';
require_once ABSPATH . WPINC . '/pomo/mo.php';
require_once ABSPATH . WPINC . '/l10n.php';
require_once ABSPATH . WPINC . '/plugin.php';
require_once ABSPATH . WPINC . '/cache.php';
