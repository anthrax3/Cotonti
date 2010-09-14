<?php

/* ====================
Seditio - Website engine
Copyright Neocrome
http://www.neocrome.net
==================== */

/**
 * @package forums
 * @version 0.0.3
 * @copyright Copyright (c) 2008-2009 Cotonti Team
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

$id = cot_import('id','G','INT');
$s = cot_import('s','G','INT');
$q = cot_import('q','G','INT');
$p = cot_import('p','G','INT');
$d = cot_import('d','G','INT');
$o = cot_import('o','G','ALP');
$w = cot_import('w','G','ALP',4);
$quote = cot_import('quote','G','INT');

/* === Hook === */
foreach (cot_getextplugins('forums.editpost.first') as $pl)
{
	include $pl;
}
/* ===== */

cot_blockguests();
cot_check_xg();

$sql = cot_db_query("SELECT * FROM $db_forum_posts WHERE fp_id='$p' and fp_topicid='$q' and fp_sectionid='$s' LIMIT 1");

if ($row = cot_db_fetcharray($sql))
{
	$fp_text = $row['fp_text'];
	$fp_posterid = $row['fp_posterid'];
	$fp_postername = $row['fp_postername'];
	$fp_sectionid = $row['fp_sectionid'];
	$fp_topicid = $row['fp_topicid'];
	$fp_updated = $row['fp_updated'];
	$fp_updater = $row['fp_updater'];

	list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('forums', $s);
	
	/* === Hook === */
	foreach (cot_getextplugins('forums.editpost.rights') as $pl)
	{
		include $pl;
	}
	/* ===== */

	if (!$usr['isadmin'] && $fp_posterid!=$usr['id'])
	{
		cot_log("Attempt to edit a post without rights", 'sec');
		cot_die();
	}
	cot_block($usr['auth_read']);
}
else
{ cot_die(); }

$sql = cot_db_query("SELECT fs_state, fs_title, fs_category, fs_allowbbcodes, fs_allowsmilies, fs_masterid, fs_mastername FROM $db_forum_sections WHERE fs_id='$s' LIMIT 1");

if ($row = cot_db_fetcharray($sql))
{
	if ($row['fs_state'])
	{
		cot_redirect(cot_url('message', "msg=602", '', true));
	}

	$fs_title = $row['fs_title'];
	$fs_category = $row['fs_category'];
	$fs_allowbbcodes = $row['fs_allowbbcodes'];
	$fs_allowsmilies = $row['fs_allowsmilies'];
	$fs_masterid = $row['fs_masterid'];
	$fs_mastername = $row['fs_mastername'];
}
else
{ cot_die(); }

$sql = cot_db_query("SELECT ft_state, ft_mode, ft_title, ft_desc FROM $db_forum_topics WHERE ft_id='$q' LIMIT 1");

if ($row = cot_db_fetcharray($sql))
{
	if ($row['ft_state'] && !$usr['isadmin'])
	{
		cot_redirect(cot_url('message', "msg=603", '', true));
	}
	$ft_title = $row['ft_title'];
	$ft_desc = $row['ft_desc'];
	$ft_fulltitle = ($row['ft_mode']==1) ? "# ".$ft_title : $ft_title;
	$sys['sublocation'] = 'q'.$q;
}
else
{ cot_die(); }

if ($a=='update')
{
	/* === Hook === */
	foreach (cot_getextplugins('forums.editpost.update.first') as $pl)
	{
		include $pl;
	}
	/* ===== */

	$rtext = cot_import('rtext','P','HTM');
	$rtopictitle = cot_import('rtopictitle','P','TXT', 255);
	$rtopicdesc = cot_import('rtopicdesc','P','TXT', 255);
	$rupdater = ($fp_posterid == $usr['id'] && ($sys['now_offset'] < $fp_updated + 300) && empty($fp_updater) ) ? '' : $usr['name'];

	if(!empty($rtext))
	{
		if($cfg['parser_cache'])
		{
			$rhtml = cot_db_prep(cot_parse(htmlspecialchars($rtext), $cfg['parsebbcodeforums'] && $fs_allowbbcodes, $cfg['parsesmiliesforums'] && $fs_allowsmilies, 1));
		}
		else
		{
			$rhtml = '';
		}
		$rtext = cot_db_prep($rtext);
		$sql = cot_db_query("UPDATE $db_forum_posts SET fp_text='$rtext', fp_html = '$rhtml', fp_updated='".$sys['now_offset']."', fp_updater='".cot_db_prep($rupdater)."' WHERE fp_id='$p'");
	}

	$is_first_post = false;
	if (!empty($rtopictitle))
	{
		$sql = cot_db_query("SELECT fp_id FROM $db_forum_posts WHERE fp_topicid='$q' ORDER BY fp_id ASC LIMIT 1");
		if ($row = cot_db_fetcharray($sql))
		{
			$fp_idp = $row['fp_id'];
			if ($fp_idp==$p)
			{
				if (mb_substr($rtopictitle, 0 ,1)=="#")
				{ $rtopictitle = str_replace('#', '', $rtopictitle); }
				$sql = cot_db_query("UPDATE $db_forum_topics SET ft_title='".cot_db_prep($rtopictitle)."', ft_desc='".cot_db_prep($rtopicdesc)."' WHERE ft_id='$q'");
				$is_first_post = true;
			}
		}
	}

	if (!empty($rtopictitle) && !empty($rtext))
	{
		$rtopicpreview = mb_substr(htmlspecialchars($rtext), 0, 128);
		$sql = cot_db_query("UPDATE $db_forum_topics SET ft_preview='".cot_db_prep($rtopicpreview)."' WHERE ft_id='$q'");
	}

	/* === Hook === */
	foreach (cot_getextplugins('forums.editpost.update.done') as $pl)
	{
		include $pl;
	}
	/* ===== */

	cot_forum_sectionsetlast($fp_sectionid);

	if ($cot_cache)
	{
		if ($cfg['cache_forums'])
		{
			$cot_cache->page->clear('forums');
		}
		if ($cfg['cache_index'])
		{
			$cot_cache->page->clear('index');
		}
	}

	cot_redirect(cot_url('forums', "m=posts&p=".$p, '#'.$p, true));
}

$sql = cot_db_query("SELECT fp_id FROM $db_forum_posts WHERE fp_topicid='$q' ORDER BY fp_id ASC LIMIT 1");

$is_first_post = false;

cot_require_api('forms');

if ($row = cot_db_fetcharray($sql))
{
	$fp_idp = $row['fp_id'];
	if ($fp_idp==$p)
	{
	 	$edittopictitle = cot_inputbox('text', 'rtopictitle', htmlspecialchars($ft_title), array('size' => 56, 'maxlength' => 255));
	 	$topicdescription = cot_inputbox('text', 'rtopicdesc', htmlspecialchars($ft_desc), array('size' => 56, 'maxlength' => 255));
	 	$is_first_post = true;
	}
}

// FIXME PFS dependency
//$pfs = cot_build_pfs($usr['id'], 'editpost', 'rtext', $L['Mypfs']);
//$pfs .= (cot_auth('pfs', 'a', 'A')) ? " &nbsp; ".cot_build_pfs(0, "editpost", "rtext", $L['SFS']) : '';
$morejavascript .= cot_build_addtxt('editpost', 'rtext');

$master = ($fs_masterid>0) ? array($fs_masterid, $fs_mastername) : false;

$toptitle = cot_build_forums($s, $fs_title, $fs_category, true, $master)." ".$cfg['separator']." ".cot_rc_link(cot_url('forums', "m=posts&p=".$p, "#".$p), htmlspecialchars($ft_fulltitle));
$toptitle .= $cfg['separator']." ".cot_rc_link(cot_url('forums', "m=editpost&s=$s&q=".$q."&p=".$p."&".cot_xg()), $L['Edit']);
$toptitle .= ($usr['isadmin']) ? " *" : '';

$sys['sublocation'] = $fs_title;
$title_params = array(
	'FORUM' => $L['Forums'],
	'SECTION' => $fs_title,
	'EDIT' => $L['Edit']
);
$out['subtitle'] = cot_title('title_forum_editpost', $title_params);
$out['head'] .= $R['code_noindex'];

cot_online_update();

/* === Hook === */
foreach (cot_getextplugins('forums.editpost.main') as $pl)
{
	include $pl;
}
/* ===== */

require_once $cfg['system_dir'] . '/header.php';

$mskin = cot_skinfile(array('forums', 'editpost', $fs_category, $fp_sectionid));
$t = new XTemplate($mskin);

cot_display_messages($t);

if ($is_first_post)
{
$t->assign(array(
	"FORUMS_EDITPOST_TOPICTITTLE" => $edittopictitle,
	"FORUMS_EDITPOST_TOPICDESCRIPTION" => $topicdescription,
));
	$t->parse("MAIN.FORUMS_EDITPOST_FIRSTPOST");
}


$t->assign(array(
	"FORUMS_EDITPOST_PAGETITLE" => $toptitle,
	"FORUMS_EDITPOST_SUBTITLE" => "#".$fp_posterid." ".$fp_postername." - ".date($cfg['dateformat'], $fp_updated + $usr['timezone'] * 3600),
	"FORUMS_EDITPOST_SEND" => cot_url('forums', "m=editpost&a=update&s=".$s."&q=".$q."&p=".$p."&".cot_xg()),
	"FORUMS_EDITPOST_TEXT" => cot_textarea('rtext', htmlspecialchars($fp_text), 20, 56, '', 'input_textarea_editor'),
	"FORUMS_EDITPOST_MYPFS" => $pfs
));

/* === Hook === */
foreach (cot_getextplugins('forums.editpost.tags') as $pl)
{
	include $pl;
}
/* ===== */

$t->parse("MAIN");
$t->out("MAIN");

require_once $cfg['system_dir'] . '/footer.php';

?>
