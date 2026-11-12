/* Shared by every distribution; optional views use existing markup and configuration.
 * Presentation adapter: the native CFF builder owns field data and serialization.
 * Required controls are checked before moving nodes; native tabs are found by href.
 * Optional settings groups remain visibly disabled when their panels are absent.
 * A failed bootstrap reloads this request in Classic; only a complete mount is ready.
 * Canvas icons are optional and retain the font glyph if rendering/loading fails.
 */
(function ($) {
    'use strict';
	window.cffBuilderModernLoaded = true;
    function failBuilder(error) {
        if (error && window.console) window.console.error('CFF modern builder:', error);
        if (window.cffBuilderModernReady) return;
        window.cffBuilderModernFailed = true;
        if (window.cffBuilderFallback) window.cffBuilderFallback();
        else if (window.cffBuilderWorkspace && window.cffBuilderWorkspace.fallbackUrl) {
            window.location.replace(window.cffBuilderWorkspace.fallbackUrl);
        } else if (window.cffBuilderReveal) window.cffBuilderReveal();
    }
    function initWorkspace() {
        var root = document.querySelector('.cff-form-builder-backend');
        if (!root) { failBuilder(); return; }
        // Essential relational layout rules must be supported before changing native controls.
        if (!window.CSS || !window.CSS.supports || !window.CSS.supports('selector(:has(*))')) {
            failBuilder(); return;
        }
        if (window.getComputedStyle(root).getPropertyValue('--cff-workspace-css-ready').trim() !== '1') {
            failBuilder(); return;
        }
        var palette = root.querySelector('#tabs-1');
        var search = root.querySelector('#cff-field-search');
        var empty = root.querySelector('.cff-palette-empty');
        if (!palette || !search || !empty) { failBuilder(); return; }
        // Icon centering is optional: privacy restrictions must not prevent editing.
        var iconCanvas, iconContext;
        try {
            iconCanvas = document.createElement('canvas');
            iconContext = iconCanvas.getContext('2d');
        } catch (error) { iconContext = null; }
        var iconCache = new WeakMap();
        function resetPaletteIcon(field) {
            field.classList.remove('cff-palette-icon-centered');
            field.style.removeProperty('--cff-palette-icon-image');
        }
        function centerPaletteIcons() {
            if (!iconContext) return;
            palette.querySelectorAll('.itemForm').forEach(function (field) {
                try {
                var style = window.getComputedStyle(field, '::before');
                var content = style.content;
                if (!content || !/^".*"$/.test(content)) return;
                var glyph;
                try { glyph = JSON.parse(content); } catch (error) { return; }
                var width = parseFloat(style.width), height = parseFloat(style.height);
                if (!glyph || !(width > 0) || !(height > 0)) return;
                var ratio = Math.max(1, window.devicePixelRatio || 1);
                var color = window.getComputedStyle(field).getPropertyValue('--cff-category-accent').trim();
                if (!color) return;
                var signature = [content, width, height, ratio, style.fontStyle, style.fontWeight,
                    style.fontSize, style.fontFamily, style.letterSpacing, color].join('|');
                if (iconCache.get(field) === signature) return;
                iconCache.set(field, signature);
                iconCanvas.width = Math.ceil(width * ratio);
                iconCanvas.height = Math.ceil(height * ratio);
                iconContext.setTransform(iconCanvas.width / width, 0, 0, iconCanvas.height / height, 0, 0);
                iconContext.font = style.fontStyle + ' ' + style.fontWeight + ' ' + style.fontSize + ' ' + style.fontFamily;
                iconContext.textAlign = 'left';
                iconContext.textBaseline = 'alphabetic';
                if ('letterSpacing' in iconContext) iconContext.letterSpacing = style.letterSpacing === 'normal' ? '0px' : style.letterSpacing;
                var bounds = iconContext.measureText(glyph);
                var inkWidth = bounds.actualBoundingBoxLeft + bounds.actualBoundingBoxRight;
                var inkHeight = bounds.actualBoundingBoxAscent + bounds.actualBoundingBoxDescent;
                if (!(inkWidth > 0) || !(inkHeight > 0)) return;
                var scale = Math.min(1, (width - 4) / inkWidth, (height - 4) / inkHeight);
                iconContext.fillStyle = color;
                iconContext.translate(width / 2, height / 2);
                iconContext.scale(scale, scale);
                // Center the visible ink, including glyphs rendered by a fallback font.
                iconContext.fillText(glyph,
                    (bounds.actualBoundingBoxLeft - bounds.actualBoundingBoxRight) / 2,
                    (bounds.actualBoundingBoxAscent - bounds.actualBoundingBoxDescent) / 2);
                var image = new Image();
                image.onload = function () {
                    if (iconCache.get(field) !== signature) return;
                    field.style.setProperty('--cff-palette-icon-image', 'url("' + image.src + '")');
                    field.classList.add('cff-palette-icon-centered');
                };
                image.onerror = function () {
                    if (iconCache.get(field) === signature) resetPaletteIcon(field);
                };
                // Only hide the font glyph after the PNG has loaded (including CSP checks).
                image.src = iconCanvas.toDataURL('image/png');
                } catch (error) { resetPaletteIcon(field); }
            });
        }
        var iconFrame;
        function schedulePaletteIcons() {
            window.cancelAnimationFrame(iconFrame);
            iconFrame = window.requestAnimationFrame(centerPaletteIcons);
        }
        function refreshPaletteFonts() {
            iconCache = new WeakMap();
            schedulePaletteIcons();
        }
        if (document.fonts) {
            document.fonts.ready.then(refreshPaletteFonts);
            document.fonts.addEventListener('loadingdone', refreshPaletteFonts);
        } else schedulePaletteIcons();
        window.addEventListener('resize', schedulePaletteIcons);
        function filterFields() {
            var query = search.value.trim().toLocaleLowerCase();
            var matches = 0;
            palette.querySelectorAll('.cff-palette-category').forEach(function (group) {
                var count = 0;
                group.querySelectorAll('.itemForm').forEach(function (field) {
                    var show = !query || (field.textContent + ' ' + field.id + ' ' + group.dataset.title).toLocaleLowerCase().indexOf(query) !== -1;
                    field.hidden = !show;
                    if (show) count++;
                });
                group.hidden = count === 0;
                matches += count;
            });
            empty.hidden = !query || matches > 0;
        }
        search.addEventListener('input', filterFields);
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') event.preventDefault();
            if (event.key === 'Escape') {
                event.stopPropagation();
                search.value = '';
                filterFields();
            }
        });
        // Watch only palette additions: third-party field types keep their native handlers.
        var observer = new MutationObserver(function () {
            filterFields();
            schedulePaletteIcons();
        });
        observer.observe(palette, {childList: true, subtree: true});
        document.addEventListener('keydown', function (event) {
            if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey || event.defaultPrevented) return;
            var target = event.target;
            if (target.closest('input, textarea, select, [contenteditable="true"], .CodeMirror, [role="dialog"]')) return;
            if (root.cffShowFields) root.cffShowFields();
            else return;
            event.preventDefault();
            var fieldsTab = root.querySelector('#tabs > ul a[href="#tabs-1"]');
            if (fieldsTab) fieldsTab.click();
            search.focus();
        });
        // Reflect the actual canvas without touching its field model or serialization.
        var canvas = root.querySelector('#fbuilder');
        if (!canvas) { failBuilder(); return; }
        var count = root.querySelector('.cff-canvas-count');
        function updateCount() {
            if (count) count.textContent = canvas.querySelectorAll('#fieldlist .fields').length;
        }
        new MutationObserver(updateCount).observe(canvas, {childList: true, subtree: true});
        updateCount();

		function revealBuilder() {
            if (window.cffBuilderReveal) window.requestAnimationFrame(window.cffBuilderReveal);
        }

        function installWorkspace() {
			try {
				if (window.cffBuilderModernFailed) return;
				if (root.classList.contains('cff-workspace-ready')) return;
				var labels = window.cffBuilderWorkspace;
				if (!labels) return;
				var form = root.querySelector('#cpformconf');
				var normal = root.querySelector('#normal-sortables');
				var builder = root.querySelector('#metabox_form_structure');
				if (!form || !normal || !builder || !$.fbuilder ||
					builder.querySelectorAll('#tabs > ul a').length < 3 ||
					!builder.querySelector('.cff-revisions-container-flex [name="save"]') ||
					!builder.querySelector('#previewbtn2') ||
					!builder.querySelector('.cff-form-builder-extend-shrink') ||
					!root.querySelector('.cff-save-controls-frame') ||
					!root.querySelector('.cff-navigation-main-menu') ||
					!root.querySelector('.cff-workspace-brand') ||
					!root.querySelector('.cff-workspace-meta') ||
					!root.querySelector('.cff-form-name') ||
                    !root.querySelector('#cff-builder-design-form') ||
                    !root.querySelector('.cff-builder-design-control') ||
                    !form.querySelector('[name="cp_calculatedfieldsf_id"]')) return;
                // Moving a live iframe with appendChild unloads its editor document.
                // Recover in Classic before touching controls on browsers without atomic moves.
                if (root.querySelector('iframe') && typeof normal.moveBefore !== 'function') {
                    failBuilder();
                    return;
                }
                function moveControl(parent, node, before) {
                    if (node.isConnected && parent.isConnected && typeof parent.moveBefore === 'function') {
                        parent.moveBefore(node, before || null);
                    } else parent.insertBefore(node, before || null);
                }
                var nativeTabs = Array.from(builder.querySelectorAll('#tabs > ul a'));
                var tabIndex = {};
                ['fields', 'options', 'appearance'].forEach(function (name, index) {
                    tabIndex[name] = nativeTabs.findIndex(function (anchor) {
                        return anchor.getAttribute('href') === '#tabs-' + (index + 1);
                    });
                });
                if (Object.keys(tabIndex).some(function (name) { return tabIndex[name] < 0; })) return;
				var originalChildren = Array.from(normal.children);
				var activeSection = 'fields';
				var legacyStorageKey = 'cff-workspace-section-' + form.querySelector('[name="cp_calculatedfieldsf_id"]').value;
                var storageKey = legacyStorageKey + ':' + location.pathname.replace(/[^/]*$/, '');

				function element(tag, className, text) {
					var node = document.createElement(tag);
					if (className) node.className = className;
					if (text) node.textContent = text;
					return node;
				}
				function findFieldNode(name) {
					if (!name) return null;
					var selector;
					try {
						selector = '.' + (window.CSS && window.CSS.escape ? window.CSS.escape(name) : name);
					} catch (error) {
						return null;
					}
					try {
						return canvas.querySelector(selector);
					} catch (error) {
						return null;
					}
				}
				function disclosure(label, className) {
					var details = element('details', className);
					details.appendChild(element('summary', '', label));
					return details;
				}
				var shell = element('div', 'cff-editor-shell');
				shell.id = 'cff-editor-shell';
				normal.appendChild(shell);
				var header = element('div', 'cff-editor-header');
				var identity = element('div', 'cff-editor-identity');
				var brand = root.querySelector('.cff-workspace-brand');
				var heading = root.querySelector('.cff-form-name');
				var globalMenu = root.querySelector('.cff-navigation-main-menu');
				var back = globalMenu.querySelector('a[href="admin.php?page=cp_calculated_fields_form"]');
				if (back) {
					back.title = back.textContent.trim();
					back.setAttribute('aria-label', back.title);
					back.className = 'cff-editor-back';
					back.replaceChildren(element('span', 'dashicons dashicons-arrow-left-alt2'));
					back.firstChild.setAttribute('aria-hidden', 'true');
					identity.appendChild(back);
				}
				identity.appendChild(brand);
				var title = element('div', 'cff-editor-title');
				title.appendChild(heading);
				title.appendChild(root.querySelector('.cff-workspace-meta'));
				identity.appendChild(title);
				header.appendChild(identity);

				// Keep auxiliary dialogs and the preference form outside the form settings.
				var more = disclosure(labels.more, 'cff-editor-disclosure cff-editor-global-menu');
				// Dialogs must remain independent of the disclosure and its scrolling/stacking context.
				globalMenu.querySelectorAll('.cff-settings-dialog-modal, #cff-video-tutorial-modal, #cff-video-tutorial-ds-modal').forEach(function (dialog) {
					moveControl(root, dialog, form);
				});
				more.appendChild(globalMenu);
				root.insertBefore(globalMenu.querySelector('#cff-builder-design-form'), form);

				var tools = disclosure(labels.revisions, 'cff-editor-disclosure cff-editor-tools');
				var toolsBody = element('div', 'cff-editor-tools-body');
				var controls = builder.querySelector('.cff-revisions-container-flex');
				var shortcuts = element('div', 'cff-editor-shortcuts');
				['cff_ai_assistant', 'cff_fields_list'].forEach(function (name) {
					var control = controls.querySelector('[name="' + name + '"]');
					if (control) {
						if (name === 'cff_fields_list') control.value = control.title;
						shortcuts.appendChild(control);
					}
				});
				header.appendChild(shortcuts);
				var actions = element('div', 'cff-editor-actions');
				actions.appendChild(controls.querySelector('.cff-form-builder-extend-shrink'));
				actions.querySelectorAll('.cff-form-builder-extend-shrink button').forEach(function (button) {
					button.setAttribute('aria-label', button.textContent.trim());
				});
				actions.appendChild(controls.querySelector('#previewbtn2'));
				actions.appendChild(controls.querySelector('[name="save"]'));
				// Classic save buttons add an anchor; modern navigation restores its own view.
				form.addEventListener('submit', function () {
					form.action = form.action.replace(/#.*$/, '');
				});
				Array.from(controls.childNodes).forEach(function (node) {
					if (node.nodeType === Node.TEXT_NODE) node.remove();
				});
				if (controls.querySelector('[name="cff_revision_list"]')) {
					toolsBody.appendChild(element('h3', 'cff-editor-revisions-title', labels.revisions));
				}
				toolsBody.appendChild(controls);
				var revisionOptions = root.querySelector('.cff-save-controls-frame');
				var revisionLabel = revisionOptions.querySelector('label');
				if (revisionLabel) {
					var revisionText = element('span', 'cff-editor-revision-label');
					Array.from(revisionLabel.childNodes).forEach(function (node) {
						if (node.nodeType === Node.TEXT_NODE) revisionText.appendChild(node);
					});
					revisionLabel.appendChild(revisionText);
				}
				toolsBody.appendChild(revisionOptions);
				tools.appendChild(toolsBody);
				header.appendChild(tools);
				header.appendChild(actions);
				var globalControls = element('div', 'cff-editor-global-controls');
				globalControls.appendChild(more);
				globalControls.appendChild(globalMenu.querySelector('.cff-builder-design-control'));
				header.appendChild(globalControls);
				shell.appendChild(header);

				var body = element('div', 'cff-editor-body');
				var rail = element('nav', 'cff-editor-rail');
				rail.setAttribute('aria-label', labels.navigation);
				var stage = element('div', 'cff-editor-stage');
				var buildPane = element('section', 'cff-editor-build-pane');
				buildPane.id = 'cff-editor-build';
				var settingsPane = element('section', 'cff-editor-settings-pane');
				settingsPane.id = 'cff-editor-settings';
				var settingsMenu = element('nav', 'cff-editor-settings-menu');
				settingsMenu.setAttribute('aria-label', labels.settings);
				var settingsHeading = element('h2', '', labels.settings);
				settingsMenu.appendChild(settingsHeading);
				var settingsContent = element('div', 'cff-editor-settings-content');
				function fitSettings() {
					var style = window.getComputedStyle(settingsContent);
					var width = settingsContent.clientWidth - parseFloat(style.paddingLeft || 0) - parseFloat(style.paddingRight || 0);
					if (width > 0) settingsContent.classList.toggle('cff-settings-narrow', width <= 800);
				}
				if (window.ResizeObserver) new ResizeObserver(fitSettings).observe(settingsContent);
				else window.addEventListener('resize', fitSettings);
				settingsPane.appendChild(settingsMenu);
				settingsPane.appendChild(settingsContent);
				stage.appendChild(buildPane);
				stage.appendChild(settingsPane);
				body.appendChild(rail);
				body.appendChild(stage);
				shell.appendChild(body);
				moveControl(buildPane, builder);

				var footer = element('div', 'cff-editor-footer');
				var context = element('span', 'cff-editor-context', labels.selectField);
				var status = element('span', 'cff-editor-status', document.querySelector('#setting-error-settings_updated') ? labels.saved : labels.ready);
				status.setAttribute('role', 'status');
				footer.appendChild(context);
				footer.appendChild(status);
				shell.appendChild(footer);

				var panels = {};
				function addPanel(id, label, group, nodes) {
					var panel = element('div', 'cff-editor-settings-panel');
					panel.id = 'cff-editor-panel-' + id;
                    // Extension markup owns its internal layout, including unknown future add-ons.
                    if (group === 'addons' || id === 'other') panel.classList.add('cff-editor-extension-content');
					settingsContent.appendChild(panel);
					nodes.forEach(function (node) { if (node) moveControl(panel, node); });
					var button = element('button', 'cff-editor-section-link', label);
					button.type = 'button';
					button.dataset.section = id;
					button.setAttribute('aria-controls', panel.id);
					settingsMenu.appendChild(button);
					panels[id] = {node: panel, button: button, group: group};
					button.addEventListener('click', function () { showSection(id); });
				}
				[
					['general', labels.general, 'metabox_submit_thank'],
					['texts', labels.texts, 'metabox_define_texts'],
					['validation', labels.validation, 'metabox_define_validation_texts'],
					['notifications', labels.notifications, 'metabox_notification_email'],
					['userEmail', labels.userEmail, 'metabox_email_copy_to_user'],
					['captcha', labels.captcha, 'metabox_captcha_settings'],
					['help', labels.help, 'metabox_basic_settings']
				].forEach(function (item) {
					var node = root.querySelector('#' + item[2]);
					if (node) addPanel(item[0], item[1], 'settings', [node]);
				});
				var payment = root.querySelector('#metabox_payment_settings');
				if (payment) {
                    var gateways = payment.querySelector('#cff-payment-gateways-accordion');
                    if (gateways) gateways.classList.add('cff-editor-extension-content');
					var previous = payment.previousElementSibling;
					var paymentNodes = [payment];
					if (previous && previous.tagName === 'HR' && previous.previousElementSibling && previous.previousElementSibling.tagName === 'H2') {
						paymentNodes.unshift(previous.previousElementSibling, previous);
					} else if (previous && previous.tagName === 'H2') paymentNodes.unshift(previous);
					addPanel('payments', labels.payments, 'payments', paymentNodes);
				}
				var addonsAnchor = root.querySelector('#metabox_addons_section');
				var addonsNodes = [];
				if (addonsAnchor) {
					var next = addonsAnchor.nextElementSibling;
					addonsNodes.push(addonsAnchor);
					// Includes every node produced by cpcff_form_settings and the add-on renderer.
					while (next && next.id !== 'metabox_basic_settings' && !next.classList.contains('cff-editor-shell')) {
						var following = next.nextElementSibling;
						addonsNodes.push(next);
						next = following;
					}
				}
				if (addonsNodes.length) addPanel('addons', labels.addons, 'addons', addonsNodes);

                // Native promotions have a separate view; they are not form settings.
                var promotion = root.querySelector('#cff-upgrade-frame');
                if (promotion && labels.upgrade) addPanel('upgrade', labels.upgrade, 'upgrade', [promotion]);

                // Keep duplicate native footer actions out of extension settings.
                var footerLinks = [];
                [normal, form].forEach(function (container) {
                    container.querySelectorAll(':scope > p.submit').forEach(function (node) {
                        if (node.querySelector('[name="save"]')) node.classList.add('cff-editor-legacy-footer');
                    });
                    container.querySelectorAll(':scope > a[href]').forEach(function (anchor) {
                        var href = anchor.getAttribute('href');
                        if (!anchor.hasAttribute('onclick') && footerLinks.indexOf(href) !== -1) {
                            anchor.classList.add('cff-editor-legacy-footer');
                            return;
                        }
                        footerLinks.push(href);
                        anchor.classList.add('button-secondary');
                        globalMenu.appendChild(anchor);
                    });
                });

				// Unknown extension panels remain available instead of being discarded by the new navigation.
				var otherNodes = originalChildren.filter(function (node) {
					return node.parentElement === normal && !node.matches('input, datalist, script, style, link, pre, hr, .cff-navigation-sections-menu, .cff-save-controls-floating-popup, .cff-save-controls-frame, .cff-editor-legacy-footer');
				});
				if (otherNodes.length) addPanel('other', labels.other, 'settings', otherNodes);

				// Navigate to the original configuration boxes without moving their controls.
				var contextMenus = {};
				['payments', 'addons'].forEach(function (id) {
					if (!panels[id]) return;
					var panel = panels[id];
					panel.button.textContent = id === 'payments' ? labels.generalSettings : labels.overview;
					var menu = element('div', 'cff-editor-context-links');
					panel.button.after(menu);
					contextMenus[id] = {node: menu, links: [], signature: null};
					refreshContextMenu(id);
					new MutationObserver(function () { refreshContextMenu(id); }).observe(panel.node, {childList: true, subtree: true});
				});

				function refreshContextMenu(id) {
					var panel = panels[id];
					var menu = contextMenus[id];
					var container = id === 'payments' ? panel.node.querySelector('#cff-payment-gateways-accordion') : panel.node;
					var entries = container ? Array.from(container.querySelectorAll('.postbox, .cff-add-on-settings')).filter(function (box) {
						var parent = box.parentElement.closest('.postbox, .cff-add-on-settings');
						return !parent || !container.contains(parent);
					}).map(function (box, index) {
						var heading = box.querySelector(':scope > .hndle, :scope > h2, :scope > h3');
						var label = '';
						if (heading) {
							var text = heading.cloneNode(true);
							text.querySelectorAll('button, input, select, textarea, script, style, .handle-actions, .screen-reader-text').forEach(function (node) { node.remove(); });
							text.querySelectorAll('a').forEach(function (anchor) {
								if (/^[\s\[\]?↗]*$/.test(anchor.textContent)) anchor.remove();
							});
							label = text.textContent.replace(/^[\s❯▸▾▶▼]+/, '').replace(/^CFF\s*[-–]\s*/i, '').trim();
						}
						if (!label && box.id === 'metabox_pay_later') label = labels.payLater;
						if (!label) return null;
						if (!box.id) box.id = 'cff-editor-' + id + '-target-' + index;
						return {target: box, label: label};
					}).filter(Boolean) : [];
					var signature = JSON.stringify(entries.map(function (entry) { return [entry.target.id, entry.label]; }));
					if (signature === menu.signature) return;
					menu.signature = signature;
					var previous = menu.links.find(function (link) { return link.button.getAttribute('aria-current') === 'location'; });
					menu.node.replaceChildren(element('h3', '', id === 'payments' ? labels.paymentMethods : labels.addonSettings));
					menu.links = entries.map(function (entry) {
						var button = element('button', 'cff-editor-section-link cff-editor-jump-link', entry.label);
						button.type = 'button';
						button.setAttribute('aria-controls', entry.target.id);
						button.setAttribute('aria-current', previous && previous.target === entry.target ? 'location' : 'false');
						button.addEventListener('click', function () {
							showSection(id);
							revealTarget(entry.target, id);
						});
						menu.node.appendChild(button);
						return {target: entry.target, button: button};
					});
					if (id === 'addons') {
						if (!entries.length) menu.node.appendChild(element('p', 'cff-editor-menu-empty', labels.noAddonSettings));
						var manage = element('a', 'cff-editor-manage-addons', labels.manageAddons);
						manage.href = labels.manageAddonsUrl;
                        if (labels.manageAddonsNewWindow) {
                            manage.target = '_blank';
                            manage.rel = 'noopener noreferrer';
                        }
						menu.node.appendChild(manage);
					}
				}

				function revealTarget(target, id) {
					// CFF uses cff-metabox-closed; opening only the native "close" class is insufficient.
					for (var box = target; box && box !== settingsContent; box = box.parentElement) {
						if (box.matches('.postbox, .cff-add-on-settings')) {
							box.classList.remove('close', 'closed');
							if (box.classList.contains('cff-metabox-closed')) {
								box.classList.remove('cff-metabox-closed');
								box.classList.add('cff-metabox-opened');
							}
						}
					}
					var menu = contextMenus[id];
					if (menu) {
						var match = menu.links.find(function (link) { return link.target === target || link.target.contains(target); });
						panels[id].button.setAttribute('aria-current', match ? 'false' : 'page');
						menu.links.forEach(function (link) { link.button.setAttribute('aria-current', link === match ? 'location' : 'false'); });
						if (match) context.textContent = match.button.textContent;
					}
					refreshEditors(target);
					requestAnimationFrame(function () {
						var padding = parseFloat(getComputedStyle(settingsContent).paddingTop) || 0;
						settingsContent.scrollTop += target.getBoundingClientRect().top - settingsContent.getBoundingClientRect().top - padding;
						var heading = target.querySelector(':scope > .hndle, :scope > h2, :scope > h3') || target;
						if (!heading.hasAttribute('tabindex')) heading.tabIndex = -1;
						heading.focus({preventScroll: true});
					});
				}

				var railButtons = {};
				[
					['fields', labels.fields, 'feedback'],
					['appearance', labels.appearance, 'media-document'],
					['settings', labels.settings, 'admin-generic'],
					['payments', labels.payments, 'money-alt'],
					['addons', labels.addons, 'admin-plugins'],
                    ['upgrade', labels.upgrade, 'star-filled']
				].forEach(function (item) {
                    if (item[0] === 'upgrade' && !panels.upgrade) return;
					var button = element('button', 'cff-editor-rail-button');
                    var section = item[0] === 'fields' || item[0] === 'appearance' ? item[0] :
                        Object.keys(panels).find(function (id) { return panels[id].group === item[0]; });
                    if (!section) {
                        button.disabled = true;
                        button.setAttribute('aria-disabled', 'true');
                        button.title = labels.unavailable;
                    }
					button.type = 'button';
					button.dataset.view = item[0];
					var icon = element('span', 'dashicons dashicons-' + item[2]);
					icon.setAttribute('aria-hidden', 'true');
					button.appendChild(icon);
					button.appendChild(element('span', '', item[1]));
					button.setAttribute('aria-controls', item[0] === 'fields' || item[0] === 'appearance' ? buildPane.id : settingsPane.id);
					rail.appendChild(button);
					railButtons[item[0]] = button;
					button.addEventListener('click', function () {
						showSection(section);
					});
				});

				function remember() {
					try { sessionStorage.setItem(storageKey, activeSection); } catch (error) {}
				}
                function dialogOpen() {
                    return $('#cff-ai-assistant-container:visible, #cff-video-tutorial-modal:visible, #cff-video-tutorial-ds-modal:visible, .cff-settings-dialog-modal:visible, .media-modal:visible, .ui-dialog:visible, .clr-picker.clr-open:visible, #cff-advanced-equation-editor:visible').length ||
                        document.querySelector('.cff-editor-container.fullscreen');
                }
				function refreshEditors(container) {
					// Editors measured inside hidden panels need a refresh when their panel opens.
					requestAnimationFrame(function () {
						$(container).trigger('cff-builder-panel-shown');
						$(container).find('.CodeMirror').each(function () {
                            try { if (this.CodeMirror) this.CodeMirror.refresh(); }
                            catch (error) { if (window.console) window.console.error('CFF editor refresh:', error); }
                        });
					});
				}
				function showSection(id) {
					if (!id || (!panels[id] && id !== 'fields' && id !== 'appearance')) return;
					activeSection = id;
					var building = id === 'fields' || id === 'appearance';
					buildPane.hidden = !building;
					settingsPane.hidden = building;
					var group = building ? id : panels[id].group;
                    shell.classList.toggle('cff-editor-upgrade', group === 'upgrade');
					Object.keys(railButtons).forEach(function (key) {
						railButtons[key].setAttribute('aria-current', key === group ? 'page' : 'false');
					});
					Object.keys(panels).forEach(function (key) {
						panels[key].node.hidden = key !== id;
						panels[key].button.hidden = panels[key].group !== group;
						panels[key].button.setAttribute('aria-current', key === id ? 'page' : 'false');
					});
					Object.keys(contextMenus).forEach(function (key) {
						contextMenus[key].node.hidden = building || key !== id;
						contextMenus[key].links.forEach(function (link) { link.button.setAttribute('aria-current', 'false'); });
					});
					if (contextMenus[id]) refreshContextMenu(id);
					if (building) {
						shell.classList.toggle('cff-editor-appearance', id === 'appearance');
						if (id === 'appearance') $('#tabs').tabs('option', 'active', tabIndex.appearance);
						else if ($('#tabs').tabs('option', 'active') === tabIndex.appearance) $('#tabs').tabs('option', 'active', tabIndex.fields);
						context.textContent = id === 'appearance' ? labels.appearance : labels.selectField;
						refreshEditors(builder);
					} else {
						settingsHeading.textContent = labels[group];
						panels[id].node.querySelectorAll(':scope > .postbox.close, :scope > .postbox.cff-metabox-closed').forEach(function (box) {
							box.classList.remove('close', 'cff-metabox-closed');
							if (box.classList.contains('cff-metabox')) box.classList.add('cff-metabox-opened');
						});
						settingsContent.scrollTop = 0;
						fitSettings();
						context.textContent = panels[id].button.textContent;
						refreshEditors(panels[id].node);
					}
					remember();
				}

				function openTarget(hash) {
					if (hash === '#cpformconf' || hash === '#metabox_form_structure') { showSection('fields'); return true; }
					if (!/^#[\w-]+$/.test(hash)) return false;
					var target = document.getElementById(hash.slice(1));
					if (!target) return false;
					var panel = target.closest('.cff-editor-settings-panel');
					if (!panel) return false;
					var id = Object.keys(panels).find(function (key) { return panels[key].node === panel; });
					showSection(id);
					revealTarget(target, id);
					return true;
				}
				root.addEventListener('click', function (event) {
					var anchor = event.target.closest('a[href^="#"]');
					if (anchor && openTarget(anchor.getAttribute('href'))) event.preventDefault();
				});
				window.addEventListener('hashchange', function () { openTarget(location.hash); });
				window.cpcff_snapToPosition = function () { showSection('fields'); };
				$.fbuilder.lockToBuilder = function () {
					shell.scrollIntoView({block: 'nearest'});
					refreshEditors(buildPane);
				};
				root.cffShowFields = window.cpcff_snapToPosition;

				// The native CFF tab widget still owns selection and field editing.
				var anchors = builder.querySelectorAll('#tabs > ul a');
				anchors[tabIndex.fields].textContent = labels.addFields;
				anchors[tabIndex.options].textContent = labels.fieldOptions;
				builder.querySelector('#tabs > ul').addEventListener('keydown', function (event) {
					if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1) return;
					var visible = Array.from(anchors).filter(function (anchor) { return anchor.offsetParent !== null; });
					var index = visible.indexOf(event.target.closest('a'));
					if (index < 0 || !visible.length) return;
					event.preventDefault();
					event.stopPropagation();
					var next = event.key === 'Home' ? 0 : event.key === 'End' ? visible.length - 1 :
						(index + (event.key === 'ArrowLeft' ? -1 : 1) + visible.length) % visible.length;
					visible[next].click();
					visible[next].focus();
				}, true);
				$('#tabs').on('tabsactivate.cffworkspace', function (event, ui) {
					if (!buildPane.hidden) {
						var appearance = ui.newPanel.attr('id') === 'tabs-3';
						activeSection = appearance ? 'appearance' : 'fields';
						remember();
						shell.classList.toggle('cff-editor-appearance', appearance);
						railButtons.fields.setAttribute('aria-current', appearance ? 'false' : 'page');
						railButtons.appearance.setAttribute('aria-current', appearance ? 'page' : 'false');
						context.textContent = appearance ? labels.appearance : ui.newPanel.attr('id') === 'tabs-2' ? labels.fieldOptions : labels.selectField;
					}
				});
				$(document).on('cff-item-added.cffworkspace', function (event, field) {
					// Let addItem/sortable finish updating the model before opening its inspector.
					requestAnimationFrame(function () {
						showSection('fields');
						if (field && field.name) {
							var node = findFieldNode(field.name);
							if (node) { node.click(); node.scrollIntoView({block: 'nearest'}); }
						}
					});
				});
				// One browser preference for every form in this WordPress installation.
				var fullscreenKey = 'cff-workspace-fullscreen:' + location.pathname.replace(/[^/]*$/, '');
				var fullscreen = true;
				try { fullscreen = localStorage.getItem(fullscreenKey) !== 'false'; } catch (error) {}
				builder.classList.toggle('fullscreen', fullscreen);
				shell.classList.toggle('cff-editor-fullscreen', fullscreen);
				new MutationObserver(function () {
					var expanded = builder.classList.contains('fullscreen');
					shell.classList.toggle('cff-editor-fullscreen', expanded);
					try { localStorage.setItem(fullscreenKey, String(expanded)); } catch (error) {}
				}).observe(builder, {attributes: true, attributeFilter: ['class']});

				function markChanged() {
					status.textContent = labels.changes;
					status.classList.add('cff-editor-unsaved');
					$.fbuilder.formWasModified = true;
				}
                // Select2 commits user selections through synthetic change events.
                $(form).on('select2:select.cffworkspace select2:unselect.cffworkspace select2:clear.cffworkspace', 'select[name]', markChanged);
				form.addEventListener('input', function (event) {
					if (event.target.id !== 'cff-field-search' && !event.target.closest('.cff-editor-tools, .select2-search')) markChanged();
				});
				form.addEventListener('invalid', function (event) {
					var panel = event.target.closest('.cff-editor-settings-panel');
					if (panel) {
						var id = Object.keys(panels).find(function (key) { return panels[key].node === panel; });
						showSection(id);
					}
				}, true);
                var editorValues = new WeakMap();
                form.querySelectorAll('textarea').forEach(function (textarea) { editorValues.set(textarea, textarea.value); });
				$(form).on('change.cffworkspace', function (event) {
                    var changed = true;
                    if (event.target.matches('textarea')) {
                        changed = editorValues.get(event.target) !== event.target.value;
                        editorValues.set(event.target, event.target.value);
                    }
                    // Rich text and CodeMirror mirror user edits through jQuery events.
                    // Ignore synthetic setup changes while the editor is not being used.
                    if (!event.originalEvent) {
                        var editor = event.target.nextElementSibling;
                        if (!changed || !event.target.matches('textarea') || !editor || !editor.matches('iframe, .CodeMirror') ||
                            (editor !== document.activeElement && !editor.contains(document.activeElement))) return;
                    }
					if (event.target.matches('[name]') && !event.target.closest('.cff-editor-tools')) markChanged();
				});
				$(document).on('cff-builder-data-updated.cffworkspace', function () {
					if ($.fbuilder.formWasModified) markChanged();
				});
				document.addEventListener('click', function (event) {
					[tools, more].forEach(function (details) { if (!details.contains(event.target)) details.open = false; });
				});
				document.addEventListener('keydown', function (event) {
					if (event.key === 'Escape') [tools, more].forEach(function (details) { details.open = false; });
					if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                        if (event.defaultPrevented || !root.contains(event.target) ||
                            event.target.closest('[role="dialog"]') || dialogOpen()) return;
						event.preventDefault();
						actions.querySelector('[name="save"]').click();
					}
				});
				document.addEventListener('keydown', function (event) {
					if (event.key === 'Escape' && shell.classList.contains('cff-editor-fullscreen') &&
						!event.defaultPrevented &&
						!event.target.closest('input, textarea, select, [contenteditable="true"], .CodeMirror, .select2-container, [role="dialog"]') &&
						!root.querySelector('details[open]') &&
						!dialogOpen()) {
						actions.querySelector('[name="cff_shrink_btn"]').click();
					}
				}, true);
				var menuActions = element('div', 'cff-editor-menu-group');
				var menuResources = element('div', 'cff-editor-menu-group');
				menuActions.appendChild(element('h2', '', labels.formActions));
				menuResources.appendChild(element('h2', '', labels.resources));
				globalMenu.querySelectorAll(':scope > a, :scope > span > a').forEach(function (anchor) {
					var href = anchor.getAttribute('href') || '';
					var action = href.indexOf('_sub_new') !== -1 || href.indexOf('list=1') !== -1;
					var icon = action ? (href.indexOf('_sub_new') !== -1 ? 'plus-alt2' : 'feedback') :
						(anchor.hasAttribute('onclick') ? (anchor.closest('span') ? 'video-alt3' : 'admin-links') :
							(href.indexOf('custom') !== -1 ? 'admin-tools' : 'editor-help'));
					var mark = element('span', 'dashicons dashicons-' + icon);
					mark.setAttribute('aria-hidden', 'true');
					anchor.prepend(mark);
					if (!anchor.hasAttribute('href')) {
						anchor.setAttribute('role', 'button');
						anchor.tabIndex = 0;
						anchor.addEventListener('keydown', function (event) {
							if (event.key === 'Enter' || event.key === ' ') {
								event.preventDefault();
								anchor.click();
							}
						});
					}
					(action ? menuActions : menuResources).appendChild(anchor);
				});
				globalMenu.appendChild(menuActions);
				globalMenu.appendChild(menuResources);
				globalMenu.addEventListener('click', function (event) {
					if (event.target.closest('a')) more.open = false;
				});
                [normal, form].forEach(function (container) {
                    Array.from(container.childNodes).forEach(function (node) {
                        if (node.nodeType === Node.TEXT_NODE && /^[\s\[\]|]*$/.test(node.textContent)) node.textContent = '';
                    });
                });
				root.classList.add('cff-workspace-ready');
				schedulePaletteIcons();
				var remembered;
				try { remembered = sessionStorage.getItem(storageKey) || sessionStorage.getItem(legacyStorageKey); } catch (error) {}
				showSection(remembered && (panels[remembered] || remembered === 'appearance') ? remembered : 'fields');
				if (location.hash && location.hash !== '#cpformconf') openTarget(location.hash);
                window.cffBuilderModernReady = true;
			} catch (error) {
                root.classList.remove('cff-workspace-ready');
                failBuilder(error);
			} finally {
                if (window.cffBuilderModernReady) revealBuilder();
                else if (!window.cffBuilderModernFailed) failBuilder();
			}
        }
        $(document).on('cff-builder-ready.cffworkspace', installWorkspace);
        if (window.cff_form && $.fbuilder) installWorkspace();
    }
    $(function () {
        try { initWorkspace(); }
        catch (error) { failBuilder(error); }
    });
})(jQuery);
