---------------------------
Update-Script für moziloCMS 1.10.x bzw. 1.11.x auf 1.12
---------------------------

Diese Anleitung bezieht sich auf ein Standard-moziloCMS; 
eigene Erweiterungen müssen u.U. per Hand nachgezogen werden.

Es werden folgende Verzeichnisse behandelt: 
- kategorien
- layouts 
- galerien


1.  Auf dem Webserver ein neues Verzeichnis erstellen 
    (z.B "neumozilo").

2.  moziloCMS 1.12 herunterladen, entpacken und den Inhalt nach 
    "neumozilo" übertragen.

3.  Im Verzeichnis "neumozilo" die Verzeichnisse "kategorien" 
    und "galerien" mit diesen Verzeichnissen aus der alten 
    moziloCMS-Installation ersetzen.

4.  Das Verzeichnis des bisher verwendeten Layouts in das 
    Verzeichnis "layouts" der neuen CMS-Installation kopieren.

5.  Aus dem alten moziloCMS folgende Dateien direkt ins 
    Verzeichnis "neumozilo/update" kopieren:
    - admin/conf/basic.conf
    - admin/conf/logindata.conf
    - conf/downloads.conf
    - conf/main.conf
    - conf/syntax.conf
    - formular/formular.conf

6.  Im Browser [neumozilo]/update/update.php aufrufen und den 
    Anweisungen folgen.

7.  Prüfen, ob das aktualisierte CMS sauber funktioniert.
    Ggfs. Meldungen in der Datei update/log.txt prüfen.

8.  Alte CMS-Installation löschen und durch die aktualisierte 
    ersetzen.

9.  Das Verzeichnis "update" im aktualisierten CMS löschen.

10. Wenn es zu Darstellungsfehlern im neuen moziloAdmin kommt:
    Den Browser-Cache leeren oder Admin mit Strg+F5 neu laden.
    

Tauchen während des Updates Probleme auf, steht das mozilo-
Supportforum unter http://forum.mozilo.de zur Verfügung.

Viel Spaß mit moziloCMS 1.12 :-)





---------------------------
Technische Details von moziloCMS 1.12
(für Fortgeschrittene)
---------------------------

- Aufbau eines Templates:

    Template-Name/
                css/
                grafiken/
                template.html
                gallerytemplate.html
                favicon.ico
                layoutsettings.conf

    Es sollten nur die Verzeichnisse und Dateien im Template-
    Verzeichnis enthalten sein, die zum Aufbau des Templates 
    gehören.
    Sonderzeichen in Datei- bzw. Verzeichnisnamen sind mithilfe 
    des Sonderzeichenkonverters (www.adresse.de/sonderzeichen/)
    in URL-kodierter Schreibweise anzugeben.

- Änderungen im CSS:

    Neu hinzugekommen in Version 1.12:
    
        /* -------------------------------------------------------- */
        /* [zentriert|...] */
        /* --------------- */
        .aligncenter {
            text-align:center;
        }

        /* -------------------------------------------------------- */
        /* [links|...] */
        /* ----------- */
        .alignleft {
            text-align:left;
        }

        /* -------------------------------------------------------- */
        /* [rechts|...] */
        /* ------------ */
        .alignright {
            text-align:right;
        }

        /* -------------------------------------------------------- */
        /* [block|...] */
        /* ----------- */
        .alignjustify {
            text-align:justify;
        }

        /* -------------------------------------------------------- */
        /* {TABLEOFCONTENTS} */
        /* ----------------- */
        div.tableofcontents ul ul {
            /*padding-left:15px;*/
        }
        div.tableofcontents li.blind {
            list-style-type:none;
            list-style-image:none;
        }

        fieldset#searchfieldset {
           border:none;
           margin:0px;
           padding:0px;
        }


    Neu hinzugekommen in Version 1.11:
    
        /* -------------------------------------------------------- */
        /* Kontaktformular */
        /* --------------- */
        form#contact_form {
        }
        table#contact_table {
        }
        table#contact_table td {
            vertical-align:top;
            padding:5px;
        }
        span#contact_errormessage{
            color:#880000;
            font-weight:bold;
        }
        span#contact_successmessage{
            color:#008800;
            font-weight:bold;
        }
        input#contact_name, input#contact_mail, input#contact_website {
            width:200px;
        }
        textarea#contact_message {
            width:200px;
        }
        input#contact_submit {
            width:200px;
        }


    CSS-Änderungen 1.11 -> 1.12:

        div.imagesubtitle       nach    span.imagesubtitle
        div.leftcontentimage    nach    span.leftcontentimage
        div.rightcontentimage   nach    span.rightcontentimage
        em.deadlink             nach    span.deadlink
        em.highlight            nach    span.highlight
        b                       nach    b.contentbold 
        i                       nach    i.contentitalic 
        u                       nach    u.contentunderlined 
        s                       nach    s.contentstrikethrough 


    ACHTUNG BEI UPDATE VON SEHR ALTEN VERSIONEN:
    Einige CSS-Elemente müssen u.U. manuell editiert werden.

        Im Stylesheet suchen: [bild|...], [bildlinks|...] und [bildrechts|...]
        ...und ersetzen durch:

            /* -------------------------------------------------------- */
            /* [bild|...] */
            /* ---------- */
            img {
                border:none;
            }

            span.imagesubtitle {
                margin:3px 3px;
                text-align:justify;
                font-size:87%;
            }

            /* -------------------------------------------------------- */
            /* [bildlinks|...] */
            /* --------------- */
            span.leftcontentimage {
                margin:6px 20px 6px 0px;
                float:left;
            }

            img.leftcontentimage {
            }

            /* -------------------------------------------------------- */
            /* [bildrechts|...] */
            /* ---------------- */
            span.rightcontentimage {
                margin:6px 0px 6px 20px;
                float:right;
            }

            img.rightcontentimage {
            }

            
        In der CSS-Datei anpassen:

            statt em.bold:
                b.contentbold {
				}

            statt em.italic:
				i.contentitalic {
				}

            statt em.underlined:
                u.contentunderlined {
                }

            statt em.crossed:
                s.contentstrikethrough {
                }


    ACHTUNG BEI VERWENDUNG VON GALERIEN:
    Die Galerie ist jetzt ein Plugin; folgendes ist zu beachten:

		Das Erscheinungsbild der Galerien kann in der Plugin-
		Konfiguration beeinflusst werden.
        Dabei zu beachten: Leerzeichen werden mit &nbsp; und 
		Zeilenumbrüche mit <br> ersetzt.

		Galerien lassen sich nun direkt in Inhaltsseiten 
		einbetten. Dazu einfach in der Inhaltsseite notieren:
        {Galerie|Name der Galerie}
		
		Galerien können aber trotzdem noch "Standalone" 
		betrieben werden; dazu gibt es wie gehabt die Datei
		gallerytemplate.html. Diese enthält aber nicht mehr die
		einzelnen Platzhalter (die sind ja in die Plugin-
		Konfiguration ausgelagert), sondern nur noch den 
		Platzhalter {Galerie}.

        Beispielhafter Auszug aus einer gallerytemplate.html...
		- ...vor 1.12:

            <body>
                <div class="gallerycontent">
                    <h2>
                      {CURRENTGALLERY}
                    </h2>
                    <div class="gallerymenu">
                      {GALLERYMENU}
                    </div>

                    <div class="gallerynumbermenu">
                      {NUMBERMENU}
                    </div>
                    <div style="text-align:center;">
                      {CURRENTPIC}<br />
                      <br />
                      {CURRENTDESCRIPTION}<br />
                      <br />

                      {XOUTOFY}
                    </div>
                </div>
            </body>

		- ...seit 1.12:
		
		    <body>
                <div class="gallerycontent">
                    {Galerie}
                </div>
            </body>