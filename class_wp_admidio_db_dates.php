<?php

class wp_admidio_db_dates {

	private static $options = NULL;
	/**
	 * Initialize all the things
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		require_once(dirname(__FILE__).'/class_admidio_db_dates.php');
		require_once(dirname(__FILE__).'/admidio_db_dates_config.php');
		//Get default options
		self::$options = $options;
	
		// Text domain

		// Actions


 		// Styles
	   	wp_register_style( 'admidio_dates_style', plugins_url('/css/admidio_dates.css', __FILE__), false, false, 'all');
		wp_enqueue_style( 'admidio_dates_style' );
		//Shortcode
		add_shortcode('admidio_dates',array($this, 'show_dates'));
		add_shortcode('admidio_dirtydates',array($this, 'dirtydates'));
	}


/* Shortcode show all dates (public) */
function show_dates($atts){
	if(empty($_REQUEST['admidio_dates_year']))
	{
		$year=date('Y');
	}else{
		$year=$_REQUEST['admidio_dates_year'];
	}

	self::$options['dates_after']=mktime(0, 0, 0, 1, 1, $year);
	self::$options['dates_before']=mktime(0, 0, 0, 1, 1, $year+1);
	if (!isset($_REQUEST['admidio_dates_year'])&&(time()>mktime(0,0,0,12,1,date('Z'))))//From December in current year, also show dates from January(only if no specifiy year is requested!)
	{
	self::$options['dates_before']=mktime(0, 0, 0, 31, 1, $year+1);
	}
	$dd =new class_admidio_db_dates(self::$options);

	$out='';
	$out='<h2>Termine im Jahr '.$year.'</h2>';
	//Display table
	$out.="<div class=admidio_dates_table>";
	$out.="<table><thead>";
	$out.="<tr>";
	$out.="<th>Termin</th>";
	$out.="<th>Veranstaltung</th>";
	$out.="<th>Ort</th>";
	$out.="<th>Infos</th>";
	$out.="</tr></thead><tbody>\n";
	foreach($dd->dates as $i=>$date)
	{

	$starttime=strtotime($date['dat_begin']);
	$endtime=strtotime($date['dat_end']);

	//echo print_r($datensatz);
		$out.="";
		if ($starttime  < (time()-60*60*24)){
			$out.="<tr class=\"oldevent\" id=\"".$date['dat_id']."\">";
		}else{
			$out.="<tr id=\"".$date['dat_id']."\">";
		}


		$out.="<td>".date_i18n('D, d.m.y',$starttime);
			if (date('H:i',$starttime)=="00:00")
			{
				if (($endtime-$starttime)<60*60*24){
					$out.="</br> ~</td>";
				}else{
					$out.="</br> bis </br>".date_i18n('D, d.m',$endtime)."</td>";
				}

			}else
			{
				if (($endtime-$starttime)<60*60*24){
					$out.="</br> ".date('H:i',$starttime);
					$out.="-".date('H:i',$endtime)."</td>";
				}else{
					$out.="</br> ".date('H:i',$starttime);
					$out.=" bis </br>".date_i18n('D, d.m H:i',$endtime)."</td>";
				}
			}

		$out.="<td>".$date['dat_headline']."</td>";
		$out.="<td>".$date['dat_location']."</td>";
		preg_match('/<p>\s(.*)<\/p>/s',$date['dat_description'],$matches);
		//print_r($matches);
		$out.="<td>".$matches[1]."</td>";
		$out.="</tr>";

	}

	$out.="</tbody></table></div>";
	$out.="<div>";
	//Display form to change year
	$form='<h2>Frühere Termine</h2>';
	$form.='<form class="contact-form" method="post" action="' . get_permalink() . '">';
	$form.='<select name="admidio_dates_year" id="admidio_dates_year" />';
		for ($formyear=intval(date('Y',time()+60*60*24*31)); $formyear>=2009; $formyear--)
		{
			if($formyear==$year)
					$form.='<option selected="selected">';
			else	$form.='<option>';
			$form.=$formyear.'</option>';
		}
	$form.='</select>';
	$form.='<input type="submit" value="Jahr ändern" name="send" id="cf_send" />';
	$form.='</form></div>';


	$out.=$form;

	unset($dd);
	return $out;
}

function dirtydates($atts)
{
	if(isset($_REQUEST['dd_userid']) && !isset($_REQUEST['overview']))
	{
		$userid=$_REQUEST['dd_userid'];
	}

	$dd =new class_admidio_db_dates(self::$options);
	//--------------------------------
	//Ansichts-Menü darstellen
	$form="<form action=".get_permalink()." method=post\n>";
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
		$out="<div class=admidio_dirtydates_overview_table>";
		$out.="<table>\n<tr>";
		$out.="<th>Gruppe</th>";
		$out.="<th>Name</th>";
		foreach( $dd->dates as $date)
		{
			$out.= "<th class=\"admidio_dirtydates_headline\" id=\"".$date['dat_id']."\">";
			$out.= $date['dat_headline'];
			$out.= "</th>";
			$dataID[]=$date['dat_id'];
		}
		$out.= "</tr>\n<tr>";
		$out.= "<td></td>";
		$out.= "<td>Termin</td>";
		foreach($dd->dates as $date)
		{
			$time=strtotime($date['dat_begin']);
			$out.= "<td class=\"admidio_dirtydates_time\" id=\"".$date['dat_id']."\">";
			if (date('H:i',$time)=="00:00")
			{
				$out.= date_i18n('D, d.m.y',$time)."<br>??&nbsp;Uhr";
			}
			else
			{
				$out.= date_i18n('D, d.m.y',$time)."<br>".date('H:i',$time).'&nbsp;Uhr';
			}
			$out.= "</td>";
		}
		$out.= "</tr>\n";

		foreach( $dd->users as $user)
		{
			$out.= "<tr>";
			$out.= "<td>".$user['group_name']."</td>";
			$out.= "<td><a href=".get_permalink()."?dd_userid=".$user['usr_id'].">".$user['first_name'].'&nbsp;'.$user['last_name']."</a></td>";

			for ($i = 0; $i < count($dataID); $i++) {
				if(isset($dd->status[$user['usr_id']][$dataID[$i]]['dd_status']))
				{
					$status=$dd->status[$user['usr_id']][$dataID[$i]]['dd_status'];
				}else{
					$status=false;
				}
				if ($status==2) $out.= "<TD class=\"admidio_dirtydates_status\" style=\"background-color:red;\" id=\"".$dataID[$i]."\">";
				else if ($status==1)$out.= "<TD class=\"admidio_dirtydates_status\" style=\"background-color:green;\" id=\"".$dataID[$i]."\">";
				else if ($status==3)$out.= "<TD class=\"admidio_dirtydates_status\" style=\"background-color:yellow;\" id=\"".$dataID[$i]."\">";
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
		$out.= "</table></div>";
	}
	//------------------------------------
	//Seite für Zu-/Absagen darstellen
	//------------------------------------
	else//user-änderungs-dialog anzeigen, wenn user ausgewählt wurde:
	{
		$user=$dd->get_user_by_id($userid);
		if($user===false)
		{
			$out.=('<p>Fehler, User mit id "'.$userid.'" nicht gefunden!</p>');
			return $out;
		}
		$out.="<div class=admidio_dirtydates_change_table>";
		$out.= "<p id=dirtydates_user>".$user['first_name']." ".$user['last_name']."</p>\n";
		$out.= "<form action=".get_permalink()." method=post>";
		$out.= "<table>\n";
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
			$out.= "<td>".date_i18n('D, d.m.y </br> \u\m G:i',$time)."</td>";
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
		$out.= "</form></div>\n";
	}

return $form.$out;

}
	
}
