fbuilderjQuery = (typeof fbuilderjQuery != 'undefined' ) ? fbuilderjQuery : jQuery;
fbuilderjQuery[ 'fbuilder' ] = fbuilderjQuery[ 'fbuilder' ] || {};
fbuilderjQuery[ 'fbuilder' ][ 'modules' ] = fbuilderjQuery[ 'fbuilder' ][ 'modules' ] || {};

fbuilderjQuery[ 'fbuilder' ][ 'modules' ][ 'text' ] = {
	'tutorial' : 'https://cff.dwbooster.com/documentation#text-module',
	'toolbars'		: {
		'text' : {
			'label' : 'Text Operations',
			'buttons' : [
                {
                    "value" : "WORDSCOUNTER",
                    "code" : "WORDSCOUNTER(",
                    "tip" : "<p><strong>WORDSCOUNTER(text)</strong></p><p>Returns the number of words in text.<br><br> E.g. <strong>WORDSCOUNTER(fieldname123|r);</strong></p>"
                },
                {
                    "value" : "CHARSCOUNTER",
                    "code" : "CHARSCOUNTER(",
                    "tip" : "<p><strong>CHARSCOUNTER(text, ignore blank characters)</strong></p><p>Returns the number of characters in text. The second parameter allows ignoring blank characters in the text.<br><br> E.g. <strong>CHARSCOUNTER(fieldname123|r);</strong> or <strong>CHARSCOUNTER(fieldname123|r, true);</strong>.</p>"
                },
                {
                    "value" : "INTEXT",
                    "code" : "INTEXT(",
                    "tip" : "<p><strong>INTEXT(to search, text, case insensitive)</strong></p><p>Returns the number of times the word, character, phrase, or regular expression appears in the text. The search can be case-sensitive or case-insensitive (optional parameter, case-sensitive by default).<br><br> E.g. <strong>INTEXT(fieldname12|r, fieldname34|r);</strong> or <strong>INTEXT(fieldname12|r, fieldname34|r, true);</strong>.</p>"
                },
                {
                    "value" : "CHARAT",
                    "code" : "CHARAT(",
                    "tip" : "<p><strong>CHARAT(text, index)</strong></p><p>Returns the character located at the specified zero-based index position (Index zero by default). If no character exists at that index, it returns an empty string.<br><br> E.g. <strong>CHARAT(&quot;ABC&quot;);</strong> returns <strong>A</strong><br><strong>CHARAT(&quot;ABC&quot;, 1);</strong> returns <strong>B</strong>.</p>"
                },
                {
                    "value" : "CHARTOCODE",
                    "code" : "CHARTOCODE(",
                    "tip" : "<p><strong>CHARTOCODE(letter)</strong></p><p>Returns the Unicode value of the given letter. If multiple characters are provided, CHARTCODE returns the value of the first.<br><br> E.g. <strong>CHARTOCODE(&quot;A&quot;);</strong> returns <strong>65</strong></p>"
                },
                {
                    "value" : "CODETOCHAR",
                    "code" : "CODETOCHAR(",
                    "tip" : "<p><strong>CODETOCHAR(number)</strong></p><p>Returns a string created from the specified sequence of UTF-16 code units.<br><br> E.g. <strong>CODETOCHAR(65);</strong> returns <strong>A</strong></p>"
                },
            ]
		}
	}
};