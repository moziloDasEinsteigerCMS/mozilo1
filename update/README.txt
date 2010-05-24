Update-Script für moziloCMS 1.10.x bzw. 1.11.x auf 1.12

Diese Anleitung bezieht sich nur auf ein Standard-moziloCMS; 
eigene Erweiterungen müssen u.U. per Hand nachgezogen werden.

Es werden folgende Verzeichnisse behandelt: kategorien, layouts 
und galerien

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

7.  prüfen, ob die neue Installation sauber funktionierte siehe log.txt

8.  alte Installation löschen und durch die neue ersetzen

9.  das Verzeichnis "update" in der neuen Installation löschen

10. Wenn der Neue amin Zerschossen aussieht einfach nur den Browser-Cache lehren

11. viel Spass mit moziloCMS 1.12 :-)



---------------------------
Was zu beachten ist:
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


    ACHTUNG !!!!!!!!!!!!!!!!!!!!!!!!!!!!
        Die Galerie ist ein Plugin geworden deshalb müssen die volgenden sachen von hand umgestelt werden

        Das erscheinungs bild wird im Galerie Plugin configuriert

        für embedete Galerie einfach in der Inhaltsseite {Galerie|MEINE GALERIE} einsetzten
        und im admin Plugins Standart Galerie die entsprechenden Platzhalter anordnen HTML code ist
        erlaubt.
        ACHTUNG hier ist zu beachten das alle Lehr und Zeilen umbrüche ersetztwerden mit &nbsp; und <br>
        deshalb überall wo das nicht erwünscht ist einfach nicht machen :)


        für nicht embedete Galerie in der gallerytemplate.html ein {Galerie} an die Passende stelle setzen
        und alle Platzhalter {CURRENTGALLERY} {GALLERYMENU} {NUMBERMENU} {CURRENTPIC} {CURRENTDESCRIPTION}
        {XOUTOFY} {CURRENT_INDEX} {PREVIOUS_INDEX} {NEXT_INDEX} entfernen und im Plugin configurieren

        z.B. Auszug aus einer gallerytemplate.html

            <body>
              <div id="container">
                <div id="header">
                  <h1>
                    <a href="index.php" title="Zur Startseite">{WEBSITE_TITLE}</a>
                  </h1>
                </div>
                <div id="sub_header">
                          {CURRENTGALLERY}
                </div>
                <div id="main_content_top"></div>
                <div id="main_content">
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
                  <div id="clear"></div>
                </div>
                <div id="main_content_bottom"></div>
                <div id="footer">
                  <b>Design by</b> <a href="http://www.pikanai.com">Pikanai.com</a>
                </div>
              </div>
            </body>

        Da holen wir uns den Relewanten Teil raus und ersetzen in mit {Galerie}

            <div id="sub_header">
                      {CURRENTGALLERY}
            </div>
            <div id="main_content_top"></div>
            <div id="main_content">
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
              <div id="clear"></div>
            </div>

        und setzen in im admin Plugin Standart Galerie im Textfeld ein

            <div id="sub_header">{CURRENTGALLERY}</div><div id="main_content_top"></div>
            <div id="main_content"><div class="gallerycontent"><h2>{CURRENTGALLERY}</h2>
            <div class="gallerymenu">{GALLERYMENU}</div><div class="gallerynumbermenu">{NUMBERMENU}</div>
            <div style="text-align:center;">
            {CURRENTPIC}
            {CURRENTDESCRIPTION}
            {XOUTOFY}</div></div><div id="clear"></div></div>

        ACHTUNG hier ist zu beachten das alle Lehr und Zeilen umbrüche ersetztwerden mit &nbsp; und <br>
        deshalb überall wo das nicht erwünscht ist einfach nicht machen :)

