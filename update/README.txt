Update-Scripte für moziloCMS ??? auf 1.12

Diese Anleitung bezieht sich nur auf ein Standard-moziloCMS; 
eigene Erweiterungen müssen u.U. per Hand nachgezogen werden.

1.  auf dem Webserver ein neues Verzeichnis erstellen 
    (z.B "neumozilo").

2.  moziloCMS 1.12 herunterladen, entpacken und den Inhalt nach 
    "neumozilo" übertragen.

3.  im Verzeichnis "neumozilo" die Verzeichnisse "kategorien" 
    und "galerien" mit diesen Verzeichnissen aus der alten 
    moziloCMS-Installation ersetzen.

4.  das Verzeichnis des bisher verwendeten Layouts in das 
    Verzeichnis "layouts" der neuen CMS-Installation kopieren.

5.  aus dem alten moziloCMS folgende Dateien direkt ins 
    Verzeichnis "neumozilo/update" kopieren:
    - admin/conf/basic.conf
    - admin/conf/logindata.conf
    - conf/downloads.conf
    - conf/main.conf
    - conf/syntax.conf
    - formular/formular.conf

6.  im Browser [neumozilo]/update/update.php aufrufen und den 
    Anweisungen folgen.

7.  prüfen, ob die neue Installation sauber funktioniert

8.  alte Installation löschen und durch die neue ersetzen

9.  das Verzeichnis "update" in der neuen Installation löschen

10. viel Spass mit moziloCMS 1.12 :-)