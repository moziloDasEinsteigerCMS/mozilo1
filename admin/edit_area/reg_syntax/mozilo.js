editAreaLoader.load_syntax["mozilo"] = {
    'DISPLAY_NAME' : 'moziloCMS'
    ,'COMMENT_SINGLE' : {}
    ,'COMMENT_MULTI' : {}
    ,'QUOTEMARKS' : {1: "'", 2: '"'}
    ,'KEYWORD_CASE_SENSITIVE' : false
    ,'KEYWORDS' : {
    }
    ,'OPERATORS' :['|']
    ,'DELIMITERS' :['[', ']', '{', '}']
    ,'REGEXPS' : {
        'msyntax' : {
            'search' : '(\\[)(' + moziloSyntax + ')(\\||=|\\])'
            ,'class' : 'msyntax'
            ,'modifiers' : 'g'
            ,'execute' : 'before'
        }
        ,'mgeschuetzt' : {
            'search' : '(\\^)(\\{|\\}|\\[|\\])()'
            ,'class' : 'mgeschuetzt'
            ,'modifiers' : 'g'
            ,'execute' : 'before'
        }
        ,'mplace' : {
            'search' : '(\\{)(' + moziloPlace + ')(\\})'
            ,'class' : 'mplace'
            ,'modifiers' : 'g'
            ,'execute' : 'before'
        }
    }
    ,'STYLES' : {
        'COMMENTS': ''
        ,'QUOTESMARKS': ''
        ,'KEYWORDS' : {
            }
        ,'OPERATORS' : 'color: #f00;'
        // Achtung bei kein Bold benutzen gibt probleme im Chrome
        ,'DELIMITERS' : 'color: #f00;'
        ,'REGEXPS' : {
            'msyntax': 'color: #00f;'
            ,'mpluginsdeactiv': 'color: #00f;text-decoration:line-through'
            ,'mgeschuetzt': 'color: #000;'
        }
    }
};

if(moziloPluginsActiv.length > 0) {
    editAreaLoader.load_syntax["mozilo"]['REGEXPS']['mpluginsactiv'] = {
        'search' : '(\\{)(' + moziloPluginsActiv + ')(\\||\\})'
        ,'class' : 'msyntax'
        ,'modifiers' : 'g'
        ,'execute' : 'before'
    };
};

if(moziloPluginsDeactiv.length > 0) {
    editAreaLoader.load_syntax["mozilo"]['REGEXPS']['mpluginsdeactiv'] = {
        'search' : '(\\{)(' + moziloPluginsDeactiv + ')(\\||\\})'
        ,'class' : 'mpluginsdeactiv'
        ,'modifiers' : 'g'
        ,'execute' : 'before'
    };
};

if(moziloUserSyntax.length > 0) {
    editAreaLoader.load_syntax["mozilo"]['REGEXPS']['musersyntax'] = {
        'search' : '(\\[)(' + moziloUserSyntax + ')(\\||=|\\])'
        ,'class' : 'msyntax'
        ,'modifiers' : 'g'
        ,'execute' : 'before'
    };
};

if(moziloSmileys.length > 0) {
    editAreaLoader.load_syntax["mozilo"]['REGEXPS']['msmileys'] = {
        'search' : '()(' + moziloSmileys + ')()'
        ,'class' : 'msyntax'
        ,'modifiers' : 'g'
        ,'execute' : 'before'
    };
};
