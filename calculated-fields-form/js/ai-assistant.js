/*************** WebLLM logic ***************/

// Lazy loader for the WebLLM module from esm.run CDN.
// Avoids fetching hundreds of MB from a third-party CDN on every admin page load;
// the module is only fetched the first time the user selects the "local" AI provider.
// ES modules dedupe by spec, so the cached _webllmModule is shared across all callers.
let _webllmModule = null;
async function loadWebLLM() {
    if (_webllmModule) return _webllmModule;
    _webllmModule = await import("https://esm.run/@mlc-ai/web-llm");
    return _webllmModule;
}
let variables = "";
let variables_tags = "";
let topic = 'js';
let extra = '';

const context = `You are a code generator for JavaScript, CSS, and HTML. Always output your answer as the code block. No pre-amble. Do not respond to unrelated question.`;
const messages = [{
		content: context,
		role: "system",
	},
];

let wordpress_ai = 'wordpress-ai';

let unsavedSettings = false;

// UI components
let aiDlgCrl 			= document.getElementById("cff-ai-assistant-container");
let aiAssistantLoadingMss = document.getElementById('cff-ai-assistant-loading-message');
let statusCtrl 			= document.getElementById("cff-ai-assistant-status");
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

function isWPModel() {
    return ( window['cff_ai_provider'] === wordpress_ai );
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
    function getLastVisibleByClass(className) {
        const elements = document.querySelectorAll(`.${className}`);

        for (let i = elements.length - 1; i >= 0; i--) {
            const el = elements[i];

            // Fast visibility check
            if (el.offsetParent !== null) {
                return el;
            }
        }
        return null;
    }
    let progressBarCtrl = getLastVisibleByClass("cff-ai-assistant-progress-bar");
    let statusCtrl = getLastVisibleByClass("cff-ai-assistant-status");

    console.log("initialize", report.progress);
    if (statusCtrl) {
        statusCtrl.textContent = report.progress === 1 ? cff_ai_texts['ready'] + ' 100%' : report.text;
    }
    if (report.progress !== undefined && progressBarCtrl) {
        progressBarCtrl.style.width = `${report.progress * 100}%`;
    }
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

// ---------- LOCAL INFERENCE ----------

let localAPI = {
    'native': {
        session: null,
        languages: ['en'],
        isModelLoaded: function() {
            return true; // Browser API is always "loaded" as it's just an interface to the native implementation
        },
        isModelLoading: function() {
            return false; // No loading state for browser API
        },
        isModelUnmountable: function() {
            return false; // Browser API does not support manual unloading
        },
        isDescriptionNeeded: function() {
            return false; // Browser API is optimized and does not require user to understand model differences, so no description is needed.
        },
        initializeModel: async function () {
            const languageCode = navigator.language.slice(0, 2).toLowerCase();
            if (!this.languages.includes(languageCode)) { this.languages.push(languageCode); }
            if (this.session !== null) return true; // Already initialized
            return await this.resetChat();
        },
        unloadModel: async function () {
            if (this.session) {
                this.session.destroy();
                this.session = null;
            }
            return true;
        },
        resetChat: async function() {
            if (this.session) {
                this.session.destroy();
                this.session = null;
            }
            try {
                this.session = await LanguageModel.create({
                    expectedInputs: [{ type: "text", languages: this.languages }],
                    expectedOutputs: [{ type: "text", languages: this.languages }],
                    monitor: function (m) {
                        m.addEventListener('downloadprogress', (e) => {
                            console.log(e.loaded, e.total);
                            let progress = e.total ? e.loaded / e.total : undefined;
                            let report = {
                                progress: progress,
                                text: 'Downloading model: ' + (progress ? (progress * 100) + '%' : e.loaded)
                            };
                            updateEngineInitProgressCallback(report);
                        });
                    },
                });
                return true;
            } catch (err) {
                console.error('Error initializing native model:', err);
                return false;
            }
        },
        makeInference: async function(messages, onUpdate, onFinish, onError) {
            if (!this.session) {
                await this.initializeModel();
            } else {
                await this.resetChat(); // Clear previous context to avoid contamination between different questions
            }
            try {
                let response = await this.session.prompt(messages);
                onFinish(response, ''); // Browser API does not provide usage stats
            } catch (err) {
                onError(err);
            }
        },
    },
    'webLLM': {
        model: "Qwen2.5-Coder-1.5B-Instruct-q4f32_1-MLC",
        engine: null,
        loadedModel: false,
        loadingModel: false,
        firstInference: true,
        isModelLoaded: function() {
            return this.loadedModel;
        },
        isModelLoading: function() {
            return this.loadingModel;
        },
        isModelUnmountable: function() {
            return true;
        },
        isDescriptionNeeded: async function() {
            return true;
        },
       initializeModel: async function() {
            if (this.engine == null) {
                try {
                    const webllm = await loadWebLLM();
                    this.engine = new webllm.MLCEngine();
                    this.engine.setInitProgressCallback(updateEngineInitProgressCallback);
                } catch (err) {
                    // WebLLM module failed to load (CDN unreachable, CSP block, etc.).
                    // Reuse the existing fatal-error UI path so the user sees a clear message
                    // and can switch to a cloud provider.
                    await handleEngineError(err, 'loading');
                    return false;
                }
            }

            if (typeof navigator == 'undefined' || !navigator.gpu) {
                document.getElementById('cff-ai-gpu-error').style.display = 'block';
                document.getElementById('cff-ai-gpu-error').parentElement.classList.add('cff-ai-assistance-error-message');
                sendBtnCtrl.disabled = true;
                userQuestionCtrl.disabled = true;
                return false;
            }

            if (typeof window == 'undefined' || !window.caches) {
                document.getElementById('cff-ai-caches-error').style.display = 'block';
                document.getElementById('cff-ai-caches-error').parentElement.classList.add('cff-ai-assistance-error-message');
                sendBtnCtrl.disabled = true;
                userQuestionCtrl.disabled = true;
                return false;
            }

            if (this.isModelLoaded()) return true;
            try {
                this.loadingModel = true;
                let modelToLoad = this.model;
                const config = {
                        temperature: 0.0,
                        top_p: 1,
                    };
                await this.engine.reload(
                    modelToLoad,
                    config
                );
                this.loadedModel = true;
                this.loadingModel = false;
                return true;
            } catch (error) {
                this.loadingModel = false;
                this.loadedModel = false;
                await handleEngineError(error, 'loading');
                return false;
            }
        },
        unloadModel: async function() {
            try {
                await this.engine.unload();
                // Also clear WebLLM caches
                const cacheNames = await caches.keys();
                for (const name of cacheNames) {
                    if (/^webllm\//i.test(name)) {
                        await caches.delete(name);
                    }
                }
            } catch (e) {
                console.warn('Error during unload:', e);
                return false;
            } finally {
                this.engine = null;
                this.loadedModel = false;
                this.firstInference = true;
            }
            return true;
        },
        resetChat: async function() {
            try {
                await this.engine.resetChat();
                this.firstInference = true;
                return true;
            } catch (e) {
                console.warn('Error during reset:', e);
                return false;
            }
        },
        makeInference: async function (messages, onUpdate, onFinish, onError) {
            try {
                if (! this.firstInference) {
                    await this.engine.resetChat();
                }
                this.firstInference = false;
                const completion = await this.engine.chat.completions.create({
                    stream: false,
                    messages
                });

                const finalMessage = completion.choices[0].message.content;
                onUpdate(finalMessage);
                onFinish(finalMessage, "");
            } catch (err) {
                onError(err);
            }
        }
    }
};

let localModelAvailable = null;
function handleFirstInteraction() {
    document.removeEventListener("mousedown", handleFirstInteraction);
    document.removeEventListener("click", handleFirstInteraction);
    document.removeEventListener("keydown", handleFirstInteraction);
    document.removeEventListener("touchstart", handleFirstInteraction);

    localModelAvailable = 'LanguageModel' in window ? 'native' : 'webLLM';
}

// Listen for first interaction
document.addEventListener("mousedown", handleFirstInteraction);
document.addEventListener("click", handleFirstInteraction);
document.addEventListener("keydown", handleFirstInteraction);
document.addEventListener("touchstart", handleFirstInteraction);

function evaluateAPIMethod(methodName, ...args) {
    if (methodName in localAPI[localModelAvailable]) {
        return localAPI[localModelAvailable][methodName](...args);
    }
}


// ---------- Robust error handling helpers ----------
async function unloadModel() {
    await evaluateAPIMethod('unloadModel');
}

function showLocalModelError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'cff-ai-assistance-error';
    errorDiv.innerHTML = `
        <p>${message}</p>
        <button type="button" class="button-secondary" onclick="cff_open_ai_assistant_settings(true);">${window['cff_ai_texts']['switch_btn']}</button>
    `;
    chatBoxCtrl.appendChild(errorDiv);
    chatBoxCtrl.scrollTop = chatBoxCtrl.scrollHeight;

    if (statusCtrl) statusCtrl.textContent = 'Error.';
    sendBtnCtrl.disabled = true;
    userQuestionCtrl.disabled = true;
    unmountBtnCtrl.style.display = 'none';
}

function removeErrorMessages() {
	const row = document.querySelector('.cff-ai-assistant-answer-row');
	if (row) {
	  let last = row.lastElementChild;
	  while (last && last.classList.contains('cff-ai-assistance-error')) {
		const toRemove = last;
		last = last.previousElementSibling;
		toRemove.remove();
	  }
	}
}

function isFatalError(msg) {
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
        /ModelNotLoadedError/i,
        /GPU/i,                  // any GPU-related crash
    ];
    return fatalPatterns.some(pattern => pattern.test(msg));
}

async function handleEngineError(error, context = 'inference') {
    console.error(`Fatal error during ${context}:`, error);
	const msg = (error?.name || '') + (error?.message || '');

    if (isFatalError(msg) || context === 'loading') {
        showLocalModelError(window['cff_ai_texts']['fatal_error'].replace('%s', msg ? `<br><br><i>${msg}</i><br><br>` : '') +'<div><div class="cff-ai-assistant-progress-bar"></div></div><div class="cff-ai-assistant-status"></div>');

        sendBtnCtrl.disabled = false;
        userQuestionCtrl.disabled = false;
        unmountBtnCtrl.style.display = 'inline-block';
    } else {
        // Display a generic error message (not as severe)
        const errorDiv = document.createElement('div');
        errorDiv.className = 'cff-ai-assistance-error';
        errorDiv.textContent = window['cff_ai_texts']['try_again_error'];
        chatBoxCtrl.appendChild(errorDiv);
        chatBoxCtrl.scrollTop = chatBoxCtrl.scrollHeight;
    }
}

// ---------- Core engine initialization ----------
async function initializeLocalEngine() {
    statusCtrl.style.display = 'block';
    return evaluateAPIMethod('initializeModel');
}

// ---------- Streaming generation ----------
async function makeInference(messages, onUpdate, onFinish, onError) {
    return await evaluateAPIMethod('makeInference', messages, onUpdate, onFinish, onError);
}

// ---------- Model loading orchestration ----------
async function loadingEngine() {
    if (window['cff_ai_provider'] === '') {
        window['cff_ai_provider'] = window['cff_ai_default_provider'] || 'local';
        window['cff_ai_model'] = window['cff_ai_default_model'] || '';
        openAIAssistantSettings();
    } else if (isLocalModel()) {
        let isModelLoading = await evaluateAPIMethod('isModelLoading');
        let isModelLoaded  = await evaluateAPIMethod('isModelLoaded');

        aiAssistantLoadingMss.style.display = (isModelLoading || ! isModelLoaded)  ? 'block': 'none';
        if (! isModelLoading) {
            initializeLocalEngine().then(async function (success) {
                if (success) {
                    sendBtnCtrl.disabled = false;
                    if(evaluateAPIMethod('isModelUnmountable')) unmountBtnCtrl.style.display = 'inline-block';
                    aiAssistantLoadingMss.style.display = 'none';
                }
            });
        } else if (isModelLoaded) {
            if (evaluateAPIMethod('isModelUnmountable')) unmountBtnCtrl.style.display = 'inline-block';
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

    const aiMessage = {
        content: '<div style="display: flex; align-items: center; gap: 3px;"><span style="flex-grow:1;" data-cff-ai-assistant-thinking="1">' + ('cff_ai_texts' in window ? window['cff_ai_texts']['thinking'] : "Thinking...") +'</span><span style="font-weight:600;">&#9201;</span></div>',
        role: "assistant",
    };

    const waitingMessages = {
        1: window?.cff_ai_texts?.waiting_message_1 ?? "Still working on it..." ,
        2: window?.cff_ai_texts?.waiting_message_2 ?? "This is taking longer than usual, but please be patient..."
    };

    const firstWaitingInterval = setTimeout(() => {
        const thinkingSpan = [...document.querySelectorAll('[data-cff-ai-assistant-thinking="1"]')].at(-1);
        if (thinkingSpan) {
            thinkingSpan.setAttribute("data-cff-ai-assistant-thinking", "2");
            thinkingSpan.textContent = waitingMessages[1];
        }
    }, 8000);

    const secondWaitingInterval = setTimeout(() => {
        const waitingSpan = [...document.querySelectorAll('[data-cff-ai-assistant-thinking="2"]')].at(-1);
        if (waitingSpan) {
            waitingSpan.setAttribute("data-cff-ai-assistant-thinking", "3");
            waitingSpan.textContent = waitingMessages[2];
        }
    }, 18000);

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
        case 'message':
			let output_format = (extra === 'html') ? 'an HTML structure' : 'a PLAIN TEXT';
            // This promt requires to check if the payment, coupons, and extra settings are enabled in the form.
            message = `
You are a writer. Your job is to produce ${output_format} for a notification email. NEVER INCLUDE the information from the "PAYMENT & ORDER TAGS", or "METADATA TAGS", if they are not mentioned in the REQUEST section.

The fields tags begin with the symbols [{ and end with the symbols }]

- When needed to include a field label you must enter its tag: [{fieldname_label}],
- When needed to include a field shortlabel you must enter its tag: [{fieldname_shortlabel}],
- When needed to include a field value you must enter its tag: [{fieldname_value}],
- When needed to include the tags to the uploaded files you must enter its tag: [{fieldname_urls}],

## TAGS REFERENCE

### FIELDS TAGS

${variables_tags}

### PAYMENT & ORDER TAGS

  [{final_price}]
  [{coupon}]
  [{couponcode}]
  [{payment_option}]
  [{payment_status}]
  [{transaction_id}]
  [{subscription_id}]

### METADATA TAGS

  [{itemnumber}]
  [{submissiondate_ddmmyyyy}]
  [{submissiontime}]
  [{currentdate_ddmmyyyy}]
  [{currenttime}]
  [{ipaddress}]
  [{from_page}]
  [{thank_you_page}]
  [{form_title}]
  [{form_description}]
  [{formid}]
  [{pdf_generator_url}]
  [{csv_generator_url}]

## REQUEST

${input}
`.trim();

        break;
		default:
            message = "Create an immediately invoked JavaScript function expressions (IIFE) that run automatically. It must start with (function(){ and enter with })(). It must include a return statement with the result as scalar value. Use only valid JavaScript syntax. Test your code mentally for syntax errors before submitting. Do not include any non-JavaScript text or characters. Keep the code simple and focused on the calculation. Enclose the code between ``` symbols. DO NOT include comments into the function code." + ("" != variables ? " \n\n CRITICAL INSTRUCTION: The following variables ALREADY EXIST in the system and contain values. DO NOT DEFINE, INITIALIZE, OR ASSIGN ANY VALUE TO THEM IN YOUR CODE:\n\n" + variables : "") + "\n\nFunction description: " + input.replace(/equation/ig, 'function');
        break;
	}

    if ( isLocalModel() ) {
        appendMessage({ content: input, role: "user" });
        appendMessage(aiMessage, true);
        messages.splice(1);
        messages.push({content: message, role: "user"});

        const onFinish = (finalMessage, usageMessage) => {
            clearTimeout(firstWaitingInterval);
            clearTimeout(secondWaitingInterval);
            updateLastMessage(finalMessage);
            sendBtnCtrl.disabled = false;
            setPlaceholder();
            chatStatsCtrl.textContent = usageMessage;
        };

        const onError = async (err) => {
            clearTimeout(firstWaitingInterval);
            clearTimeout(secondWaitingInterval);
            // Error during generation
            console.error('Inference error: ', err);
            sendBtnCtrl.disabled = false;
            userQuestionCtrl.value = input;  // restore input
            setPlaceholder();
            await handleEngineError(err, 'inference');
        };

        makeInference(
            messages,
            updateLastMessage,
            onFinish,
            onError
        );
    } else {
        // Check settings for cloud provider.
        if ( !isWPModel() && (! ('cff_ai_api_key' in window) || window.cff_ai_api_key.trim() === '' ) ) {
            userQuestionCtrl.value = input;
            alert( ( 'cff_ai_texts' in window ? window['cff_ai_texts']['api_key_required'] : 'API Key is required for the selected provider.' ) );
            openAIAssistantSettings();
            return;
        }
        appendMessage({ content: input, role: "user" });
        appendMessage(aiMessage, true);
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
            clearTimeout(firstWaitingInterval);
            clearTimeout(secondWaitingInterval);
        }
    }
}

function appendMessage(message, asHTML = false) {

	const newMessage = document.createElement("div");
    newMessage.classList.add("cff-ai-assistance-message");

    if (message.role === "user") {
        newMessage.classList.add("cff-ai-assistance-user-message");
    } else {
		newMessage.classList.add("cff-ai-assistance-bot-message");
    }
	if (asHTML) {
		newMessage.innerHTML = message.content;
	} else {
		newMessage.textContent = message.content;
	}

    chatBoxCtrl.appendChild(newMessage);
    chatBoxCtrl.scrollTop = chatBoxCtrl.scrollHeight; // Scroll to the latest message
}

function updateLastMessage(content) {
    function formatMessage(message) {
        function escapeHTML(str) {
            return str
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");
        };

        function addTopicSection(escapedCode, topic) {
            let topic_identifier = '<span>' + (topic ?? '') + '</span>';
            let btn = '';
            let btn_text = '';
            switch (topic) {
                case 'list':
                    btn_text = window?.cff_ai_texts?.use_list_btn ?? 'Use list';
                    btn = `<button type="button" id="cff-ai-assistant-use-list-btn" data-text="${btn_text}" class="button-secondary" onclick="cff_ai_assistant_use_list(this);">${btn_text}</button>`;
                break;
                case 'message':
                    btn_text = window?.cff_ai_texts?.copy_message_btn ?? 'Copy message';
                    btn = `<button type="button" id="cff-ai-assistant-use-message-btn" data-text="${btn_text}" class="button-secondary" onclick="cff_ai_assistant_copy(this);">${btn_text}</button>`;
                break;
                default:
                    btn_text = window?.cff_ai_texts?.copy_btn ?? 'Copy';
                    btn = `<button type="button" id="cff-ai-assistant-copy-btn" data-text="${btn_text}" class="button-secondary" onclick="cff_ai_assistant_copy(this);">${btn_text}</button>`;
                break;
            }
            return `${topic_identifier}<pre><div style="text-align:right;margin-bottom:10px;">${btn}</div><code>${escapedCode}</code></pre>`;
        };

		message = message.replace(/fieldname(\d+)/ig, 'fieldname$1');
		if ( topic === 'message' ) {
			message = message.replace(/\[\{/g, '<%').replace(/\}\]/g, '%>');
		}

        // Split message into parts: text and code blocks
        const parts = [];
		let lastIndex = 0;
		const regex = /```(?:\w+)?\n([\s\S]*?)```/g;
		let match;

		if ((match = regex.exec(message)) !== null) {
            let btn = '';
            const before = message.slice(lastIndex, match.index);
			const code = match[1];

			// Escape and add the text before the code block
			parts.push(`<p>${escapeHTML(before)}</p>`);

			// Add the raw code block
            let escapedCode = escapeHTML(code);
            if ( topic === 'list' ) {
                escapedCode = '<ul>' + escapedCode.replace(/&lt;li&gt;/ig, '<li>').replace(/&lt;\/li&gt;/ig, '</li>').replace(/[\r\n]/g, '') + '</ul>';
            } else if (topic === 'js') {
                // Patch for specific case: replace new Date( with DATEOBJ(, this ensure using the plugin operation.
                escapedCode = escapedCode.replace(/new Date\(/g, 'DATEOBJ(');
            }

			parts.push(
                addTopicSection(escapedCode, topic)
			);

			lastIndex = regex.lastIndex;
            // Add any remaining text after the last code block
            const remaining = message.slice(lastIndex);
            if (remaining.trim()) {
                parts.push(`<p>${escapeHTML(remaining)}</p>`);
            }
		} else if (topic === 'message') {
            parts.push( addTopicSection(escapeHTML(message), topic) );
        }

        let output = parts.join("");
        return output;
	}

    const messageDoms = chatBoxCtrl.querySelectorAll(".cff-ai-assistance-message");
    const lastMessageDom = messageDoms[messageDoms.length - 1];
    lastMessageDom.innerHTML = formatMessage(content);
}

window['cff_ai_assistant_copy'] = function ( btn ) {
	const codeElement = btn.parentElement.parentElement.querySelector('code');
	const copy_text = btn.getAttribute('data-text') || ( 'cff_ai_texts' in window ? window['cff_ai_texts']['copy_btn'] : 'Copy' );
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
                    texts.push(p[0]);
                    if(1 < p.length) {
                        values.push(p[1]);
                    } else {
                        values.push(p[0]);
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

window['cff_ai_assistant_open'] = function( _answer_topic, _extra = '' ){
	extra = _extra;
    sendBtnCtrl.disabled = true;
    aiAssistantLoadingMss.style.display = 'none';
	variables = "";
    variables_tags = "";
	topic = _answer_topic || 'js';

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

            if (!( 'exclude' in item ) || ! item.exclude) {
                variables_tags += "- "+item.name+" (" + l + ")\n"+
                "field label tag: [{"+item.name+"_label}]\n"+
                "field shortlabel tag: [{"+item.name+"_shortlabel}]\n"+
                "field value tag: [{"+item.name+"_value}]"+
                (item.ftype == 'ffile' ? "\nuploaded files tag: [{"+item.name+"_urls}]" : "")+
                "\n\n";
            }
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
    return document.getElementById('cff-ai-assistant-settings-container').classList.contains('cff-ai-assistant-settings-opened');
}

function openAIAssistantSettings(cloud) {
    settingsBtnCtrl.classList.add('cff-ai-assistant-settings-active');
    initializeAIAssistantSettings()
    document.getElementById('cff-ai-assistant-settings-container').classList.add('cff-ai-assistant-settings-opened');
    if ( cloud && providerCtrl.value == 'local' ) {
        providerCtrl.value = window['cff_ai_default_provider'];
        providerCtrl.dispatchEvent(new Event('change'));
        modelCtrl.value = window['cff_ai_default_model'];
    }
    sendBtnCtrl.disabled = true;
    userQuestionCtrl.disabled = true;
}

function closeAIAssistantSettings() {
    if ( ! unsavedSettings || window.confirm( window?.cff_ai_texts?.unsave_settings || 'Do you want to close the settings without saving?' )) {
        settingsBtnCtrl.classList.remove('cff-ai-assistant-settings-active');
        document.getElementById('cff-ai-assistant-settings-container').classList.remove('cff-ai-assistant-settings-opened');
        sendBtnCtrl.disabled = false;
        userQuestionCtrl.disabled = false;
        if (
            window['cff_ai_provider'] === 'local' &&
            providerCtrl.value === window['cff_ai_provider']
        ) {
			removeErrorMessages();
            cff_ai_assistant_open(topic);
        }
    }
}

function initializeAIAssistantSettings() {
    if ( 'cff_ai_api_key' in window ) {
        apiKeyCtrl.value = window.cff_ai_api_key;
    }
    if ( 'cff_ai_provider' in window ) {
        providerCtrl.value = window.cff_ai_provider;
        populateModelOptions( window.cff_ai_provider );
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

providerCtrl.addEventListener("input", async function () {
    unsavedSettings = true;
});

modelCtrl.addEventListener("input", async function () {
    unsavedSettings = true;
});

apiKeyCtrl.addEventListener('input', (event) => {
    unsavedSettings = true;
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
        if ( evaluateAPIMethod('isDescriptionNeeded') ) {
            document.querySelector('.cff-ai-local-model-description').style.display = 'block';
        }
    } else {
        document.querySelector('.cff-ai-local-model-description').style.display = 'none';
        // Update the link to the provider's API Key page.
        if (selectedProvider in cff_ai_models && 'api_key_url' in cff_ai_models[selectedProvider]) {
            let providerUrl = `<a href="${cff_ai_models[selectedProvider]['api_key_url']}" target="_blank">${cff_ai_models[selectedProvider]['title']}</a>`;
            document.querySelector('.cff-ai-assistant-provider-url').innerHTML = providerUrl;
        }
        if( selectedProvider == wordpress_ai) {
            modelContainer.style.display = 'none';
            apiKeyContainer.style.display = 'none';
            apiKeyCtrl.setAttribute('required', '');
        } else {
            modelContainer.style.display = 'block';
            apiKeyContainer.style.display = 'block';
            apiKeyCtrl.removeAttribute('required');
        }
    }
});

closeBtnCtrl.addEventListener("click", async function () {
	aiDlgCrl.style.display = 'none';
});

closeSettingsBtnCtrl.addEventListener("click", async function () {
    closeAIAssistantSettings();
});

unmountBtnCtrl.addEventListener("click", async function (evt) {
    if (evaluateAPIMethod('isModelLoaded')) {
        const confirm_message = ( 'cff_ai_texts' in window ? window['cff_ai_texts']['unload'] : 'Would you like to proceed?' );
        if (window.confirm(confirm_message)) {
            await evaluateAPIMethod('unloadModel');
            evt.target.style.display = 'none';
            aiDlgCrl.style.display = 'none';
        }
    }
});

saveSettingsBtnCtrl.addEventListener("click", async function () {
    const selectedProvider = providerCtrl.value;
    const selectedModel = modelCtrl.value;
    const apiKey = apiKeyCtrl.value;
    if (selectedProvider !== 'local' && selectedProvider !== wordpress_ai && apiKey.trim() === '') {
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

    window.cff_ai_provider = selectedProvider;
    window.cff_ai_model = selectedModel;
    window.cff_ai_api_key = apiKey;
    unsavedSettings = false;
    cff_ai_assistant_open(topic);
    this.removeAttribute('disabled');
    closeAIAssistantSettings();
});

sendBtnCtrl.addEventListener("click", function () {
    onMessageSend();
});