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

include ('../../../inc/includes.php');
Session::checkRight("storkmdm:storkmdm", PluginStorkmdmProfile::RIGHT_STORKMDM_USE);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$agent = new PluginStorkmdmAgent();
if (isset($_POST["add"])) {
   $agent->check(-1, CREATE, $_POST);
   if ($newID = $agent->add($_POST)) {
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($agent->getFormURL() . "?id=" . $newID);
      }
   }
   Html::back();
} else if (isset($_POST["update"])) {
   $agent->check($_POST['id'], UPDATE);
   $agent->update($_POST);
   Html::back();
} else if (isset($_POST["wipe"])) {
   $agent->check($_POST['id'], UPDATE);
   $agent->wipe($_POST);
   Html::back();
}  else if (isset($_POST["purge"])) {
   $agent->check($_POST['id'], PURGE);
   if ($agent->delete($_POST, 1)) {
      //Event::log($_POST["id"], $agent->getTypeName(Session::getPluralNumber()), 4, "inventory",
      //           //TRANS: %s is the user login
      //           sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   }
   $agent->redirectToList();
} else {
   Html::header(
         PluginStorkmdmAgent::getTypeName(Session::getPluralNumber()),
         "",
         "tools",
         "PluginStorkmdmMenu",
         "agent"
   );
   $agent->display(array('id' => $_GET["id"],
         'withtemplate' => $_GET["withtemplate"]));

   // Footer
   if (strstr($_SERVER['PHP_SELF'], "popup")) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
