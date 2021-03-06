<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

(PHP_SAPI == 'cli') or die("Only available from command line");

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include (__DIR__ . "/docopt.php");

$doc = <<<DOC
cli_install.php

Usage:
   cli_install.php [--as-user USER] [--api-user-token APITOKEN] [ --tests ]

Options:
   --as-user USER       Do install/upgrade as specified USER. If not provided, 'glpi' user will be used
   --api-user-token APITOKEN    APITOKEN
   --tests              Use GLPi test database

DOC;

$docopt = new \Docopt\Handler();
$args = $docopt->handle($doc);

$asUser = 'glpi';
if (!is_null($args['--as-user'])) {
   $asUser = $args['--as-user'];
}
if (isset($args['--tests']) && $args['--tests'] !== false ) {
   // Use test GLPi's database
   // Requires use of cliinstall of GLPI with --tests argument
   define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
   define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
}

include (__DIR__ . "/../../../inc/includes.php");

// Init debug variable
$_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
$_SESSION['glpilanguage']  = "en_GB";

Session::loadLanguage();

// Only show errors
$CFG_GLPI["debug_sql"]        = $CFG_GLPI["debug_vars"] = 0;
$CFG_GLPI["use_log_in_files"] = 1;
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
//set_error_handler('userErrorHandlerDebug');

// Prevent problem of execution time
ini_set("max_execution_time", "0");
ini_set("memory_limit", "-1");
ini_set("session.use_cookies","0");

$DB = new DB();
if (!$DB->connected) {
   die("No DB connection\n");
}

$user = new User();
$user->getFromDBbyName($asUser);
$auth = new Auth();
$auth->auth_succeded = true;
$auth->user = $user;
Session::init($auth);

$apiUserToken = $args['--api-user-token'];

/*---------------------------------------------------------------------*/

if (!TableExists("glpi_configs")) {
   echo "GLPI not installed\n";
   exit(1);
}

$plugin = new Plugin();

// Install the plugin
$plugin->getFromDBbyDir("storkmdm");
print("Installing Plugin Id: " . $plugin->fields['id'] . " version " . $plugin->fields['version'] . "\n");
ob_start(function($in) { return ''; });
$plugin->install($plugin->fields['id']);
ob_end_clean();
print("Install Done\n");
if($apiUserToken){
   $serviceUser = PluginStorkmdmConfig::SERVICE_ACCOUNT_NAME;
   $storkUser = new User();
   $storkUser->getFromDBbyName($serviceUser);
   $sqlUpdate = "update glpi_users set personal_token = '" . $apiUserToken . "' where id = ". $storkUser->fields['id'];
   $DB->query($sqlUpdate);
   print("update $serviceUser user with provided api token " . $apiUserToken . "\n");
}

print("setting queuedmail to Queue mode\n");
$cronQuery = "update glpi_crontasks set mode = 2 where name ='queuedmail'";
$DB->query($cronQuery);


print("opening glpi client api access\n");
$apiClientQuery = "update glpi_apiclients set name = 'full access flyve', ipv4_range_start = null, ipv4_range_end = null  where name like 'full access from localhost'";
$DB->query($apiClientQuery);

// Enable the plugin
print("Activating Plugin...\n");
$plugin->activate($plugin->fields['id']);
if (!$plugin->activate($plugin->fields['id'])) {
   print("Activation failed\n");
   exit(1);
}
print("Activation Done\n");

//Load the plugin
print("Loading Plugin...\n");
$plugin->load("storkmdm");
print("Load Done...\n");

