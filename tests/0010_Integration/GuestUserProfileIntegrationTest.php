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

class GuestUserProfileIntegrationTest extends RegisteredUserTestCase
{

   public function testInitGetGuestProfileId() {
      $config = Config::getConfigurationValues('storkmdm', ['guest_profiles_id']);
      $this->assertArrayHasKey('guest_profiles_id', $config);
      return $config['guest_profiles_id'];
   }

   /**
    * @depends testInitGetGuestProfileId
    * @return array Rights
    */
   public function testGetRights($profileId) {
      $config = Config::getConfigurationValues('storkmdm', ['guest_profiles_id']);
      $profileId = $config['guest_profiles_id'];
      $rights = ProfileRight::getProfileRights(
            $profileId,
            array(
                  PluginStorkmdmAgent::$rightname,
                  PluginStorkmdmFleet::$rightname,
                  PluginStorkmdmPackage::$rightname,
                  PluginStorkmdmFile::$rightname,
                  PluginStorkmdmGeolocation::$rightname,
                  PluginStorkmdmWellknownpath::$rightname,
                  PluginStorkmdmPolicy::$rightname,
                  PluginStorkmdmPolicyCategory::$rightname,
                  PluginStorkmdmProfile::$rightname,
            )
      );
      $this->assertGreaterThan(0, count($rights));
      return $rights;
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testAgentRight($rights) {
      $this->assertEquals(READ | CREATE, $rights[PluginStorkmdmAgent::$rightname]);
   }

   public function testSessionHasGuestProfileId() {
      $this->assertTrue(isset($_SESSION['plugin_storkmdm_guest_profiles_id']));
   }

   /**
    * @@depends testInitGetGuestProfileId
    * @depends testSessionHasGuestProfileId
    */
   public function testSessionGuestProfileIdValue($profileId) {
      $this->assertEquals($profileId, $_SESSION['plugin_storkmdm_guest_profiles_id']);
   }
}
