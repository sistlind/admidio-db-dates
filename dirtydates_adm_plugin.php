<?php


################
## Include Admidio common for admidio-variables
################


require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');
require_once(SERVER_PATH. '/adm_program/system/classes/formelements.php');
require_once(SERVER_PATH. '/adm_program/system/classes/tabletext.php');


$options=array('admidio_path'=>'../../../admidio',//pfad zur admidio installation
				'org_id'=>$gCurrentOrganization->getValue('org_id'),				//organisations id von admidio(meist 1)
				'adm_role'=>'Mitglieder',		//Mitglieder welcher Rolle sollen angezeigt werden
				'dates_after'=>time()-(60*60*24)*1,//Nur bis gestern laden
				'dates_before'=>time()+(60*60*24)*62,//Nur die nächsten 2 Monate
				'use_dirtydates'=>true,//Dirtydates funktion wird genutzt und tabelle angelegt
);


if (!function_exists('print_dirtydates_menu')) {
	function print_dirtydates_menu()
	{
		$plugin_folder='admidio-db-dates';
		global $gCurrentUser;
		$awardmenu = new Menu('dirtydatesmenu', 'Terminzu/absagen');
			$awardmenu->addItem('dirtydates_show', '/adm_plugins/'.$plugin_folder.'/dirtydates_adm_plugin.php?dd_userid=overview',
					'Terminübersicht', '/icons/lists.png');
		if($gCurrentUser->getValue('usr_id')!=0)
		{
			$awardmenu->addItem('dirtydates_change', '/adm_plugins/'.$plugin_folder.'/dirtydates_adm_plugin.php?dd_userid='.$gCurrentUser->getValue('usr_id').'','Meine Anwesenheit ändern', '/icons/profile.png');
		}

		echo' <div id="plgAwards" class="admPluginContent">';
		$awardmenu->show();  
		echo' </div>';
	}
}



if (!function_exists('dirtydates')) {
function dirtydates($userid,$options)
{
	$formcallback=$_SERVER['REQUEST_URI'];
	global $gCurrentUser;
	$dd =new class_admidio_db_dates($options);
	//--------------------------------
	//Ansichts-Menü darstellen
	$form="<form action=".$formcallback." method=post\n>";
	$form.="<select size=\"1\" name=\"dd_userid\" class=\"eingabetext\">";
	$form.= "<option value=\"" . "--" . "\">";
	$form.= "User auswählen";
	$form.= "</option>\n";


	foreach( $dd->users as $datensatz)
	{
		$form.= "<option value=\"" . $datensatz['usr_id'] . "\">";
		$form.= $datensatz['first_name']." ".$datensatz['last_name']." (".$datensatz['birthday'].")";
		$form.= "</option>\n";
	}
	$form.= "</select>\n";
	$form.= "<input type=\"submit\" value=\"Los gehts !\">\n";
	$form.= "<input type=\"submit\" name=\"overview\" value=\"Zurück zur Übersicht\">\n";
	$form.= "</form>\n";


	//-----------------------------------
	//Status eines Users ändern
	//-----------------------------------
	if (isset($_REQUEST['status_changed']))
	{
		if(!(($gCurrentUser->getValue('usr_id')===($userid))||($gCurrentUser->editUsers() === true)))
		{
			return 'Not allowed!';
		}
	$changes=array();
	foreach($_REQUEST as $key=>$value)
	{
		preg_match('/^status_new_([0-9]+)/', $key, $match);
		if(!isset($match[1])){continue;}
		$dateid=$match[1];

		/*print_r($match);
		echo $dateid." changed to ".$value."<br>";
		*/
		$changes[$dateid]['status']=intval($value);
		$changes[$dateid]['comment']=htmlspecialchars($_REQUEST['status_new_comment_'.$dateid]);
	}

	$dd->save_status($userid,$changes);
	//reload status
	$dd->load_status();
	//load overview
	$userid='overview';
	}

	//------------------------------------
	//Übersichtsmenü darstellen mit allen Usern und Daten
	//------------------------------------
	if($userid =='overview' or !isset($userid))
	{
		//tabelle erzeugen
		$out="<table width=200 border=1>\n<tr>";
		$out.="<th>Name</th>";
		foreach( $dd->dates as $date)
		{
			$out.= "<th class=\"admidio_dirtydates_headline\" id=\"".$date['dat_id']."\">";
			$out.= $date['dat_headline'];
			$out.= "</th>";
			$dataID[]=$date['dat_id'];
		}
		$out.= "</tr>\n<tr>";
		$out.= "<td>Termin</td>";
		foreach($dd->dates as $date)
		{
			$time=strtotime($date['dat_begin']);
			$out.= "<td class=\"admidio_dirtydates_time\" id=\"".$date['dat_id']."\">";
			if (date('H:i',$time)=="00:00")
			{
				$out.= date('D, d.m.y',$time)."<br>??&nbsp;Uhr";
			}
			else
			{
				$out.= date('D, d.m.y',$time)."<br>".date('H:i',$time).'&nbsp;Uhr';
			}
			$out.= "</td>";
		}
		$out.= "</tr>\n";

		foreach( $dd->users as $user)
		{
			$out.= "<tr>";
		if(!(($gCurrentUser->getValue('usr_id')===($user['usr_id']))||($gCurrentUser->editUsers() === true)))
		{//only show name for others
			$out.= "<td>".$user['first_name'].'&nbsp;'.$user['last_name']."</td>";
		}else
		{// for myself and admins show link
			$out.= "<td><a href=".$_SELF."?dd_userid=".$user['usr_id'].">".$user['first_name'].'&nbsp;'.$user['last_name']."</a></td>";
		}

			for ($i = 0; $i < count($dataID); $i++) {
				if(isset($dd->status[$user['usr_id']][$dataID[$i]]['dd_status']))
				{
					$status=$dd->status[$user['usr_id']][$dataID[$i]]['dd_status'];
				}else{
					$status=false;
				}
				if ($status==2) $out.= "<TD class=\"admidio_dirtydates_status\" BGCOLOR=red id=\"".$dataID[$i]."\">";
				else if ($status==1)$out.= "<TD class=\"admidio_dirtydates_status\" BGCOLOR=green id=\"".$dataID[$i]."\">";
				else if ($status==3)$out.= "<TD class=\"admidio_dirtydates_status\" BGCOLOR=yellow id=\"".$dataID[$i]."\">";
				else $out.= "<TD id=\"".$dataID[$i]."\">";
			
				if(!empty($dd->status[$user['usr_id']][$dataID[$i]]['dd_comment']))
					{
						$comment=$dd->status[$user['usr_id']][$dataID[$i]]['dd_comment'];
						$out.='<abbr title="'.$comment.'">Info</abbr></td>';
					}else{
						$out.="</TD>";
					}
			}
			$out.= "</tr>\n";
		}
		$out.= "</table>";
	}
	//------------------------------------
	//Seite für Zu-/Absagen darstellen
	//------------------------------------
	else//user-änderungs-dialog anzeigen, wenn user ausgewählt wurde:
	{
		if(!(($gCurrentUser->getValue('usr_id')===($userid))||($gCurrentUser->editUsers() === true)))
		{
			return 'Not allowed!';
		}
		$user=$dd->get_user_by_id($userid);
		if($user===false)
		{
			$out.=('<p>Fehler, User mit id "'.$userid.'" nicht gefunden!</p>');
			return $out;
		}
		$out.= "<p><font size=+4 color=red>".$user['first_name']." ".$user['last_name']."</font></p>\n";
		$out.= "<form action=".$formcallback." method=post>";
		$out.= "<table border=1>\n";
		$out.= '<tr><th>Termin</th><th>Datum</th><th>weitere Infos</th><th>JA&nbsp;-&nbsp;?&nbsp;-&nbsp;Nein&nbsp;-&nbsp;Reset</th><th>Kommentar</th></tr>';


		foreach($dd->dates as $date)
		{//pro termin eine reihe!
			$time=strtotime($date['dat_begin']);
			if(isset($dd->status[$userid][$date['dat_id']]))
			{
				$oldstatus=$dd->status[$userid][$date['dat_id']]['dd_status'];
			}else{
				$oldstatus=0;
			}
				$oldcomment=$dd->status[$userid][$date['dat_id']]['dd_comment'];
				$checked_no='';
				$checked_yes='';
				$checked_perhaps='';
				if ($oldstatus[0]==2) $checked_no='checked';
				else if ($oldstatus[0]==1)$checked_yes='checked';
				else if ($oldstatus[0]==3)$checked_perhaps='checked';
			$out.= "\n<tr>";
			$out.= "<td>".$date['dat_headline']."</td>";
			$out.= "<td>".date('D, d.m.y </br> \u\m G:i',$time)."</td>";
			$out.= "<td>".$date['dat_description']."</td>";


			$out.= "<td><input type=radio name=status_new_".$date['dat_id']." value=1 ".$checked_yes."> 
				<input type=radio name=status_new_".$date['dat_id']." value=3 ".$checked_perhaps.">
				<input type=radio name=status_new_".$date['dat_id']." value=2 ".$checked_no.">
				<input type=radio name=status_new_".$date['dat_id']." value=0 ></td>";
			$out.= "<td><input type=text name=status_new_comment_".$date['dat_id']." value=\"".$oldcomment."\">"; 
			$out.= "</tr>\n";
			}
		$out.= "</table>\n";
		$out.= "<input type=hidden name=status_changed value=1>\n";
		$out.= "<input type=hidden name=dd_userid value=".$userid.">\n";
		$out.= "<input type=\"submit\" value=\"Änderungen speichern\">\n";
		$out.= "</form>\n";
	}
	if($gCurrentUser->editUsers() === true)
	{//only show form for admins
		return $form.$out;
	}
	else
	{
		return $out;
	}
}
}



if (isset($_REQUEST['dd_userid']))
{
	$userid=$_REQUEST['dd_userid'];
	unset($_REQUEST['dd_userid']);
	//call dirtydates
	require_once(dirname(__FILE__).'/class_admidio_db_dates.php');
	$page = new HtmlPage('Dirtydates');
	if($gCurrentUser->getValue('usr_id')==0){
		$page->addHtml('Login required!');
	}else{
		$page->addHtml(dirtydates($userid,$options));
	}
	$page->show();
}else
{
	$userid=NULL;
	if($gCurrentUser->getValue('usr_id')!=0){
		print_dirtydates_menu();
	}
//global $gCurrentUser;
//print_r($gCurrentUser);
}

//echo $formcallback;


