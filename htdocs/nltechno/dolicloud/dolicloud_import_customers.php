<?php
/* Copyright (C) 2008-2013	Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/nltechno/dolicloud/dolicloud_import_customers.php
 *      \ingroup    nltechno
 *      \brief      Page list payment
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");

// Use on dev env only
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php")) $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php");

if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');
dol_include_once('/nltechno/class/dolicloudcustomer.class.php');

if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("nltechno@nltechno");

$def = array();
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$actionsave=GETPOST('save', 'alpha');
$file=GETPOST('file');
$line=GETPOST('line');

$modules = array();
$arraystatus=Dolicloudcustomer::$listOfStatus;
$upload_dir = $conf->nltechno->dir_temp.'/dolicloud';

/*
 * Actions
 */

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

// Send file
if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	$error=0;

	dol_mkdir($dir);

	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload=dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir."/".$_FILES['userfile']['name'], 1, 0, $_FILES['userfile']['error']);
		if (is_numeric($resupload) && $resupload > 0)
		{
			setEventMessage($langs->trans("FileTransferComplete"),'mesgs');
			$showmessage=1;
		}
		else
		{
			$langs->load("errors");
			if ($resupload < 0)	// Unknown error
			{
				setEventMessage($langs->trans("ErrorFileNotUploaded"),'mesgs');
			}
			else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
			{
				setEventMessage($langs->trans("ErrorFileIsInfectedWithAVirus"),'mesgs');
			}
			else	// Known error
			{
				setEventMessage($langs->trans($resupload),'errors');
			}
		}
	}

	if ($error)
	{
		setEventMessage($langs->trans("ErrorFileNotUploaded"),'errors');
	}
}

// Delete file
if ($action == 'remove_file')
{
	$file = $conf->nltechno->dir_temp . "/" . $file;	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

	$ret=dol_delete_file($file);
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
	header('Location: '.$_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'import' || $action == 'create')
{
	$importresult='';

	$handle=fopen($conf->nltechno->dir_temp.'/'.$file, 'r');
	if ($handle)
	{
		$importresult.='Import file '.$conf->nltechno->dir_temp.'/'.$file.'<br>';

		$i=0; $j=0;
		$dolicloudcustomer=new Dolicloudcustomer($db);
		while(($data = fgetcsv($handle, 1000, ",")) !== FALSE)
		{
			$i++;
			$organization=$data[0];
			$email=$data[1];
			$date_acquired=dol_stringtotime($data[2]);
			$plan=$data[3];
			$total_invoiced=$data[4];
			$total_payed=$data[5];
			$status=$data[6];
			if ($organization == 'Organization') continue;	// Discard first line
			if (empty($total_invoiced)) continue;

			$j++;
			$importresult.=str_pad($j,4,'0',STR_PAD_LEFT).' - Line '.str_pad($i,4,'0',STR_PAD_LEFT).' - ';

			$result=$dolicloudcustomer->fetch('','',$organization);
			if ($result <= 0)
			{
				$importresult.='Organization "'.$organization.'" not found. ';
				//$importresult.='<a href="'.$_SERVER["PHP_SELF"].'?action=create&line='.$i.'&file='.urlencode($file).'">Click to create</a>.<br>';
				$importresult.='<a target="_blank" href="'.dol_buildpath('/nltechno/dolicloud/dolicloud_card.php',1).'?';
				$importresult.='action=create&plan='.urlencode($plan).'&organization='.urlencode($organization).'&email='.urlencode($email);
				$importresult.='&date_registrationmonth='.dol_print_date($date_acquired,'%m');
				$importresult.='&date_registrationday='.dol_print_date($date_acquired,'%d');
				$importresult.='&date_registrationyear='.dol_print_date($date_acquired,'%Y');
				$importresult.='">Click to create</a>.<br>';
			}
			else
			{
				$importresult.='Organization "'.$organization.'" found.';

				$partner=(preg_match('/2Byte/i',$plan)?'2Byte':'');		// TODO Not complete

				//var_dump($dolicloudcustomer->plan.' '.$plan.' '.$dolicloudcustomer->partner.' '.$partner.' '.$dolicloudcustomer->date_registration.' '.$date_acquired);
				$change=false;
				if ($dolicloudcustomer->plan!=$plan) $change=true;
				if ($dolicloudcustomer->partner!=$partner) $change=true;
				if ($dolicloudcustomer->date_registration!=$date_acquired) $change=true;
				if ($dolicloudcustomer->status!=$status) $change=true;
				if (! in_array($status,$arraystatus))
				{
					$importresult.=' Status is not recognized.';
				}
				else if ($change)
				{
					$dolicloudcustomer->plan=$plan;
					$dolicloudcustomer->partner=$partner;
					$dolicloudcustomer->date_registration=$date_acquired;
					$dolicloudcustomer->status=$status;

					$result=$dolicloudcustomer->update($user,1);
					$importresult.=' We update record.<br>';
				}
				else
				{
					$importresult.=' No need to update. Current status is '.$status.'<br>';
				}
			}

			$importresult.="\n";
		}
		fclose($handle);
	}
	else dol_print_error('','Failed to open file '.$conf->nltechno->dir_temp.'/'.$file);
}




/*
 * View
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('','DoliCloud',$linktohelp);

print_fiche_titre($langs->trans("List payments"))."\n";
print '<br>';

$formfile->form_attach_new_file($_SERVER['PHP_SELF'], $langs->trans("ImportFileCustomers"), 0, 0, 1, 50, '', '', false);

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

$morehtml=' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'?module=nltechno_temp&action=import&file=__FILENAMEURLENCODED__">'.$langs->trans("Import").'</a>';
print $formfile->showdocuments('nltechno_temp', 'dolicloud', $conf->nltechno->dir_temp.'/dolicloud', $_SERVER["PHP_SELF"], 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $morehtml);

if ($importresult)
{
	print '<br>'.$langs->trans("Result").':<br>'."\n";
	print $importresult;
}

// Footer
llxFooter();
// Close database handler
$db->close();
?>