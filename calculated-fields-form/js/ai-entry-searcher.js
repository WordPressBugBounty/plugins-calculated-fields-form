/*
 * CFF Entries AI Searcher — front-end controller.
 */
(function ($) {
    'use strict';

    var config = window.cpcff_ai_entry_searcher_config || {};
    var strings = config.strings || {};

    // ---------------------------------------------------------------
    // functionality
    // ---------------------------------------------------------------
    function getSettings() {
        return {
            'provider': $('#cff-ai-model-provider option:selected').attr('data-provider'),
            'model': String($('#cff-ai-model-provider').val()),
            'api_key': String($('#cff-ai-api-key').val()).trim()
        };
    }

    async function saveSettings() {
        let $button = $('#cff-ai-save-btn');
        const {provider, model, api_key} = getSettings();

        $button.prop('disabled', true).text(strings.saving_settings || 'Saving settings...');

        // Save settings.
        let formData = new FormData();
        formData.append('cff_ai_form_save_settings', true);
        formData.append('cff_ai_form_generator_provider', provider);
        formData.append('cff_ai_form_generator_model', model);
        formData.append('cff_ai_form_generator_api_key', api_key);
        const response = await fetch(
            config['save_settings_url'],
            {
                'method': 'POST',
                'body': formData
            }
        );

        $button.prop('disabled', false).text(strings.save_settings || 'Save settings');

        if (response.ok) {
            const output = await response.text();
            try {
                let result = JSON.parse(output);
                if ('error' in result) {
                    alert( escapeHtml(result['error']) );
                }

                if ('success' in result) {
                    alert( escapeHtml(result['success']) );
                }
            } catch (err) {
                alert( escapeHtml(err.message) );
            }
        }
    }

    async function sendMessage() {
        let $form       = $('#cff-ai-searcher-form'),
            $input      = $('#cff-ai-searcher-input'),
            $sendBtn    = $('#cff-ai-searcher-send'),
            search_criteria = $input.val().replace(/[\n\r]+/g, "\n").trim();
        const { provider, model, api_key } = getSettings();

        if (!search_criteria) {
            alert(strings.empty || 'Please enter a search criteria.');
            return;
        }
        $form.append('<div class="cff-processing-form"></div>');
        setTimeout(function () {
            $form.find('.cff-processing-form').append('<div class="cff-still-loading">' + strings['still_thinking'] ?? 'Be patient, still thinking...' + '</div>');
        }, 5000);
        // Disable send button.
        $input.prop('disabled', true);
        $sendBtn.prop('disabled', true).text(strings.sending || 'Sending...');

        // Make inference.
        let formData = new FormData();
        formData.append('cff_ai_entry_searcher_description', search_criteria);
        formData.append('cff_ai_entry_searcher_provider', provider);
        formData.append('cff_ai_entry_searcher_model', model);
        formData.append('cff_ai_entry_searcher_api_key', api_key);

        const response = await fetch(
            config['search_entries_url'],
            {
                'method': 'POST',
                'body': formData
            }
        );

        $('.cff-processing-form').remove();
        $sendBtn.prop('disabled', false).text(strings.send || 'Send');
        $input.prop('disabled', false);

        if (response.ok) {
            const output = await response.text();
            try {
                let result = JSON.parse(output);
                if ('error' in result) {
                    throw new Error(result['error']);
                }

                if ('success' in result) {
                    $sendBtn.closest('form').submit();
                }
            } catch (err) {
                alert( escapeHtml(err.message) );
            }
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // ---------------------------------------------------------------
    // Init
    // ---------------------------------------------------------------

    $(function () {
        // Toggle button.
        $(document).on('click', '#cff-display-ai-filter', function (e) {
            e.preventDefault();
            $('#cff-basic-filter').hide();
            $('#cff-ai-searcher-filter').show();
            $(this).hide();
            $('#cff-display-basic-filter').show();
        });

        $(document).on('click', '#cff-display-basic-filter', function (e) {
            e.preventDefault();
            $('#cff-ai-searcher-filter').hide();
            $('#cff-basic-filter').show();
            $(this).hide();
            $('#cff-display-ai-filter').show();
        });

        // Model provider change / show-hide key.
        $(document).on('change', '#cff-ai-model-provider', function (e) {
            $('#cff-ai-api-key')[$(this).val() == 'wordpress-ai' ? 'hide' : 'show']();
        });
        $('#cff-ai-model-provider').trigger('change');

        $(document).on('focus', '#cff-ai-api-key', function (e) {
            this.type = 'text';
        }).on('blur', '#cff-ai-api-key', function (e) {
            this.type = 'password';
        });

        $(document).on('click', '#cff-ai-save-btn', function (e) {
            if ( ! ( 'save_settings_url' in config ) ) {
                return;
            }
            saveSettings();
        });

        $(document).on('click', '#cff-ai-clear-btn', function (e) {
            $('#cff-ai-api-key').val('');
            $('#cff-ai-save-btn').trigger('click');
        });

        // Send button.
        $(document).on('click', '#cff-ai-searcher-send', function (e) {
            e.preventDefault();
            sendMessage();
        });

        // Ctrl+Enter to send.
        $(document).on('keydown', '#cff-ai-searcher-input', function (e) {
            if (e.ctrlKey && e.keyCode === 13) {
                e.preventDefault();
                sendMessage();
            }
        });
    });

})(window.jQuery);
