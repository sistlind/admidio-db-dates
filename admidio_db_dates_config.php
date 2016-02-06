<?php
//$PASSWORD="pw";

$options=array('admidio_path'=>'../../../../admidio',//pfad zur admidio installation
				'org_id'=>1,				//organisations id von admidio(meist 1)
				'adm_role'=>'Mitglied',		//Mitglieder welcher Rolle sollen angezeigt werden
				'group_field'=>'',		//Feld, nachdem die Mitglieder gruppiert werden sollen
				'dates_after'=>time()-(60*60*24)*1,//Nur bis gestern laden
				'dates_before'=>time()+(60*60*24)*100,//Nur die nächsten 100Tage
				'use_dirtydates'=>true,//Dirtydates funktion wird genutzt und tabelle angelegt
				'allow_maybe'=>false,//Vielleicht als Option erlauben
);
?>
