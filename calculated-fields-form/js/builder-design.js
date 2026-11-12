/* The presentation preference is submitted separately from form settings. */
(function ($) {
    'use strict';
    $(function () {
        var description = $('.cff-builder-design-description');
        if (description.length) {
            var showDescription = function () {
                description.fadeIn(250).delay(5000).fadeOut(250);
            };
            // The modern editor stays hidden while its controls are mounted.
            if (document.documentElement.classList.contains('cff-builder-loading')) {
                var revealObserver = new MutationObserver(function () {
                    if (!document.documentElement.classList.contains('cff-builder-loading')) {
                        revealObserver.disconnect();
                        showDescription();
                    }
                });
                revealObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
            } else {
                showDescription();
            }
        }
        var preference = document.getElementById('cff-builder-design-form');
        var form = document.getElementById('cpformconf');
        if (!preference || !form) return;
        var settingsChanged = false;
		var markSettingsChanged = function (event) {
            if (event.target.matches('[name]') && !event.target.closest('.cff-editor-tools')) settingsChanged = true;
        };
		form.addEventListener('change', markSettingsChanged);
        form.addEventListener('input', markSettingsChanged);
        preference.addEventListener('submit', function (event) {
            if (settingsChanged || ($.fbuilder && $.fbuilder.formWasModified)) {
                if (!window.confirm(window.cffBuilderDesign.unsaved)) {
                    event.preventDefault();
                    return;
                }
                // The user has explicitly chosen to discard; avoid a second native warning.
                if ($.fbuilder) $.fbuilder.formWasModified = false;
            }
        });
    });
})(jQuery);
