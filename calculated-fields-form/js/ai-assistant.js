import * as webllm from "https://esm.run/@mlc-ai/web-llm";

/*************** WebLLM logic ***************/
let variables = "";
let topic = 'js';

const context = `You are a code generator for JavaScript, CSS, and HTML. Always output your answer as the code block. No pre-amble. Do not respond to unrelated question.`;
const messages = [{
		content: context,
		role: "system",
	},
];

let selectedModel;
selectedModel = "Qwen2.5-Coder-3B-Instruct-q4f32_1-MLC";

let loadedModel   = false,
    loadingModel  = false;

// UI components
let aiDlgCrl 			= document.getElementById("cff-ai-assistant-container");
let aiAssistantLoadingMss = document.getElementById('cff-ai-assistant-loading-message');
let statusCrl 			= document.getElementById("cff-ai-assistant-status");
let progressBarCrl 		= document.getElementById("cff-ai-assistant-progress-bar");
let userQuestionCtrl 	= document.getElementById("cff-ai-assistant-question");
let chatBoxCtrl 		= document.getElementById("cff-ai-assistant-answer-row");
let chatStatsCtrl 		= document.getElementById("cff-ai-assistant-stats");
let sendBtnCtrl 		= document.getElementById("cff-ai-assistan-send-btn");
let unmountBtnCtrl 		= document.getElementById("cff-ai-assistant-unmount");
let closeBtnCtrl 		= document.getElementById("cff-ai-assistant-close");
let settingsBtnCtrl 	= document.getElementById("cff-ai-assistant-settings");
let closeSettingsBtnCtrl = document.getElementById("cff-ai-assistant-settings-close");
let providerCtrl        = document.getElementById("cff-ai-assistant-provider");
let modelCtrl           = document.getElementById("cff-ai-assistant-model");
let apiKeyCtrl          = document.getElementById("cff-ai-assistant-api-key");
let saveSettingsBtnCtrl = document.getElementById("cff-ai-assistant-save-settings-btn");

// Check if the selected provider is local
function isLocalModel() {
    return ( window['cff_ai_provider'] === 'local' );
}

// Resize button.
function btnHeight() {
	sendBtnCtrl.style.height = userQuestionCtrl.offsetHeight + 'px';
}

const resizeObserver = new ResizeObserver(entries => {
  btnHeight();
});

resizeObserver.observe(userQuestionCtrl);

// Callback function for initializing progress
function updateEngineInitProgressCallback(report) {
    console.log("initialize", report.progress);
    statusCrl.textContent = report.progress === 1 ? cff_ai_texts['ready'] + ' 100%' : report.text;
	if (report.progress !== undefined)  progressBarCrl.style.width = `${report.progress * 100}%`;
}

function setPlaceholder() {
	userQuestionCtrl.setAttribute(
		"placeholder",
		(
			'cff_ai_texts' in window ?
			(
				'placeholder_' + topic in window['cff_ai_texts'] ?
				window['cff_ai_texts']['placeholder_' + topic] :
				window['cff_ai_texts']['placeholder']
			) :
			'Please, enter your question ...'
		)
	);
}

// Create engine instance
let engine = new webllm.MLCEngine();
engine.setInitProgressCallback(updateEngineInitProgressCallback);

// ---------- Robust error handling helpers ----------
async function unloadModel() {
    try {
        await engine.unload();
        // Also clear WebLLM caches
        const cacheNames = await caches.keys();
        for (const name of cacheNames) {
            if (/^webllm\//i.test(name)) {
                await caches.delete(name);
            }
        }
    } catch (e) {
        console.warn('Error during unload:', e);
    } finally {
        loadedModel = false;
    }
}

function showLocalModelError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'cff-ai-assistance-error';
    errorDiv.innerHTML = `
        <p>${message}</p>
        <button type="button" class="button-secondary" onclick="cff_ai_assistant_settings(true);">${window['cff_ai_texts']['switch_btn']}</button>
    `;
    chatBoxCtrl.appendChild(errorDiv);
    chatBoxCtrl.scrollTop = chatBoxCtrl.scrollHeight;

    statusCrl.textContent = 'Error: ' + message;
    sendBtnCtrl.disabled = true;
    userQuestionCtrl.disabled = true;
    unmountBtnCtrl.style.display = 'none';
}

function isFatalError(error) {
    const msg = error?.message || '';
    const fatalPatterns = [
        /device lost/i,
        /context lost/i,
        /out of memory/i,
        /memory exhausted/i,
        /alloc failed/i,
        /shader compilation/i,
        /compile error/i,
        /internal error/i,
        /assertion failed/i,
        /unexpected state/i,
        /GPU/i,                  // any GPU-related crash
    ];
    return fatalPatterns.some(pattern => pattern.test(msg));
}

async function handleEngineError(error, context = 'inference') {
    console.error(`Fatal error during ${context}:`, error);

    if (isFatalError(error) || context === 'loading') {
        await unloadModel();
        showLocalModelError(window['cff_ai_texts']['fatal_error']);
    } else {
        // Non-fatal, maybe just reset chat
        try { await engine.resetChat(); } catch (e) {}
        // Display a generic error message (not as severe)
        const errorDiv = document.createElement('div');
        errorDiv.className = 'cff-ai-assistance-error';
        errorDiv.textContent = window['cff_ai_texts']['try_again_error'];
        chatBoxCtrl.appendChild(errorDiv);
        chatBoxCtrl.scrollTop = chatBoxCtrl.scrollHeight;
    }
}

// ---------- Core engine initialization ----------
async function initializeWebLLMEngine() {
    statusCrl.style.display = 'block';

    if (typeof navigator == 'undefined' || !navigator.gpu) {
		document.getElementById('cff-ai-gpu-error').style.display = 'block';
		document.getElementById('cff-ai-gpu-error').parentElement.classList.add('cff-ai-assistance-error-message');
		sendBtnCtrl.disabled = true;
		userQuestionCtrl.disabled = true;
		return false;
	}

	if ( typeof window == 'undefined' || ! window.caches ) {
		document.getElementById('cff-ai-caches-error').style.display = 'block';
		document.getElementById('cff-ai-caches-error').parentElement.classList.add('cff-ai-assistance-error-message');
		sendBtnCtrl.disabled = true;
		userQuestionCtrl.disabled = true;
		return false;
	}
    if ( loadedModel ) return true;
    try {
        loadingModel = true;
        const config = {
            temperature: 0.0,
            top_p: 1,
            context_window_size: -1,
            sliding_window_size: 2048,
            attention_sink_size: 1024,
        };
        try { await engine.unload(); } catch (err) {}
        await engine.reload(selectedModel, config);
        await engine.resetChat();
        loadedModel = true;
        loadingModel = false;
        return true;
    } catch( error ) {
        loadingModel = false;
        loadedModel = false;
        await handleEngineError(error, 'loading');
        return false;
    }
}

// ---------- Streaming generation ----------
async function streamingGenerating(messages, onUpdate, onFinish, onError) {
    try {
        let curMessage = "";
		let usageMessage = "";
		let finalChunk;

        const completion = await engine.chat.completions.create({
            stream: true,
            messages,
			stream_options: { include_usage: true }
        });

        for await(const chunk of completion) {
			try {
				const curDelta = chunk.choices[0].delta.content;
				if (curDelta) {
					curMessage += curDelta;
				}
				onUpdate(curMessage);
			} catch (err) {
                console.warn('Error processing chunk:', err);
            }
			finalChunk = chunk;
        }

		if (finalChunk && finalChunk.usage) {
			if ( 'extra' in finalChunk.usage ) {
				let message_components = [];
				if ('prefill_tokens_per_s' in finalChunk.usage['extra']) {
					message_components.push( 'prefill: '+finalChunk.usage['extra']['prefill_tokens_per_s'].toFixed(4)+' tk/s');
				}

				if ('time_per_output_token_s' in finalChunk.usage['extra']) {
					message_components.push( 'decoding: '+finalChunk.usage['extra']['time_per_output_token_s'].toFixed(4)+' tk/s');
				}
				usageMessage = message_components.join(', ');
			}
		}
        const finalMessage = await engine.getMessage();
        onFinish(finalMessage, usageMessage);
    } catch (err) {
        onError(err);
    }
}

// ---------- Model loading orchestration ----------
function loadingEngine() {
    if (window['cff_ai_provider'] === '') {
        window['cff_ai_provider'] = 'local';
        openAIAssistantSettings();
    } else if (isLocalModel()) {
        aiAssistantLoadingMss.style.display = (loadingModel || !loadedModel)  ? 'block': 'none';
        if (!loadingModel) {
            initializeWebLLMEngine().then(success => {
                if (success) {
                    sendBtnCtrl.disabled = false;
                    unmountBtnCtrl.style.display = 'inline-block';
                    aiAssistantLoadingMss.style.display = 'none';
                }
            });
        } else if (loadedModel) {
            unmountBtnCtrl.style.display = 'inline-block';
            sendBtnCtrl.disabled = false;
        }
    } else {
        unmountBtnCtrl.style.display = 'none';
        sendBtnCtrl.disabled = false;
    }
}

/*************** UI logic ***************/
async function onMessageSend() {

	const input = userQuestionCtrl.value.trim();
    if (input.length === 0) {
        return;
    }

	let message = input;
    appendMessage({content: message, role: "user"});

    const aiMessage = {
        content: ( 'cff_ai_texts' in window ? window['cff_ai_texts']['typing'] : "typing..." ),
        role: "assistant",
    };
    appendMessage(aiMessage);

    userQuestionCtrl.value = "";
    userQuestionCtrl.setAttribute("placeholder", ( 'cff_ai_texts' in window ? window['cff_ai_texts']['generating'] : 'Generating...' ) );
    sendBtnCtrl.disabled = true;

	switch(topic) {
        case 'list':
            message = "Only output a list of items wrapped between triple backticks (```), nothing else. Each item must be wrapped in <li></li> tags. If you need to specify a different value and label, use the format 'value|label' as the content of the <li>. If only one item is provided, it will be used as both the value and label. For example:\n- For a simple list of colors:\n```\n<li>Red</li>\n<li>Blue</li>\n<li>Yellow</li>\n```\n- For a list of US states with abbreviations:\n```\n<li>AL|Alabama</li>\n<li>AK|Alaska</li>\n<li>AZ|Arizona</li>\n```\n\nList request: " + input;
        break;
		case 'css':
			message = "Only output CSS code wrapped between triple backticks (```), nothing else. Follow this structure exactly. Use class selectors as descendants of the #fbuilder ID selector. Always include !important for every rule.\nExample:\n```css\n#fbuilder input[type=\"text\"] {\nfont-weight: 700 !important;\nbackground-color: green !important;\ncolor: white !important;\n}```\n\n"+
			"Styles request: " + input;
		break;
		case 'html':
			message = "Create an block of HTML tags, including style attributes when required. To display the fields values within the tags, use the data-cff-field attribute in the corresponding text. Enclose the code between ``` symbols." + ( "" != variables ? " You have access to the fields:\n" + variables + "Use these fields in code when appropriate. Example: ```html\n<div>User name: <span data-cff-field=\"fieldname1\"></span></div><br><div>Email: <span data-cff-field=\"fieldname2\"></span></div><br><div>Message: <p data-cff-field=\"fieldname3\"></p></div>```" : "" ) + "\nDescription: " + input;
		break;
		default:
            message = "Create an immediately invoked JavaScript function expressions (IIFE) that run automatically. It must start with (function(){ and enter with })(). It must include a return statement with the result as scalar value. Use only valid JavaScript syntax. Test your code mentally for syntax errors before submitting. Do not include any non-JavaScript text or characters. Keep the code simple and focused on the calculation. Enclose the code between ``` symbols. DO NOT include commentS into the function code." + ("" != variables ? " \n\n CRITICAL INSTRUCTION: The following variables ALREADY EXIST in the system and contain values. DO NOT DEFINE, INITIALIZE, OR ASIGN ANY VALUE TO THEM IN YOUR CODE:\n" + variables : "") + "\n\nFunction description: " + input.replace(/equation/ig, 'function');
        break;
	}

    if ( isLocalModel() ) {
        await engine.resetChat();
        messages.splice(1);
        messages.push({content: message, role: "user"});

        const onFinishGenerating = (finalMessage, usageMessage) => {
            updateLastMessage(finalMessage);
            sendBtnCtrl.disabled = false;
            setPlaceholder();
            chatStatsCtrl.textContent = usageMessage;
        };

        streamingGenerating(
            messages,
            updateLastMessage,
            onFinishGenerating,
            async function (err) {
                // Error during generation
                console.error('Inference error: ', err);
                sendBtnCtrl.disabled = false;
                userQuestionCtrl.value = input;  // restore input
                setPlaceholder();

                await handleEngineError(err, 'inference');
            }
        );
    } else {
        const data = new FormData();
        data.append('_cpcff_ai_assistant_action', 'cff_ai_assistant_get_response');
        data.append('_cpcff_ai_assistant_context', context);
        data.append('_cpcff_ai_assistant_message', message);
        data.append('_cpcff_ai_assistant_nonce', cff_ai_request_nonce);
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: data,
            });

            if (!response.ok) {
                alert(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (result.error) {
                alert(result.error);
                throw new Error(result.error);
            } else {
                if (result.warning) {
                    alert(result.warning);
                }
                updateLastMessage(result.response);
            }
            setPlaceholder();
        } catch (err) {
            console.error(err);
            userQuestionCtrl.value = input;
        } finally {
            sendBtnCtrl.disabled = false;
        }
    }
}

function appendMessage(message) {

	const newMessage = document.createElement("div");
    newMessage.classList.add("cff-ai-assistance-message");

    if (message.role === "user") {
        newMessage.classList.add("cff-ai-assistance-user-message");
    } else {
		newMessage.classList.add("cff-ai-assistance-bot-message");
    }
	newMessage.textContent = message.content;

    chatBoxCtrl.appendChild(newMessage);
    chatBoxCtrl.scrollTop = chatBoxCtrl.scrollHeight; // Scroll to the latest message
}

function updateLastMessage(content) {
	function escapeHTML(str) {
		return str
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;");
	}

	function formatMessage(message) {
		message = message.replace(/fieldname(\d+)/ig, 'fieldname$1');
		// Split message into parts: text and code blocks
		const parts = [];
		let lastIndex = 0;
		const regex = /```(?:\w+)?\n([\s\S]*?)```/g;
		let match;

		while ((match = regex.exec(message)) !== null) {
            let btn = '';
            const before = message.slice(lastIndex, match.index);
			const code = match[1];

			// Escape and add the text before the code block
			parts.push(`<p>${escapeHTML(before)}</p>`);

			// Add the raw code block
            let escapedCode = escapeHTML(code);
            let topic_identifier = '<span>'+( topic ?? '' )+'</span>';
            if (topic && topic === 'list') {
                const replace_text = window?.cff_ai_texts?.use_list_btn ?? 'Use list';
                btn = `<button type="button" id="cff-ai-assistant-use-list-btn" class="button-secondary" onclick="cff_ai_assistant_use_list(this);">${replace_text}</button>`;
                escapedCode = '<ul>'+escapedCode.replace(/&lt;li&gt;/ig, '<li>').replace(/&lt;\/li&gt;/ig, '</li>').replace(/[\r\n]/g,'')+'</ul>';
            } else {
                const copy_text = window?.cff_ai_texts?.copy_btn ?? 'Copy';
                btn = `<button type="button" id="cff-ai-assistant-copy-btn" class="button-secondary" onclick="cff_ai_assistant_copy(this);">${copy_text}</button>`;
            }
			parts.push(
				`${topic_identifier}<pre><div style="text-align:right;margin-bottom:10px;">${btn}</div><code>${escapedCode}</code></pre>`
			);

			lastIndex = regex.lastIndex;
		}

		// Add any remaining text after the last code block
		const remaining = message.slice(lastIndex);
		if (remaining.trim()) {
			parts.push(`<p>${escapeHTML(remaining)}</p>`);
		}

        let output = parts.join("");

        // Patch for specific case: replace new Date( with DATEOBJ(, this ensure using the plugin operation.
        output = output.replace(/new Date\(/g, 'DATEOBJ(');
		return output;
	}

    const messageDoms = chatBoxCtrl.querySelectorAll(".cff-ai-assistance-message");
    const lastMessageDom = messageDoms[messageDoms.length - 1];
    lastMessageDom.innerHTML = formatMessage(content);
}

window['cff_ai_assistant_copy'] = function ( btn ) {
	const codeElement = btn.parentElement.parentElement.querySelector('code');
	const copy_text = ( 'cff_ai_texts' in window ? window['cff_ai_texts']['copy_btn'] : 'Copy' );
	const copied_text = ( 'cff_ai_texts' in window ? window['cff_ai_texts']['copied_btn'] : 'Copied !!!' );
	if (codeElement) {
		const text = codeElement.textContent;
		navigator.clipboard.writeText(text).then(() => {
			btn.textContent = copied_text;
		}).catch(err => {
			console.error('Failed to copy code: ', err);
		});
	}
	setTimeout(function(){ btn.textContent = copy_text; }, 3000);
};

window['cff_ai_assistant_use_list'] = function ( btn ) {
    try {
        const items = btn.parentElement.parentElement.querySelectorAll('li');
        const field_type_error = window?.cff_ai_texts?.field_type_error ?? 'Select a Radio Button, Checkbox, or Dropdown field before applying the list.';
        if (items) {
            let values = [],
                texts  = [];

            items.forEach(item => {
                const t = item.textContent.trim();
                if (t) {
                    const p = t.split('|');
                    values.push(p[0]);
                    if(1 < p.length) {
                        texts.push(p[1]);
                    } else {
                        texts.push(p[0]);
                    }
                }
            });

            // Get the field
            let field_tag = document.querySelector('.fields.ui-selected');
            if (field_tag) {
                let field_name = Array.from(field_tag.classList).find(x => x.startsWith('fieldname'));
                if (field_name) {
                    let field_index = window.cff_form.fBuild.getFieldsIndex()[field_name];
                    if( field_index == undefined ) throw field_type_error;
                    let field = window.cff_form.fBuild.getItems()[field_index];
                    if (!field || ['fdropdown', 'fradio', 'fcheck'].indexOf(field.ftype) == -1) throw field_type_error;
                    field.choices = [];
                    field.choicesVal = [];
                    if ('optgroup' in field) {
                        field.optgroup = [];
                    }
                    let deps = [];
                    for (let i = 0; i < values.length; i++) {
                        field.choices.push(texts[i]);
                        field.choicesVal.push(values[i]);
                        deps.push(field?.choicesDep?.[i] ?? []);
                    }
                    field.choicesDep = deps;
                    window?.fbuilderjQuery?.fbuilder?.editItem(field_index);
                    window?.fbuilderjQuery?.fbuilder?.reloadItems({ 'field': field });
                    return;
                }
            }
        }
    } catch (err) { alert(err); }
};

window['cff_ai_assistant_open'] = function( answer_topic ){
    sendBtnCtrl.disabled = true;
    aiAssistantLoadingMss.style.display = 'none';
	variables = '';
	topic = answer_topic || 'js';

	setPlaceholder();
	// Get variables.
	window.cff_form.fBuild.getItems().forEach( (item) => {
		if (
			'ftype' in item &&
			['ftext', 'fcurrency', 'fnumber', 'fslider', 'fcolor', 'femail', 'fdate', 'ftextarea', 'fcheck', 'fradio', 'fdropdown', 'ffile', 'fpassword', 'fPhone', 'fhidden', 'frecordsetds', 'ftextds', 'femailds', 'ftextareads', 'fcheckds', 'fradiods', 'fPhoneds', 'fdropdownds', 'fhiddends', 'fnumberds', 'fcurrencyds', 'fdateds', 'fqrcode', 'fCalculated'].indexOf(item.ftype) != -1
		) {
			let l = ( 'title' in item ) ? String( item.title ).trim() : '';
			l = ( '' == l && 'shortlabel' in item ) ? String( item.shortlabel ).trim() : l;
			l = ( '' == l && 'userhelp' in item ) ? String(item.userhelp) : l;
			if( 'dformat' in item && item['dformat'] == 'percent') l += ' ( it is the decimal value, it is not required to divide the variable value by 100 )';
			variables += item.name + " (existing constant that represents " + l + ")\n";
		}
	} );
    loadingEngine();
	aiDlgCrl.style.display = 'flex';
	btnHeight();
};

window['cff_open_ai_assistant_settings'] = function(cloud) {
    openAIAssistantSettings(cloud);
};

/*************** UI binding ***************/
function isAIAssistantSettingsOpen() {
    return document.getElementById('cff-ai-assistant-settings-container').style.display != 'none';
}

function openAIAssistantSettings(cloud) {
    initializeAIAssistantSettings()
    document.getElementById('cff-ai-assistant-settings-container').style.display = 'block';
    if ( cloud ) {
        providerCtrl.selectedIndex = providerCtrl.options.length - 1;
        providerCtrl.dispatchEvent(new Event('change'));
    }
    sendBtnCtrl.disabled = true;
    userQuestionCtrl.disabled = true;
}

function closeAIAssistantSettings() {
    document.getElementById('cff-ai-assistant-settings-container').style.display = 'none';
    sendBtnCtrl.disabled = false;
    userQuestionCtrl.disabled = false;
    if (
        window['cff_ai_provider'] === 'local' &&
        providerCtrl.value === window['cff_ai_provider']
    ) {
        cff_ai_assistant_open(topic);
    }
}

function initializeAIAssistantSettings() {
    if ( 'cff_ai_api_key' in window ) {
        apiKeyCtrl.value = cff_ai_api_key;
    }
    if ( 'cff_ai_provider' in window ) {
        providerCtrl.value = cff_ai_provider;
        populateModelOptions( cff_ai_provider );
        providerCtrl.dispatchEvent(new Event('change'));
    }
}

function populateModelOptions(provider) {

    // Clear existing options
    modelCtrl.innerHTML = '';

    if ( ! (provider in cff_ai_models) ) {
        return;
    }

    const models = cff_ai_models[provider]['models'] || {};

    // Add new options
    for (let model in models) {
        if ( models[model]['ai-assistance'] == false ) continue;
        const option = document.createElement('option');
        option.value = model;
        option.textContent = models[model]['title'];
        modelCtrl.appendChild(option);
    }

    // Set selected model if available
    if ('cff_ai_model' in window && cff_ai_model in models) {
        modelCtrl.value = cff_ai_model;
    }

}
apiKeyCtrl.addEventListener('focus', (event) => {
    event.target.type = 'text';
});

apiKeyCtrl.addEventListener('blur', (event) => {
    event.target.type = 'password';
});

settingsBtnCtrl.addEventListener("click", async function () {
    if (isAIAssistantSettingsOpen()) {
        closeAIAssistantSettings();
    } else {
        openAIAssistantSettings();
    }
});

providerCtrl.addEventListener("change", async function () {
    const selectedProvider = providerCtrl.value;
    const modelContainer = document.getElementById('cff-ai-assistant-model-container');
    const apiKeyContainer = document.getElementById('cff-ai-assitance-api-key-container');
    populateModelOptions( selectedProvider );
    if (selectedProvider === 'local') {
        modelContainer.style.display = 'none';
        apiKeyContainer.style.display = 'none';
        apiKeyCtrl.removeAttribute('required');
        document.querySelector('.cff-ai-local-model-description').style.display = 'block';
    } else {
        document.querySelector('.cff-ai-local-model-description').style.display = 'none';
        // Update the link to the provider's API Key page.
        if (selectedProvider in cff_ai_models && 'api_key_url' in cff_ai_models[selectedProvider]) {
            let providerUrl = `<a href="${cff_ai_models[selectedProvider]['api_key_url']}" target="_blank">${cff_ai_models[selectedProvider]['title']}</a>`;
            document.querySelector('.cff-ai-assistant-provider-url').innerHTML = providerUrl;
        }
        modelContainer.style.display = 'block';
        apiKeyContainer.style.display = 'block';
        apiKeyCtrl.setAttribute('required', '');
    }
});

closeBtnCtrl.addEventListener("click", async function () {
	aiDlgCrl.style.display = 'none';
});

closeSettingsBtnCtrl.addEventListener("click", async function () {
    closeAIAssistantSettings();
});

unmountBtnCtrl.addEventListener("click", async function (evt) {
	const confirm_message = ( 'cff_ai_texts' in window ? window['cff_ai_texts']['unload'] : 'Would you like to proceed?' );
	if (loadedModel && window.confirm(confirm_message)) {
        await unloadModel();
	}
	evt.target.style.display = 'none';
	aiDlgCrl.style.display = 'none';
});

saveSettingsBtnCtrl.addEventListener("click", async function () {
    const selectedProvider = providerCtrl.value;
    const selectedModel = modelCtrl.value;
    const apiKey = apiKeyCtrl.value;
    if (selectedProvider !== 'local' && apiKey.trim() === '') {
        alert( ( 'cff_ai_texts' in window ? window['cff_ai_texts']['api_key_required'] : 'API Key is required for the selected provider.' ) );
        return;
    }

    this.setAttribute('disabled', 'disabled');
    const data = new FormData();
    data.append('_cpcff_ai_assistant_action', 'cff_ai_assistant_save_settings');
    data.append('_cpcff_ai_assistant_provider', selectedProvider);
    data.append('_cpcff_ai_assistant_model', selectedModel);
    data.append('_cpcff_ai_assistant_api_key', apiKey);
    data.append('_cpcff_ai_assistant_nonce', cff_ai_save_settings_nonce);

    await fetch(window.location.href, {
        method: 'POST',
        body: data,
    });

    cff_ai_provider = selectedProvider;
    cff_ai_model = selectedModel;
    cff_ai_api_key = apiKey;
    cff_ai_assistant_open(topic);
    this.removeAttribute('disabled');
    closeAIAssistantSettings();
});
sendBtnCtrl.addEventListener("click", function () {
    onMessageSend();
});