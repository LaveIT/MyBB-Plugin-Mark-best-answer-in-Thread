<?php
/*
Plugin "Mark best answers" 13.03.2019
2019 (c) itsmeJAY
Plugin by itsmeJAY - if you have questions or found bugs, please write me!
Version tested: 1.8.20 by itsmeJAY
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("postbit", "bestanswer_ba_button");

function bestanswer_info() {
    // Sprachdatei laden
    global $lang;
    $lang->load("babestanswer");

	return array(
		"name"			=> "$lang->ba_title",
		"description"	=> "$lang->ba_desc",
		"website"		=> "https://www.mybb.de/forum/user-10220.html",
		"author"		=> "itsmeJAY from MyBB.de",
		"authorsite"	=> "https://www.mybb.de/forum/user-10220.html",
		"version"		=> "1.0.0",
	);
}

function bestanswer_install() {
    global $db;
    if(!$db->field_exists('tsbestansweryn', "threads")) {
		$db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `tsbestansweryn` INT( 1 ) NOT NULL DEFAULT '0';");
        $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `tsbestanswerpid` INT( 1 ) NOT NULL DEFAULT '0';");
        $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `tsbestansweruid` INT( 1 ) NOT NULL DEFAULT '0';");
    }
}

function bestanswer_uninstall() {
    global $db;
	$db->query("ALTER TABLE `".TABLE_PREFIX."threads` DROP `tsbestansweryn`;");
	$db->query("ALTER TABLE `".TABLE_PREFIX."threads` DROP `tsbestanswerpid`;");
	$db->query("ALTER TABLE `".TABLE_PREFIX."threads` DROP `tsbestansweruid`;");
    $db->delete_query('settings', "name IN ('ba_delete_group', 'ba_thread_author', 'ba_button_goto', 'ba_button_mark', 'ba_button_delete', 'ba_button_thisis')");
    $db->delete_query('settinggroups', "name = 'ba_bestanswer'");
    
    // Rebuild Settings! :-)
    rebuild_settings();
}

function bestanswer_activate() {
    global $db, $mybb, $lang;

    // Sprachdatei laden
    $lang->load("babestanswer");

    require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'button_rep\']}') . "#i",'{$post[\'button_rep\']}{$post[\'button_ba\']}{$post[\'button_del_ba\']}');
	find_replace_templatesets("postbit", "#" . preg_quote('post_content') . "#i",'{$post[\'ts_class\']}');
	find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'user_details\']}') . "#i",'{$post[\'user_details\']}<br/>{$post[\'countsba\']}');


    $setting_group = array(
        'name' => 'ba_bestanswer',
        'title' => "$lang->ba_title",
        'description' => "$lang->ba_desc",
        'disporder' => 5,
        'isdefault' => 0
    );
    
    $gid = $db->insert_query("settinggroups", $setting_group);
    
    // Einstellungen
  
    $setting_array = array(
      // Welche Usergruppe darf als erledigt markieren?
      'ba_thread_author' => array(
          'title' => "$lang->ba_group_select_title",
          'description' => "$lang->ba_group_select_desc",
          'optionscode' => 'yesno',
          'value' => 1, // Default
          'disporder' => 1
      ),
      'ba_delete_group' => array(
        'title' => "$lang->ba_deletegroup_title",
        'description' => "$lang->ba_deletegroup_desc",
        'optionscode' => 'groupselect',
        'value' => 4, // Default
        'disporder' => 2
    ),
    'ba_button_mark' => array(
        'title' => "$lang->ba_buttonmark_title",
        'description' => "$lang->ba_buttonmark_desc",
        'optionscode' => 'text',
        'value' => "Mark as best answer", // Default
        'disporder' => 3
    ),
    'ba_button_goto' => array(
        'title' => "$lang->ba_buttongoto_title",
        'description' => "$lang->ba_buttongoto_desc",
        'optionscode' => 'text',
        'value' => "Go to best answer", // Default
        'disporder' => 4
    ),
    'ba_button_delete' => array(
        'title' => "$lang->ba_deletebutton_title",
        'description' => "$lang->ba_deletebutton_desc",
        'optionscode' => 'text',
        'value' => "Delete best answer", // Default
        'disporder' => 5
    ),
    'ba_button_thisis' => array(
        'title' => "$lang->ba_thisis_title",
        'description' => "$lang->ba_thisis_desc",
        'optionscode' => 'text',
        'value' => "This is the best answer", // Default
        'disporder' => 6
    ),
  );  
  
  // Einstellungen in Datenbank speichern
  foreach($setting_array as $name => $setting)
  {
      $setting['name'] = $name;
      $setting['gid'] = $gid;
  
      $db->insert_query('settings', $setting);
  }

  // Rebuild Settings! :-)
  rebuild_settings();


}

function bestanswer_deactivate() {
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'button_ba\']}{$post[\'button_del_ba\']}') . "#i",'');
	find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'ts_class\']}') . "#i",'post_content');
	find_replace_templatesets("postbit", "#" . preg_quote('<br/>{$post[\'countsba\']}') . "#i",'');
}

function bestanswer_is_installed() {
    global $db;
    if($db->field_exists('tsbestansweryn', "threads")) {
        return true;
    } else {
        return false;
    }
}


function bestanswer_ba_button(&$post) {
    global $db, $mybb, $lang, $thread;
	
    $post['ts_class'] = "post_content";
	
	// Datenbank-Query
	//$query = $db->simple_select("threads", "tsbestansweryn, tsbestanswerpid, firstpost, uid", "tid='".$post['tid']."'", array(
    //"limit" => 1
    //));
        
    //$results = $db->fetch_array($query);
    
    if(basename($_SERVER['PHP_SELF']) == "showthread.php") {
		if(isset($mybb->input['bestanswer']) && isset($mybb->input['uid']) && $thread['tsbestansweryn'] == "0" && $mybb->input['bestanswer'] != $thread['firstpost'] && $mybb->user['uid'] == $thread['uid']) {
		$db->query("UPDATE ".TABLE_PREFIX."threads SET tsbestansweryn= '1' WHERE tid = '".$post['tid']."';");
        $db->query("UPDATE ".TABLE_PREFIX."threads SET tsbestanswerpid= '".$mybb->input['bestanswer']."' WHERE tid = '".$post['tid']."';");
        $db->query("UPDATE ".TABLE_PREFIX."threads SET tsbestansweruid= '".$mybb->input['uid']."' WHERE tid = '".$post['tid']."';");
        header('Location: showthread.php?tid='.$post['tid'].'&pid='.$mybb->input['bestanswer'].'#pid'.$mybb->input['bestanswer']);
		}
    }

    if(basename($_SERVER['PHP_SELF']) == "showthread.php") {
        // input(deletebestanswer) muss gesetzt sein und in der Datenbank muss die beste Antwort bereits gewählt wurden sein, zusätzlich muss überprüft werden ob eine Gruppe die Berechtigung dazu hat oder ob Autoren des Themas
        // die beste Antwort löschen können! Wenn ja, muss ebenfalls überprüft werden ob der Benutzer mit der uid == der Autor ist.
		if(isset($mybb->input['deletebestanswer']) && $thread['tsbestansweryn'] == "1" && (in_array($mybb->user['usergroup'], explode(',',$mybb->settings['ba_delete_group'])) || $mybb->settings['ba_thread_author'] == 1 && $mybb->user['uid'] == $thread['uid'])) {
		$db->query("UPDATE ".TABLE_PREFIX."threads SET tsbestansweryn= '0' WHERE tid = '".$post['tid']."';");
        $db->query("UPDATE ".TABLE_PREFIX."threads SET tsbestanswerpid= '0' WHERE tid = '".$post['tid']."';");
        $db->query("UPDATE ".TABLE_PREFIX."threads SET tsbestansweruid= '0' WHERE tid = '".$post['tid']."';");
        header('Location: showthread.php?tid='.$post['tid'].'&pid='.$mybb->input['deletebestanswer'].'#pid'.$mybb->input['deletebestanswer']);
		}
    }

    // Weise Buttons die Settingstexte zu
    $buttonmark = $mybb->settings['ba_button_mark'];
    $buttondelete = $mybb->settings['ba_button_delete'];
    $buttongoto = $mybb->settings['ba_button_goto'];
    $buttonthisis = $mybb->settings['ba_button_thisis'];

    // Zeige für alle Beiträge, außer für den Firstpost und den Autor den Button "Mark as best answer"
    if ($thread['tsbestansweryn'] == 0 && $post['pid'] != $thread['firstpost'] && $post['uid'] != $thread['uid'] && $mybb->user['uid'] == $thread['uid']) {
            $post['button_ba'] = "<a class=\"postbit_reputation_add\" href=\"showthread.php?tid=".$post['tid']."&amp;page=".$page."&amp;bestanswer=".$post['pid']."&amp;uid=".$post['uid']."\"><span>$buttonmark</span></a>";
}
    // Zeige Button "Best Answer" für den besten Beitrag
    if ($thread['tsbestansweryn'] == 1 && $post['pid'] == $thread['tsbestanswerpid']) {
    $post['button_ba'] = "<a class=\"postbit_reputation_add\" href=\"showthread.php?tid=".$post['tid']."&amp;pid=".$thread['tsbestanswerpid']."#pid".$thread['tsbestanswerpid']."\"><span>$buttonthisis</span></a>";
	// wenn berechtigt, wird der Button angezeigt:
		if ($mybb->settings['ba_thread_author'] == 1 && $mybb->user['uid'] == $thread['uid'] || in_array($mybb->user['usergroup'], explode(',',$mybb->settings['ba_delete_group']))) {
            $post['button_del_ba'] = "<a class=\"postbit_qdelete postbit_mirage\" href=\"showthread.php?tid=".$post['tid']."&amp;deletebestanswer=".$post['pid']."\"><span>$buttondelete</span></a>";
		}
	}

    // Zeige Button "Go to best answer" für den Firstpost
    if ($thread['tsbestansweryn'] == 1 && $post['pid'] == $thread['firstpost']) {
    $post['button_ba'] = "<a class=\"postbit_reputation_add\" href=\"showthread.php?tid=".$post['tid']."&amp;pid=".$thread['tsbestanswerpid']."#pid".$thread['tsbestanswerpid']."\"><span>$buttongoto</span></a>";
    }

    //CSS Klassenzuweisung für besten Beitrag
    if ($post['pid'] == $thread['tsbestanswerpid']) {
        $post['ts_class'] = "post_content_ba";
    }
	
	//Hole Daten jedes Users aus der Datenbank - wie viel Best-Answers erhalten?
    $counts = $db->query('SELECT COUNT(*), tsbestansweruid FROM ' . TABLE_PREFIX . 'threads WHERE tsbestansweruid = ' . $post['uid'] . '');
	$counts_re = $db->fetch_array($counts);
	
	// Sofern $counts_re nicht leer, schreibe es in die $post['countsbla'] Variable. Wenn leer, dann schreibe 0 rein!
	
	if (!empty($counts_re['COUNT(*)'])) {
	$post['countsba'] = $counts_re['COUNT(*)'];
	} else
	{
	$post['countsba'] = "0";
	}
	
}

?>