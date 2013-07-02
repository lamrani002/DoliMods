#!/usr/bin/php
<?php
/**
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       scripts/user/sync_users_dolibarr2ldap.php
 *      \ingroup    ldap core
 *      \brief      Script de mise a jour des users dans LDAP depuis base Dolibarr
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

if (! isset($argv[1]) || ! $argv[1]) {
    print "Usage: $script_file now\n";
    exit;
}
$now=$argv[1];

// Recupere env dolibarr
$version='1.13';

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");

$error=0;


print "***** $script_file ($version) *****\n";

/*
if (! $conf->global->LDAP_SYNCHRO_ACTIVE)
{
	print $langs->trans("LDAPSynchronizationNotSetupInDolibarr");
	exit 1;
}
*/

$sql = "SELECT rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."user";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$ldap=new Ldap();
	$ldap->connect_bind();

	while ($i < $num)
	{
		$ldap->error="";

		$obj = $db->fetch_object($resql);

		$fuser = new User($db);
		$fuser->fetch($obj->rowid);

		print $langs->trans("UpdateUser")." rowid=".$fuser->id." ".$fuser->getFullName($langs);

		$oldobject=$fuser;

	    $oldinfo=$oldobject->_load_ldap_info();
	    $olddn=$oldobject->_load_ldap_dn($oldinfo);

	    $info=$fuser->_load_ldap_info();
		$dn=$fuser->_load_ldap_dn($info);

		$result=$ldap->add($dn,$info,$user);	// Wil fail if already exists
		$result=$ldap->update($dn,$info,$user,$olddn);
		if ($result > 0)
		{
			print " - ".$langs->trans("OK");
		}
		else
		{
			$error++;
			print " - ".$langs->trans("KO").' - '.$ldap->error;
		}
		print "\n";

		$i++;
	}

	$ldap->unbind();
	$ldap->close();
}
else
{
	dol_print_error($db);
}

return $error;
?>
