$(document).on('formReady', function( evt, form_identifier ) {
	try {
		if (
			! $( '[data-assistant]', '#'+form_identifier ).length ||
			typeof cff_ai_assistant_loaded != 'undefined'
		) return;
		cff_ai_assistant_loaded = true;
		let seq			= $('[name="cp_calculatedfieldsf_pform_psequence"]', '#'+form_identifier ).val();
		let bubble 		= $('<div id="cff_ai_assistant_bubble"></div>');
		let close		= $('<div id="cff_ai_assistant_bubble_close">&times;</div>');
		let suggestionText = $('<div id="cff_ai_assistant_suggestion_text"></div>');
		let useButton 	= $('<button id="cff_ai_assistant_use_suggestion" aria-label="Use suggestion"></button>');

		let button_text = $.fbuilder.forms[seq]?.settings?.messages?.ai_assistant_button ?? 'Apply suggestion';
		let generating_text = $.fbuilder.forms[seq]?.settings?.messages?.ai_assistant_generating ?? 'Apply suggestion';

		useButton.text( button_text );

		bubble.append(close).append( suggestionText ).append( useButton );
		bubble.appendTo('body');

		let typingTimer;
		let target;
		let suggestionsList = {};


		const doneTypingInterval = 2000; // 2 inactive seconds

		function attachListeners() {
			function triggerGenerateSuggestion() {
				if (target) generateSuggestion( target.val() );
			}
			$(document).on( 'input', '[data-assistant]', function() {
				target = $(this);
				clearTimeout(typingTimer);
				typingTimer = setTimeout(
					function() { triggerGenerateSuggestion(); },
					doneTypingInterval
				);
			});

			$(document).on( 'blur', '[data-assistant]', function() {
				target = $(this);
				clearTimeout(typingTimer);
				triggerGenerateSuggestion();
			});

			close.on( 'click', function(){
				target = null;
				bubble.hide();
			});

			useButton.on( 'click', function() {
			  if(target) target.val(suggestionText.text());
			  bubble.hide();
			});

			$(document).on( 'focus', ':input:not(#cff_ai_assistant_use_suggestion)', function() {
				target = null;
				bubble.hide();
			});

			let e = $(':focus');
			if ( e.attr('data-assistant') != undefined ) {
				target = e;
				triggerGenerateSuggestion();
			}
		};

        let activeModel = null;
		let session = null;

		async function generateSuggestion(text) {
			if (!text || text.trim().length < 5) {
				bubble.hide();
				return;
			}

			function showSuggestion(newText, noBtn) {
				noBtn = noBtn || false;
				suggestionText.text(newText);
				useButton.css('display', noBtn ? 'none' : 'block');
				bubble.show();
			};

			if ( text in suggestionsList ) {
				showSuggestion(suggestionsList[text]);
				return;
			}

			showSuggestion(generating_text, true);

			try {
				const prompt = `Please improve the following text to make it clearer and more appealing. Respond only with the improved version of the original text, with no commentary, quotes, or headers:\n"${text}"`;

                let suggestion = await makeInference(prompt);

				if (suggestion) {
					suggestion = suggestion.replace(/^"+/, '').replace(/"+$/, '');
					suggestionsList[text] = suggestion;
					suggestionsList[suggestion] = suggestion;
					showSuggestion(suggestion);
				}
			} catch(err) {
				bubble.hide();
				console.error(err);
			}
		};

		async function initModel() {

			/******************* Gemini Nano ***************/
			if ( 'LanguageModel' in window) {
				let languages = ['en'];
				const languageCode = navigator.language.slice(0, 2).toLowerCase();
                if ( !languages.includes(languageCode) ) { languages.push(languageCode); }
                session = await LanguageModel.create({
                    expectedInputs:[{type: "text", languages: languages}],
                    expectedOutputs: [{ type: "text", languages: languages }],
                    initialPrompts: [
                        {role: "system", content: "You are a helpful assistant that improves the clarity and appeal of text."}
                    ],
                    monitor: function(m) {
                        m.addEventListener('downloadprogress', (e) => {
                            console.log(e.loaded, e.total);
                        });
                    },
                });
                activeModel = 'native';
                attachListeners();

                return; // The browser's API is present, I don't require WebLLM.
			}

			/******************* WEBLLM ********************/
			const module = await import("https://esm.run/@mlc-ai/web-llm");
			const CreateMLCEngine = module.CreateMLCEngine;
			session = await CreateMLCEngine( "Llama-3.2-1B-Instruct-q4f16_1-MLC", {
				useCache: false,
				initProgressCallback: function(p) {
					console.log(p);
				}
			});
            activeModel = 'webllm';
			attachListeners();
		};

		async function makeInference( prompt) {
            if (activeModel === 'native') {
                const response = await session.prompt([
                    { role: "user", content: prompt }
                ]);
                return response.trim();
            } else if (activeModel === 'webllm') {
                await session.resetChat();
                const response = await session.chat.completions.create({
                    messages: [
                        { role: "user", content: prompt }
                    ],
                    temperature: 0.9,
                    max_tokens: 256
                });

                return response.choices[0].message.content.trim();
            }
		}

        function handleFirstInteraction() {
            document.removeEventListener("click", handleFirstInteraction);
            document.removeEventListener("keydown", handleFirstInteraction);
            document.removeEventListener("touchstart", handleFirstInteraction);
            initModel();
        }

        // Listen for first interaction
        document.addEventListener("click", handleFirstInteraction);
        document.addEventListener("keydown", handleFirstInteraction);
        document.addEventListener("touchstart", handleFirstInteraction);
	} catch ( err ) { console.log(err); }
});