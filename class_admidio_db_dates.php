<?php
class class_admidio_db_dates{

public     $users = array();
public     $dates = array();
public     $status = array();
private    $userfieldids = array();
private	   $roleid ='';
private	   $orgid ='';
private	   $tblname ='';
private	   $options=NULL;

public function __construct($options){
	if (is_file(realpath(dirname(__FILE__).'/'.$options['admidio_path'].'/adm_my_files/config.php'))) {
		require(realpath(dirname(__FILE__).'/'.$options['admidio_path'].'/adm_my_files/config.php'));//admidio V.3+
	}else if (is_file(realpath(dirname(__FILE__).'/'.$options['admidio_path'].'/config.php')))
	{
		require(realpath(dirname(__FILE__).'/'.$options['admidio_path'].'/config.php'));//admidio prior to V.3+
	}else
	{
 		$path=(dirname(__FILE__).'/'.$options['admidio_path']);
		die('Admidio-Installation not found at "'.$path.'"');
	}
	require_once(dirname(__FILE__).'/'.$options['admidio_path'].'/adm_program/system/constants.php');
	$this->orgid=$options['org_id'];
	$this->options=$options;

	$mysqli = new mysqli($g_adm_srv, $g_adm_usr, $g_adm_pw, $g_adm_db);

	if (!$mysqli) {
		die('Connect Error (' . mysqli_connect_errno() . ') '
		        . mysqli_connect_error());
	}
	if (!$mysqli->set_charset("utf8")) {
    	printf("Error loading character set utf8: %s\n", $mysqli->error);
	}

	$this->db=$mysqli;
	$this->load_dates($options['dates_after'],$options['dates_before']);
	if($options['use_dirtydates'])
	{
		$this->tblname=$g_tbl_praefix.'_dirtydates';
		$this->check_install_db();
		$this->set_roleid($options['adm_role']);
		$this->load_users();
		$this->load_status();
	}
	
}
function __destruct() {
	$this->db->close();
}


function get_user_by_id($userid)
{
	if(empty($this->users))
	{
		$this->load_users();
	}
	if(!isset($this->users[$userid]))
	{
		return false;
	}else
	{
		return $this->users[$userid];
	}
}

private function set_roleid($admidio_rolename)
{
	$result = $this->db->query('SELECT * FROM '. TBL_ROLES .' where rol_name=\''.$admidio_rolename.'\' Limit 1');

	if($result->num_rows===0)
	{
		die('Rolle nicht gefunden!');
	}
	$admroleid=$result->fetch_array();
	$this->roleid=$admroleid[0];
	return $this->roleid;
}


function load_users()
{
	$this->load_user_fields();
	$grouping_field=strtoupper($this->options['group_field']);

	//only active members
		$memberCondition = ' AND EXISTS 
		    (SELECT 1
		       FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
		      WHERE mem_usr_id = usr_id
		        AND mem_rol_id = rol_id
		        AND mem_begin <= \''.DATE_NOW.'\'
		        AND mem_end    > \''.DATE_NOW.'\'
				AND cat_name_intern <> \'CONFIRMATION_OF_PARTICIPATION\'
		        AND rol_valid  = 1
		        AND rol_id  = '.$this->roleid.'
		        AND rol_cat_id = cat_id
		        AND (  cat_org_id = '. $this->orgid. '
		            OR cat_org_id IS NULL )) ';

	$searchCondition="";

if (!empty($grouping_field)){
$grouping_select=', grouping.usd_value as grouping';
$grouping='LEFT JOIN '.TBL_USER_DATA.' as grouping
		           ON grouping.usd_usr_id = usr_id
		          AND grouping.usd_usf_id = '. $this->userfieldids[$grouping_field]['usf_id'];
	$orderCondition="ORDER grouping, last_name, first_name";//unsortierte am Anfang
	//$orderCondition="ORDER BY CASE WHEN grouping is null THEN 1 ELSE 0 END, grouping, last_name, first_name";//unsortierte am Ende
}else
{
$grouping_select="";
$grouping="";
$orderCondition="ORDER BY last_name.usd_value, first_name.usd_value";
}
	$sql    = 'SELECT usr_id, last_name.usd_value as last_name, first_name.usd_value as first_name, birthday.usd_value as birthday '.$grouping_select.' FROM '. TBL_USERS. '
		         JOIN '.TBL_USER_DATA.' as last_name
		           ON last_name.usd_usr_id = usr_id
		          AND last_name.usd_usf_id = '. $this->userfieldids['LAST_NAME']['usf_id']. '
		         JOIN '.TBL_USER_DATA.' as first_name
		           ON first_name.usd_usr_id = usr_id
		          AND first_name.usd_usf_id = '.$this->userfieldids['FIRST_NAME']['usf_id']. '
		         LEFT JOIN '.TBL_USER_DATA.' as birthday
		           ON birthday.usd_usr_id = usr_id
		          AND birthday.usd_usf_id = '. $this->userfieldids['BIRTHDAY']['usf_id'].
				$grouping. '
		         WHERE usr_valid = 1'.$memberCondition.$searchCondition.' '.$orderCondition.';';
	$users=$this->db->query($sql);

	if($users->num_rows==0)
	{	
		echo "No user found @role ".$this->options['adm_role'];
		return false;
	}

	$this->users = array();

	while($user = $users->fetch_array())
	{
		$this->users[$user['usr_id']]=$user;
		if($this->userfieldids[$grouping_field]['usf_type']==='DROPDOWN')
		{
			$groupnames=explode("\n",$this->userfieldids[$grouping_field]['usf_value_list']);
			$this->users[$user['usr_id']]['group_name']=$groupnames[intval($user['grouping'])-1];
		}else
		{
			$this->users[$user['usr_id']]['group_name']=$user['grouping'];
		}
	}

	if(empty($this->users))
	{
		return false;
	}
	else
	{
		return true;
	}

}

function load_dates($display_after_timestamp,$display_before_timestamp)
{
	$sql='SELECT * FROM '. TBL_DATES.'
		where `dat_begin` >=\''.date('Y-m-d H:i:s',$display_after_timestamp).'\'
		AND  `dat_end` <=\''.date('Y-m-d H:i:s',$display_before_timestamp).'\'
		ORDER BY `'. TBL_DATES.'`.`dat_begin` ASC';
	//echo $sql;
	$dates =$this->db->query($sql);

	if($dates->num_rows===0)
	{
		return false;
	}

	while($date = $dates->fetch_array())
		{
			$this->dates[$date['dat_id']]=$date;
		}

	if(empty($this->dates))
	{
		return false;
	}
	else
	{
		return true;
	}
}

function load_status()
{
	if(!$this->options['use_dirtydates'])
	{
		die('Dirtydates disabled, enable in options.php!');
	}
	$sql='SELECT * FROM `'.$this->tblname.'`';

	$status =$this->db->query($sql);

	if($status->num_rows===0)
	{
		return false;
	}

	while($state = $status->fetch_array())
		{
			if(isset($this->dates[$state['dd_date_id']]))
			{
				$this->status[$state['dd_usr_id']][$state['dd_date_id']]=$state;
			}
		}

}


function save_status($userid,$changes)
{
	if(!$this->options['use_dirtydates'])
	{
		die('Dirtydates disabled, enable in options.php!');
	}

	//update cache	
	$this->load_status();
	foreach($changes as $dateid=>$value){
		$createdby='1';
		$updatedby='2';
		$status=$value['status'];
		$comment=$value['comment'];
		if(!isset($this->status[$userid][$dateid]['dd_status'])||($this->status[$userid][$dateid]['dd_status']!==$status)||($this->status[$userid][$dateid]['dd_comment']!==$comment)){
			$sql='INSERT INTO `'.$this->tblname.'` 
				(dd_usr_id,dd_date_id,dd_status,dd_comment,dd_usr_id_create) 
				VALUES ('.$userid.','.$dateid.','.$status.',\''.$comment.'\',\''.$createdby.'\')
				ON DUPLICATE KEY UPDATE 
				dd_status='.$status.',dd_comment=\''.$comment.'\',dd_usr_id_change=\''.$updatedby.'\',dd_timestamp_change=NOW();';
			$result =$this->db->query($sql);
		}
	}
}




function check_install_db()
{
	if(!$this->options['use_dirtydates'])
	{
		die('Dirtydates disabled, enable in options.php!');
	}
	$sql='SHOW TABLES LIKE \''.$this->tblname.'\';';

	$result =$this->db->query($sql);
	if($result->num_rows===1)
	{
		return true;
	}else{
		echo "datenbank nicht vorhanden<br>";
		$this->install_db();
	}
}

private function install_db()
{
	if(!$this->options['use_dirtydates'])
	{
		die('Dirtydates disabled, enable in options.php!');
	}
	$sql='CREATE TABLE `'.$this->tblname.'` (
		dd_id int(10) NOT NULL auto_increment,
		dd_date_id int(10) NOT NULL,
		dd_usr_id int(10) NOT NULL,
		dd_status tinyint(1) NOT NULL,
		dd_comment varchar(100) NOT NULL,
		dd_usr_id_create int(10) NOT NULL,
		dd_timestamp_create timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		dd_usr_id_change int(10) NOT NULL,
		dd_timestamp_change timestamp NOT NULL,
		Primary KEY (dd_id), UNIQUE (dd_date_id, dd_usr_id));';
	$result =$this->db->query($sql);
	echo "Leere Datenbank initialisiert!<br>";
}

private function load_user_fields()
{
	$sql='SELECT * FROM `'.TBL_USER_FIELDS.'`';
	$userfields =$this->db->query($sql);
	while($userfield = $userfields->fetch_array())
	{
		$this->userfieldids[$userfield['usf_name_intern']]=$userfield;
	}

}



//end of class
}

?>
