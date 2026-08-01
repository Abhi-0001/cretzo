<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Database config used ONLY when ENVIRONMENT === 'testing' (CI_ENV=testing).
 * CodeIgniter loads this file instead of the main application/config/database.php
 * when it exists (see system/database/DB.php) — so this never touches the
 * development/production credentials in the base file. Used by Docker/CI only;
 * never set CI_ENV=testing on the real server.
 */
$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => getenv('DB_HOST') ?: 'db',
	'port' => getenv('DB_PORT') ?: 3306,
	'username' => getenv('DB_USER') ?: 'root',
	'password' => getenv('DB_PASS') ?: 'root',
	'database' => getenv('DB_NAME') ?: 'cretzo_test',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);
