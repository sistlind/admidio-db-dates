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

 		// Filters

		//Shortcode
		add_shortcode('admidio_dates',array($this, 'show_dates'));
	}


/* Shortcode display author */
function show_dates($atts){
	if(empty($_REQUEST['admidio_dates_year']))
	{
		$year=date('Y');
	}else{
		$year=$_REQUEST['admidio_dates_year'];
	}

	self::$options['dates_after']=mktime(0, 0, 0, 1, 1, $year);
	self::$options['dates_before']=mktime(0, 0, 0, 12, 31, $year);
	$dd =new class_admidio_db_dates(self::$options);

	$wochentage = explode(',','So,Mo,Di,Mi,Do,Fr,Sa');
	$output_string='';
	$output_string='<h2>Termine im Jahr '.$year.'</h2>';
	//Display table
	$output_string.="<table>";
	$output_string.="<tr>";
	$output_string.="<th>Termin</th>";
	$output_string.="<th>Veranstaltung</th>";
	$output_string.="<th>Ort</th>";
	$output_string.="<th>Infos</th>";
	$output_string.="</tr>";
	foreach($dd->dates as $i=>$date)
	{

	$starttime=strtotime($date['dat_begin']);
	$endtime=strtotime($date['dat_end']);

	//echo print_r($datensatz);
		if ($starttime  < ($today-60*60*24)){
			$style="style=\"color: gray;\"";
		}else{
			$style="";
		}

		$output_string.="<tr>";
		$output_string.="<td ".$style.">".$wochentage[date('w',$starttime)].date(',  d.m.y',$starttime);
			if (date('H:i',$starttime)=="00:00")
			{
				if (($endtime-$starttime)<60*60*24){
					$output_string.="</br> ~</td>";
				}else{
					$output_string.="</br> bis </br>".$wochentage[date('w',$endtime)].date(',  d.m',$endtime)."</td>";
				}

			}else
			{
				if (($endtime-$starttime)<60*60*24){
					$output_string.="</br> ".date('H:i',$starttime);
					$output_string.="-".date('H:i',$endtime)."</td>";
				}else{
					$output_string.="</br> ".date('H:i',$starttime);
					$output_string.=" bis </br>".$wochentage[date('w',$endtime)].date(',  d.m H:i',$endtime)."</td>";
				}
			}

		$output_string.="<td>".$date['dat_headline']."</td>";
		$output_string.="<td>".$date['dat_location']."</td>";
		$output_string.="<td>".$date['dat_description']."</td>";
		$output_string.="</tr>";

	}

	$output_string.="</table>";
//Dipsplay form to change year
	$form='<h2>Frühere Termine</h2>';
	$form.='<form class="contact-form" method="post" action="' . get_permalink() . '">';
	$form.='<select name="admidio_dates_year" id="admidio_dates_year" />';
		for ($year=intval(date('Y')); $year>=2009; $year--)
		{
			$form.='<option>'.$year.'</option>';
		}
	$form.='</select>';
	$form.='<input type="submit" value="Jahr ändern" name="send" id="cf_send" />';
	$form.='</form>';


	$output_string.=$form;

	unset($dd);
	return $output_string;
}
	
}
