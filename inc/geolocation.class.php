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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginStorkmdmGeolocation extends CommonDBTM {

   // name of the right in DB
   static $rightname = 'storkmdm:geolocation';

   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory                   = false;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad               = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights         = true;

   public static $types                = array('Computer');

   /**
    * Localized name of the type
    * @param $nb integer number of item in the type (default 0)
    */
   public static function getTypeName($nb = 0) {
      global $LANG;
      return _n('Geolocation', 'Geolocations', $nb, "storkmdm");
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::getRights()
    */
   public function getRights($interface='central') {
      $rights = parent::getRights();
      //$values = array(READ    => __('Read'),
      //                PURGE   => array('short' => __('Purge'),
      //                                 'long'  => _x('button', 'Delete permanently')));

      //$values += ObjectLock::getRightsToAdd( get_class($this), $interface ) ;

      //if ($this->maybeDeleted()) {
      //   $values[DELETE] = array('short' => __('Delete'),
      //                           'long'  => _x('button', 'Put in dustbin'));
      //}
      //if ($this->usenotepad) {
      //   $values[READNOTE] = array('short' => __('Read notes'),
      //                             'long' => __("Read the item's notes"));
      //   $values[UPDATENOTE] = array('short' => __('Update notes'),
      //                               'long' => __("Update the item's notes"));
      //}

      return $rights;
   }

   /**
    *
    * {@inheritDoc}
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      if (!isset($input['computers_id'])) {
         Session::addMessageAfterRedirect(__('associated device is mandatory', 'storkmdm'));
         return false;
      }
      if (!isset($input['latitude']) || !isset($input['longitude'])) {
         Session::addMessageAfterRedirect(__('latitude and longitude are mandatory', 'storkmdm'));
         return false;
      }

      if (!$input['latitude'] == 'na' && !$input['longitude'] == 'na') {
         $input['latitude']      = floatval($input['latitude']);
         $input['longitude']     = floatval($input['longitude']);
         $input['computers_id']  = intval($input['computers_id']);

         if ($input['latitude'] < -180 || $input['latitude'] > 180) {
            Session::addMessageAfterRedirect(__('latitude is invalid', 'storkmdm'));
            return false;
         }
         if ($input['longitude'] < -180 || $input['longitude'] > 180) {
            Session::addMessageAfterRedirect(__('longitude is invalid', 'storkmdm'));
            return false;
         }
      }

      $computer = new Computer();
      if (!$computer->getFromDB($input['computers_id'])) {
         Session::addMessageAfterRedirect(__('Device not found', 'storkmdm'));
         return false;
      }

      return $input;
   }

   public function prepareInputForUpdate($input) {
      if (!isset($input['latitude']) || !isset($input['longitude'])) {
         Session::addMessageAfterRedirect(__('latitude and longitude are mandatory', 'storkmdm'));
         return false;
      }

      if (isset($input['computers_id'])) {
         $input['computers_id'] = intval($input['computers_id']);
         $computer = new Computer();
         if (!$computer->getFromDB($input['computers_id'])) {
            Session::addMessageAfterRedirect(__('Device not found', 'storkmdm'));
            return false;
         }
      }

      if (!$input['latitude'] == 'na' && !$input['longitude'] == 'na') {
         if (isset($input['latitude'])) {
            $input['latitude'] = floatval($input['latitude']);
            if ($input['latitude'] < -180 || $input['latitude'] > 180) {
               Session::addMessageAfterRedirect(__('latitude is invalid', 'storkmdm'));
               return false;
            }
         }
         if (isset($input['longitude'])) {
            $input['longitude'] = floatval($input['longitude']);
            if ($input['longitude'] < -180 || $input['longitude'] > 180) {
               Session::addMessageAfterRedirect(__('longitude is invalid', 'storkmdm'));
               return false;
            }
         }
      }

      return $input;
   }

   /*
    * Add default join for search on Geolocation
    */
   public static function addDefaultJoin() {
      $geolocationTable = self::getTable();
      $computerTable = Computer::getTable();
      $join = "LEFT JOIN `$computerTable` AS `c` ON `$geolocationTable`.`computers_id`=`c`.`id` ";

      return $join;
   }

   /*
    * Add default where for search on Geolocation
    */
   public static function addDefaultWhere() {

      $where = '';

      // Entities
      if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         // Force complete SQL not summary when access to all entities
         $geolocationTable = self::getTable();
         $computerTable = 'c'; // See self::addDefaultJoin
         $where .= getEntitiesRestrictRequest('', "c", "entities_id", '', false, true);
      } else {

      }

      return $where;
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {

      global $CFG_GLPI;

      $tab = array();
      $tab['common']             = __s('Geolocation', "storkmdm");

      $i = 2;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'id';
      $tab[$i]['name']            = __('ID');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'number';

      $i++;
      $tab[$i]['table']           = Computer::getTable();
      $tab[$i]['field']           = 'id';
      $tab[$i]['name']            = __('Computer');
      $tab[$i]['datatype']        = 'dropdown';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'latitude';
      $tab[$i]['name']            = __('latitude', 'storkmdm');
      $tab[$i]['datatype']        = 'string';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'longitude';
      $tab[$i]['name']            = __('longitude', 'storkmdm');
      $tab[$i]['datatype']        = 'string';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'date';
      $tab[$i]['name']            = __('date');
      $tab[$i]['datatype']        = 'string';
      $tab[$i]['massiveaction']   = false;

      return $tab;
   }

   public static function hook_computer_purge(CommonDBTM $item) {
      $geolocation = new self();
      $geolocation->deleteByCriteria(array('computers_id' => $item->getID()));
   }
}
