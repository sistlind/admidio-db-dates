<?php
 //session_unset();
################
## Header
################
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
ob_start('mb_output_handler');
error_reporting(-1);
ini_set('display_errors', 'On');

preg_match('/(^[^\?]+)(\Z|\?userid=(\d+))/',$_SERVER['REQUEST_URI'],$matches);
//print_r($matches);
if(empty($matches))
	$formcallback=$_SERVER['REQUEST_URI'];
else
	$formcallback=$matches[1];

if ((isset($_REQUEST['userid'])||isset($matches[3]) )&& !isset($_REQUEST['overview']))
{
	if(isset($_REQUEST['userid']))
		$userid=$_REQUEST['userid'];
	else
		$userid=$matches[3];
	//echo "<p>(Debug)Userid:".$userid."</p>";
}else
{
	$userid=NULL;
}

//echo $formcallback;


################
## Include Admidio common for admidio-variables
################
include_once(dirname(__FILE__).'/admidio_db_dates_config.php');
include_once(dirname(__FILE__).'/class_admidio_db_dates.php');

################
## Allgemeine Variablen
################

$wochentag_lang = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
$wochentag = array("So", "Mo", "Di", "Mi", "Do", "Fr", "Sa");


################
## Check Session and passwort
################
//echo '<p><a href="/">Zurück zur Homepage!</a></p>';
if(!session_id())
{
	session_start();
}
//echo session_id()."</br>";
if(!empty($_REQUEST['pw']))
{
	$pw=$_REQUEST['pw'];
}else if(!empty($_SESSION['pw']))
{
	$pw=$_SESSION['pw'];
}

if (empty($pw)||$pw !== $PASSWORD)
{
	echo "Session abgelaufen, bitte neu einloggen !<br>";
	echo '<form action="'.$formcallback.'" method="post">
		Passwort:<br><input type="Password" name="pw">
		<input type="Submit" value="Absenden"></form>';
	die();
}else
{
	$_SESSION['pw']=$pw;
	//echo "Session noch aktiv!";
}
$dd =new class_admidio_db_dates ($options);



//print_r($dates->fetch_array());

//--------------------------------
//Ansichts-Menü darstellen
echo "<form action=".$formcallback." method=post\n>";
echo "<select size=\"1\" name=\"userid\" class=\"eingabetext\">";
echo "<option value=\"" . "--" . "\">";
echo "User auswählen";
echo "</option>\n";


foreach( $dd->users as $datensatz)
{
	echo "<option value=\"" . $datensatz['usr_id'] . "\">";
	echo $datensatz['first_name']." ".$datensatz['last_name']." (".$datensatz['birthday'].")";
	echo "</option>\n";
}
echo "</select>\n";
echo "<input type=\"submit\" value=\"Los gehts !\">\n";
echo "<input type=\"submit\" name=\"overview\" value=\"Zurück zur Übersicht\">\n";
echo "</form>\n";


//-----------------------------------
//Status eines Users ändern
//-----------------------------------
if (isset($_REQUEST['status_changed']))
{
	/*
	echo "<pre>";
	print_r($_REQUEST);
	echo "-------------Parse Request------------<br>";
	*/
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
	$userid='--';
}

//------------------------------------
//Übersichtsmenü darstellen mit allen Usern und Daten
//------------------------------------
if($userid =='--' or !isset($userid))
{
	//tabelle erzeugen
	echo "<table width=200 border=1>\n<tr>";
	echo"<th>Name</th>";
	foreach( $dd->dates as $date)
	{
		echo "<th style=\"width:400\">";
		echo $date['dat_headline'];
		echo "</th>";
		$dataID[]=$date['dat_id'];
	}
	echo "</tr>\n<tr>";
	echo "<td><center>Zeit</center></td>";
	foreach($dd->dates as $date)
	{
		$time=strtotime($date['dat_begin']);
		echo "<td style=\"width:400\">";
		if (date('H:i',$time)=="00:00")
		{
			echo $wochentag_lang[date('w',$time)]."<br>".date(' d.m.y',$time)."<br>??&nbsp;Uhr";
		}
		else
		{
			echo $wochentag_lang[date('w',$time)]."<br>".date(' d.m.y',$time)."<br>".date('H:i',$time).'&nbsp;Uhr';
		}
		echo "</td>";
	}
	echo "</tr>\n";
	foreach( $dd->users as $user)
	{
		echo "<tr>";
		echo "<td><a href=".$formcallback."?userid=".$user['usr_id'].">".$user['first_name'].'&nbsp;'.$user['last_name']."</a></td>";

		for ($i = 0; $i < count($dataID); $i++) {
			if(isset($dd->status[$user['usr_id']][$dataID[$i]]))
			{
				$status=$dd->status[$user['usr_id']][$dataID[$i]]['dd_status'];
			}else{
				$status=false;
			}
			if ($status==2) echo "<TD BGCOLOR=red>";
			else if ($status==1)echo "<TD BGCOLOR=green>";
			else if ($status==3)echo "<TD BGCOLOR=yellow>";
			else echo "<TD>";

			if(!empty($dd->status[$user['usr_id']][$dataID[$i]]['dd_comment']))
			{
			$comment=$dd->status[$user['usr_id']][$dataID[$i]]['dd_comment'];
				echo "<abbr title=\"".$comment."\">Info</abbr></td>";
			}else
			{
				echo "</td>";
			}
		}
		echo "</tr>\n";
	}
	echo "</table>";
}
//------------------------------------
//Seite für Zu-/Absagen darstellen
//------------------------------------
else//user-änderungs-dialog anzeigen, wenn user ausgewählt wurde:
{
	$user=$dd->get_user_by_id($userid);
	if($user===false)
	{
		echo('<p>Fehler, User mit id "'.$userid.'" nicht gefunden!</p>');
		exit;
	}
	echo "<p><font size=+4 color=red>".$user['first_name']." ".$user['last_name']."</font></p>\n";
	echo "<form action=".$formcallback." method=post>";
	echo "<table border=1>\n";
	echo '<tr><th>Termin</th><th>Datum</th><th>weitere Infos</th><th>JA&nbsp;-&nbsp;?&nbsp;-&nbsp;Nein&nbsp;-&nbsp;Reset</th><th>Kommentar</th></tr>';


	foreach($dd->dates as $date)
	{//pro termin eine reihe!
		$time=strtotime($date['dat_begin']);
		if(isset($dd->status[$userid][$date['dat_id']]))
		{
			$oldstatus=$dd->status[$userid][$date['dat_id']]['dd_status'];
		}else{
			$oldstatus=0;
		}
		if(isset($dd->status[$userid][$date['dat_id']]['dd_comment']))
		{
			$oldcomment=$dd->status[$userid][$date['dat_id']]['dd_comment'];
		}else
		{
			$oldcomment='';
		}	
		$checked_no='';
		$checked_yes='';
		$checked_perhaps='';
			if ($oldstatus[0]==2) $checked_no='checked';
			else if ($oldstatus[0]==1)$checked_yes='checked';
			else if ($oldstatus[0]==3)$checked_perhaps='checked';

		echo "\n<tr>";
		echo "<td>".$date['dat_headline']."</td>";
		echo "<td>".$wochentag[date('w',$time)].',&nbsp;'.date('d.m.y',$time)." <br> um ".date('G:i',$time)."</td>";
		echo "<td>".$date['dat_description']."</td>";
		echo "<td><input type=radio name=status_new_".$date['dat_id']." value=1 ".$checked_yes."> 
			<input type=radio name=status_new_".$date['dat_id']." value=3 ".$checked_perhaps.">
			<input type=radio name=status_new_".$date['dat_id']." value=2 ".$checked_no.">
			<input type=radio name=status_new_".$date['dat_id']." value=0 ></td>";
		echo "<td><input type=text name=status_new_comment_".$date['dat_id']." value=\"".$oldcomment."\" >";
		echo "</tr>\n";
		}
	echo "</table>\n";
	echo "<input type=hidden name=status_changed value=1>\n";
	echo "<input type=hidden name=userid value=".$userid.">\n";
	echo "<input type=\"submit\" value=\"Änderungen speichern\">\n";
	echo "</form>\n";
}


?>
