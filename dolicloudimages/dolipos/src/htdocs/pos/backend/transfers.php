<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011-2012 Juanjo Menent    	<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *		\file       htdocs/pos/backend/transfers.php
 *		\ingroup    pos
 *		\brief      Page for input acoount transfers
 */

//require("./pre.inc.php");
$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

//require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/bank.lib.php");

$langs->load("banks");

if (! $user->rights->pos->transfer)
  accessforbidden();

$action=GETPOST('action','alpha');

/*
 * Action ajout d'un transfers
 */
if ($action == 'add')
{
	$langs->load("errors");

	$mesg='';
	$dateo = dol_mktime(12,0,0,GETPOST('remonth','int'),GETPOST('reday','int'),GETPOST('reyear','int'));
	$label = GETPOST('label','alpha');
	$amount= GETPOST('amount','int');

	if (! $label)
	{
		$error=1;
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("Description"))."</div>";
	}
	if (! $amount)
	{
		$error=1;
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"))."</div>";
	}
	if (! GETPOST('account_from','int'))
	{
		$error=1;
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("TransferFrom"))."</div>";
	}
	if (! GETPOST('account_to','int'))
	{
		$error=1;
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("TransferTo"))."</div>";
	}
	if (! $error)
	{
		require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');

		$accountfrom=new Account($db);
		$accountfrom->fetch($_POST["account_from"]);

		$accountto=new Account($db);
		$accountto->fetch($_POST["account_to"]);

		if ($accountto->id != $accountfrom->id)
		{
			$db->begin();

			$error=0;
			$bank_line_id_from=0;
			$bank_line_id_to=0;
			$result=0;

			// By default, electronic transfert from bank to bank
			$typefrom='PRE';
			$typeto='VIR';
			if ($accountto->courant == 2 || $accountfrom->courant == 2)
			{
				// This is transfert of change
				$typefrom='LIQ';
				$typeto='LIQ';
			}

			if (! $error) $bank_line_id_from = $accountfrom->addline($dateo, $typefrom, $label, -1*price2num($amount), '', '', $user);
			if (! ($bank_line_id_from > 0)) $error++;
			if (! $error) $bank_line_id_to = $accountto->addline($dateo, $typeto, $label, price2num($amount), '', '', $user);
			if (! ($bank_line_id_to > 0)) $error++;

		    if (! $error) $result=$accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
			if (! ($result > 0)) $error++;
		    if (! $error) $result=$accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
			if (! ($result > 0)) $error++;

			if (! $error)
			{
				$mesg.="<div class=\"ok\">";
				$mesg.=$langs->trans("TransferFromToDone","<a href=\"account.php?account=".$accountfrom->id."\">".$accountfrom->label."</a>","<a href=\"account.php?account=".$accountto->id."\">".$accountto->label."</a>",$amount,$langs->transnoentities("Currency".$conf->monnaie));
				$mesg.="</div>";
				$db->commit();
			}
			else
			{
				$mesg.="<div class=\"error\">".$accountfrom->error.' '.$accountto->error."</div>";
				$db->rollback();
			}
		}
		else
		{
			$mesg.="<div class=\"error\">".$langs->trans("ErrorFromToAccountsMustDiffers")."</div>";
		}
	}
}



/*
 * Affichage
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader('','',$helpurl);
if($conf->global->POS_HELP){
	dol_include_once('/pos/backend/class/utils.class.php');
}

$html=new Form($db);


print_fiche_titre($langs->trans("BankTransfer"));

dol_htmloutput_mesg($mesg);

print $langs->trans("TransferDesc");
print "<br><br>";

print "<form name='add' method=\"post\" action=\"transfers.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<input type="hidden" name="action" value="add">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("TransferFrom").'</td><td>'.$langs->trans("TransferTo").'</td><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Description").'</td><td>'.$langs->trans("Amount").'</td>';
print '</tr>';

$var=false;
print '<tr '.$bc[$var].'><td>';
print $html->select_comptes('','account_from',0,'',1);
print "</td>";

print "<td>\n";
print $html->select_comptes('','account_to',0,'',1);
print "</td>\n";

print "<td>";
$html->select_date($dateo,'','','','','add');
print "</td>\n";
print '<td><input name="label" class="flat" type="text" size="40" value=""></td>';
print '<td><input name="amount" class="flat" type="text" size="8" value=""></td>';

print "</table>";

print '<br><center><input type="submit" class="button" value="'.$langs->trans("Add").'"></center>';

print "</form>";

llxFooter();

$db->close();
?>