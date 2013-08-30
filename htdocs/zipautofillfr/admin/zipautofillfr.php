<?php
/* Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/zipautofill/admin/zipautofill.php
 *      \ingroup    zipautofill
 *      \brief      Page to setup module ZipAutoFill
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php');


if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("zipautofillfr@zipautofillfr");

$def = array();
$action=GETPOST("action");
$actionsave=GETPOST("save");



/*
 * Actions
 */

if (preg_match('/^set/',$action))
{
    // This is to force to add a new param after css urls to force new file loading
    // This set must be done before calling llxHeader().
    $_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');
}

if ($action == 'set')
{
	$name = GETPOST("name");
	$value = GETPOST("value");
	$res = dolibarr_set_const($db, $name, $value,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;
 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}

if ($action == 'setcolor')
{
	$res = dolibarr_set_const($db, 'THEME_ELDY_RGB', GETPOST('THEME_ELDY_RGB'),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db, 'THEME_ELDY_FONT_SIZE1', GETPOST('THEME_ELDY_FONT_SIZE1'),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db, 'THEME_ELDY_USE_HOVER', GETPOST('THEME_ELDY_USE_HOVER'),'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;
 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}



/**
 * View
 */

$formother=new FormOther($db);

llxHeader('','ZipAutoFill',$linktohelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ZipAutoFillSetup"),$linkback,'setup');
print '<br>';

print $langs->trans("ZipAutoFillDesc").'<br>';
print '<br>';


$head=array();
$h=0;

$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'tabsetup';
$h++;

$head[$h][0] = 'about.php';
$head[$h][1] = $langs->trans("About");
$head[$h][2] = 'tababout';
$h++;

dol_fiche_head($head,'tabsetup');


print '<br>';

print $langs->trans("ZipAutoFillNoSetup").'<br>';

print '<br>';

dol_fiche_end();


llxFooter();

if (is_object($db)) $db->close();
?>