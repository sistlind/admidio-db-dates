# admidio-db-dates
Wordpress Plugin um Admidio Termin in Wordpress zu präsentieren

## Beschreibung
Mit diesem Plugin ist es möglich direkt auf die Datenbank einer parallel im Webspace liegenden Admidio Installation 
zuzugreifen und die Termine anzuzeigen. Außerdem ist eine einfachs An/Abmelde Möglichkeit("dirtydates") von Terminen gegeben, die in einer extra Datentabelle abgelegt wird.

## Standalone
Die Standalone Version kann auch ohne wordpress genutzt werden, die Konfiguration läuft wie in Wordpress, danach einfach die Datei direkt aufrufen.


## Installation
Nach ./wp-conten/plugins/admidio-db-dates kopieren, die Config Datei anpassen und im Admin-Interface Plugin aktivieren.

## Konfiguration
In der Config Datei muss der relative Pfad zu Admidio-Installation angegeben werden(weil von dort die Datenbank-Parameter geladen werden)
Außerdem muss eine Rolle angegeben werden, die für die Terminzu/absagen dargestellt wird. Bei mehreren Organisationen kann auch noch 
die Organisation angegeben werden(die Nummer, also 1,2,3).
"dates-after" und dates-before wird nur für dirtydates genutzt.

## Einbinden in Seite
Einfach eine neue leere Seite erstellen und die Shortcodes
[admidio-dates] für die Terminanzeig
und [admidio-dirtydates] für die zu/absagen werden.
Für letzteres sollte eine passwort-geschützte Seite verwendet werden!
