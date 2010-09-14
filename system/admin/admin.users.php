<?php
/**
 * Administration panel - Users
 *
 * @package Cotonti
 * @version 0.7.0
 * @author Neocrome, Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2008-2010
 * @license BSD
 */

(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('users', 'a');
cot_block($usr['isadmin']);

cot_require_api('auth');
cot_require_api('uploads');

$t = new XTemplate(cot_skinfile('admin.users'));



$adminpath[] = array(cot_url('admin', 'm=users'), $L['Users']);

$g = cot_import('g', 'G', 'INT');

$lincif_extfld = cot_auth('admin', 'a', 'A');

/* === Hook === */
foreach (cot_getextplugins('admin.users.first') as $pl)
{
	include $pl;
}
/* ===== */

if($n == 'add')
{
	$ntitle = cot_import('ntitle', 'P', 'TXT');
	$ndesc = cot_import('ndesc', 'P', 'TXT');
	$nicon = cot_import('nicon', 'P', 'TXT');
	$nalias = cot_import('nalias', 'P', 'TXT');
	$nlevel = cot_import('nlevel', 'P', 'LVL');
	$nmaxsingle = min(cot_import('nmaxsingle', 'P', 'INT'), cot_get_uploadmax());
	$nmaxtotal = cot_import('nmaxtotal', 'P', 'INT');
	$ncopyrightsfrom = cot_import('ncopyrightsfrom', 'P', 'INT');
	$ndisabled = cot_import('ndisabled', 'P', 'BOL');
	$nhidden = cot_import('nhidden', 'P', 'BOL');
	$nmtmode = cot_import('nmtmode', 'P', 'BOL');

	$sql = (!empty($ntitle)) ? cot_db_query("INSERT INTO $db_groups (grp_alias, grp_level, grp_disabled, grp_hidden,  grp_maintenance, grp_title, grp_desc, grp_icon, grp_pfs_maxfile, grp_pfs_maxtotal, grp_ownerid) VALUES ('".cot_db_prep($nalias)."', ".(int)$nlevel.", ".(int)$ndisabled.", ".(int)$nhidden.",  ".(int)$nmtmode.", '".cot_db_prep($ntitle)."', '".cot_db_prep($ndesc)."', '".cot_db_prep($nicon)."', ".(int)$nmaxsingle.", ".(int)$nmaxtotal.", ".(int)$usr['id'].")") : '';
	$grp_id = cot_db_insertid();

	/* === Hook === */
	foreach (cot_getextplugins('admin.users.add') as $pl)
	{
		include $pl;
	}
	/* ===== */

	cot_auth_add_group($grp_id, $ncopyrightsfrom);

	$cot_cache->db->remove('cot_groups', 'system');

	cot_message('Added');
}
elseif($n == 'edit')
{
	if($a == 'update')
	{
		$rtitle = cot_import('rtitle', 'P', 'TXT');
		$rdesc = cot_import('rdesc', 'P', 'TXT');
		$ricon = cot_import('ricon', 'P', 'TXT');
		$ralias = cot_import('ralias', 'P', 'TXT');
		$rlevel = cot_import('rlevel', 'P', 'LVL');
		$rmaxfile = min(cot_import('rmaxfile', 'P', 'INT'), cot_get_uploadmax());
		$rmaxtotal = cot_import('rmaxtotal', 'P', 'INT');
		$rdisabled = ($g < 6) ? 0 : cot_import('rdisabled', 'P', 'BOL');
		$rhidden = ($g == 4) ? 0 : cot_import('rhidden', 'P', 'BOL');
		$rmtmode = cot_import('rmtmode', 'P', 'BOL');

		/* === Hook === */
		foreach (cot_getextplugins('admin.users.update') as $pl)
		{
			include $pl;
		}
		/* ===== */

		$rtitle = cot_db_prep($rtitle);
	   	$rdesc = cot_db_prep($rdesc);
	   	$ricon = cot_db_prep($ricon);
	   	$ralias = cot_db_prep($ralias);

		$sql = (!empty($rtitle)) ? cot_db_query("UPDATE $db_groups SET grp_title='$rtitle', grp_desc='$rdesc', grp_icon='$ricon', grp_alias='$ralias', grp_level='$rlevel', grp_pfs_maxfile='$rmaxfile', grp_pfs_maxtotal='$rmaxtotal', grp_disabled='$rdisabled', grp_hidden='$rhidden', grp_maintenance='$rmtmode' WHERE grp_id='$g'") : '';

		$cot_cache->db->remove('cot_groups', 'system');

		cot_message('Updated');
	}
	elseif($a == 'delete' && $g > 5)
	{
		$sql = cot_db_query("DELETE FROM $db_groups WHERE grp_id='$g'");
		cot_auth_remove_group($g);
		$sql = cot_db_query("DELETE FROM $db_groups_users WHERE gru_groupid='$g'");

		/* === Hook === */
		foreach (cot_getextplugins('admin.users.delete') as $pl)
		{
			include $pl;
		}
		/* ===== */
		cot_auth_clear('all');
		$cot_cache->db->remove('cot_groups', 'system');

		cot_message('Deleted');
	}
	else
	{
       	$showdefault = false;

	    $sql = cot_db_query("SELECT * FROM $db_groups WHERE grp_id='$g'");
		cot_die(cot_db_numrows($sql) == 0);
		$row = cot_db_fetcharray($sql);

		$sql1 = cot_db_query("SELECT COUNT(*) FROM $db_groups_users WHERE gru_groupid='$g'");
		$row['grp_memberscount'] = cot_db_result($sql1, 0, "COUNT(*)");

		$row['grp_title'] = htmlspecialchars($row['grp_title']);

		$adminpath[] = array (cot_url('admin', 'm=users&n=edit&g='.$g), $row['grp_title']);

		$t->assign(array(
			'ADMIN_USERS_EDITFORM_URL' => cot_url('admin', 'm=users&n=edit&a=update&g='.$g),
			'ADMIN_USERS_EDITFORM_GRP_TITLE' => cot_inputbox('text', 'rtitle', $row['grp_title'], 'size="40" maxlength="64"'),
			'ADMIN_USERS_EDITFORM_GRP_DESC' => cot_inputbox('text', 'rdesc', htmlspecialchars($row['grp_desc']), 'size="40" maxlength="64"'),
			'ADMIN_USERS_EDITFORM_GRP_ICON' => cot_inputbox('text', 'ricon', htmlspecialchars($row['grp_icon']), 'size="40" maxlength="128"'),
			'ADMIN_USERS_EDITFORM_GRP_ALIAS' => cot_inputbox('text', 'ralias', htmlspecialchars($row['grp_alias']), 'size="40" maxlength="24"'),
			'ADMIN_USERS_EDITFORM_GRP_PFS_MAXFILE' => cot_inputbox('text', 'rmaxfile', htmlspecialchars($row['grp_pfs_maxfile']), 'size="16" maxlength="16"'),
			'ADMIN_USERS_EDITFORM_GRP_PFS_MAXTOTAL' => cot_inputbox('text', 'rmaxtotal', htmlspecialchars($row['grp_pfs_maxtotal']), 'size="16" maxlength="16"'),
			'ADMIN_USERS_EDITFORM_GRP_DISABLED' => ($g <= 5) ? $L['No'] : cot_radiobox($row['grp_disabled'], 'rdisabled', array(1, 0), array($L['Yes'], $L['No'])),
			'ADMIN_USERS_EDITFORM_GRP_HIDDEN' => ($g == 4) ? $L['No'] : cot_radiobox($row['grp_hidden'], 'rhidden', array(1, 0), array($L['Yes'], $L['No'])),
			'ADMIN_USERS_EDITFORM_GRP_MAINTENANCE' => cot_radiobox($row['grp_maintenance'], 'rmtmode', array(1, 0), array($L['Yes'], $L['No'])),
			'ADMIN_USERS_EDITFORM_GRP_RLEVEL' => cot_selectbox($row['grp_level'], 'rlevel', range(0, 99), range(0, 99), false),
			'ADMIN_USERS_EDITFORM_GRP_MEMBERSCOUNT' => $row['grp_memberscount'],
			'ADMIN_USERS_EDITFORM_GRP_MEMBERSCOUNT_URL' => cot_url('users', 'g='.$g),
			'ADMIN_USERS_EDITFORM_RIGHT_URL' => cot_url('admin', 'm=rights&g='.$g),
			'ADMIN_USERS_EDITFORM_DEL_URL' => cot_url('admin', 'm=users&n=edit&a=delete&g='.$g.'&'.cot_xg()),
		));
		/* === Hook === */
		foreach (cot_getextplugins('admin.users.edit.tags') as $pl)
		{
			include $pl;
		}
		/* ===== */
		$t->parse('MAIN.ADMIN_USERS_EDIT');
	}
}

if(!isset($showdefault) OR $showdefault == true)
{
	$sql = cot_db_query("SELECT DISTINCT(gru_groupid), COUNT(*) FROM $db_groups_users WHERE 1 GROUP BY gru_groupid");
	while($row = cot_db_fetcharray($sql))
	{
		$members[$row['gru_groupid']] = $row['COUNT(*)'];
	}

	$sql = cot_db_query("SELECT grp_id, grp_title, grp_disabled, grp_hidden FROM $db_groups WHERE 1 order by grp_level DESC, grp_id DESC");

	if(cot_db_numrows($sql) > 0)
	{
		while($row = cot_db_fetcharray($sql))
		{
			$row['grp_hidden'] = ($row['grp_hidden']) ? '1' : '0';
			$members[$row['grp_id']] = (empty($members[$row['grp_id']])) ? '0' : $members[$row['grp_id']];
			$t->assign(array(
				'ADMIN_USERS_ROW_GRP_TITLE_URL' => cot_url('admin', 'm=users&n=edit&g='.$row['grp_id']),
				'ADMIN_USERS_ROW_GRP_TITLE' => htmlspecialchars($row['grp_title']),
				'ADMIN_USERS_ROW_GRP_ID' => $row['grp_id'],
				'ADMIN_USERS_ROW_GRP_COUNT_MEMBERS' => $members[$row['grp_id']],
				'ADMIN_USERS_ROW_GRP_DISABLED' => $cot_yesno[!$row['grp_disabled']],
				'ADMIN_USERS_ROW_GRP_HIDDEN' => $cot_yesno[$row['grp_hidden']],
				'ADMIN_USERS_ROW_GRP_RIGHTS_URL' => cot_url('admin', 'm=rights&g='.$row['grp_id']),
				'ADMIN_USERS_ROW_GRP_JUMPTO_URL' => cot_url('users', 'g='.$row['grp_id'])
			));
			$t->parse('MAIN.ADMIN_USERS_DEFAULT.USERS_ROW');
		}
	}

	$t->assign(array(
		'ADMIN_USERS_FORM_URL' => cot_url('admin', 'm=users&n=add'),
		'ADMIN_USERS_NGRP_TITLE' => cot_inputbox('text', 'ntitle', '', 'size="40" maxlength="64"'),
		'ADMIN_USERS_NGRP_DESC' => cot_inputbox('text', 'ndesc', '', 'size="40" maxlength="64"'),
		'ADMIN_USERS_NGRP_ICON' => cot_inputbox('text', 'nicon', '', 'size="40" maxlength="128"'),
		'ADMIN_USERS_NGRP_ALIAS' => cot_inputbox('text', 'nalias', '', 'size="40" maxlength="24"'),
		'ADMIN_USERS_NGRP_PFS_MAXFILE' => cot_inputbox('text', 'nmaxfile', '', 'size="16" maxlength="16"'),
		'ADMIN_USERS_NGRP_PFS_MAXTOTAL' => cot_inputbox('text', 'nmaxtotal', '', 'size="16" maxlength="16"'),
		'ADMIN_USERS_NGRP_DISABLED' => cot_radiobox(0, 'ndisabled', array(1, 0), array($L['Yes'], $L['No'])),
		'ADMIN_USERS_NGRP_HIDDEN' => cot_radiobox(0, 'nhidden', array(1, 0), array($L['Yes'], $L['No'])),
		'ADMIN_USERS_NGRP_MAINTENANCE' => cot_radiobox(0, 'nmtmode', array(1, 0), array($L['Yes'], $L['No'])),
		'ADMIN_USERS_NGRP_RLEVEL' => cot_selectbox(50, 'nlevel', range(0, 99), range(0, 99), false),
		'ADMIN_USERS_FORM_SELECTBOX_GROUPS' => cot_selectbox_groups(4, 'ncopyrightsfrom', array('5'))
	));
	$t->parse('MAIN.ADMIN_USERS_DEFAULT');
}

$t->assign(array(
	'ADMIN_USERS_URL' => cot_url('admin', 'm=config&n=edit&o=core&p=users'),
	'ADMIN_USERS_EXTRAFIELDS_URL' => cot_url('admin', 'm=extrafields&n=users')
));


cot_display_messages($t);

/* === Hook  === */
foreach (cot_getextplugins('admin.users.tags') as $pl)
{
	include $pl;
}
/* ===== */

$t->parse('MAIN');
if (COT_AJAX)
{
	$t->out('MAIN');
}
else
{
	$adminmain = $t->text('MAIN');
}

?>