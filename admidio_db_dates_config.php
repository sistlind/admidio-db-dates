<?php
//$PASSWORD="pw";

$options=array('admidio_path'=>'../../../../admidio',//pfad zur admidio installation
				'org_id'=>1,				//organisations id von admidio(meist 1)
				'adm_role'=>'Mitglieder',		//Mitglieder welcher Rolle sollen angezeigt werden
				'dates_after'=>time()-(60*60*24)*1,//Nur bis gestern laden
				'dates_before'=>time()+(60*60*24)*62,//Nur die nächsten 2 Monate
				'use_dirtydates'=>true,//Dirtydates funktion wird genutzt und tabelle angelegt
);
?>
