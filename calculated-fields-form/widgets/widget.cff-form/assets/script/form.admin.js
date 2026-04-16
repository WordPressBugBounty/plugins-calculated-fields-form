	$.fbuilder.typeList.push(
		{
			id:"formimporter_widget",
            name: "Import Form Fields",
			control_category:30
		}
	);
	$.fbuilder.controls[ 'formimporter_widget' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'formimporter_widget' ].prototype,
		$.fbuilder.controls[ 'fdiv' ].prototype,
		{
			ftype:"formimporter_widget",
            init:function(){
                var me = this;

                // Create the replacement div.
                var fiv_obj = new $.fbuilder.controls['fdiv']()
                fiv_obj = $.extend(true, {}, fiv_obj);

                // Overwrite all me properties with fiv_obj properties.
                for (var prop in fiv_obj) {
                    me[prop] = fiv_obj[prop];
                }

                var modalId = 'cpcff-forms-modal';
                var overlayId = 'cpcff-forms-modal-overlay';

                $.fbuilder.controls['fdiv'].prototype.init.call(me);

                $(document).one('cff-item-added', function(evt, obj){
                    if ( obj.name !== me.name) return;

                    function addLoadingAnimation() {
                        $('body').append('<div class="cff-processing-form"></div>');
                    } // End addLoadingAnimation.

                    function removeLoadingAnimation() {
                        $('.cff-processing-form').remove();
                    } // End removeLoadingAnimation.

                    // Remove existing modal if present
                    function closeModal(evt) {
                        if (!evt || evt.key === 'Escape' || evt.keyCode === 27) {
                            // If no fields: the form was not imported, there was an error, or the imported form was empty
                            // In this case, remove the field.
                            if ( ! ( 'fields' in me ) || me.fields.length === 0 ) {
                                me.fBuild.removeItem( me.index );
                            }
                            removeLoadingAnimation();
                            $('#' + overlayId).remove();
                            $('#' + modalId).remove();
                        }
                    } // End closeModal

                    function renderCFFFormsModal(forms, isEmpty) {
                        var $overlay = $('<div>', {
                            id: overlayId,
                            css: {
                                position: 'fixed',
                                left: 0,
                                top: 0,
                                width: '100%',
                                height: '100%',
                                'z-index': 100000,
                                'background-color': 'rgba(0,0,0,0.6)'
                            }
                        });

                        var $modal = $('<div>', {
                            id: modalId,
                            css: {
                                position: 'fixed',
                                left: '50%',
                                top: '50%',
                                transform: 'translate(-50%, -50%)',
                                'background-color': '#fff',
                                'border-radius': '8px',
                                'box-shadow': '0 8px 30px rgba(0,0,0,0.3)',
                                'padding': '10px 20px 20px 20px',
                                'width': '90%',
                                'max-width': '600px',
                                'max-height': '80%',
                                'overflow-y': 'auto',
                                'box-sizing': 'border-box',
                                'z-index': 100001
                            }
                        });

                        var $close = $('<button>', {
                            class: 'button-secondary',
                            text: 'x',
                            title: cpcff_form_widget_close_title,
                            css: {
                                'font-size': '24px',
                                'background': 'transparent',
                                'border': 0,
                                'cursor': 'pointer',
                                'line-height': '1',
                                'min-height': '18px',
                                'padding': '0 5px',
								'box-shadow' : 'none'
                            },
                            click: function () {
                                closeModal();
                            }
                        });

                        var $content = $('<div>').css({ 'margin-top': '10px' });

                        if (!isEmpty && Array.isArray(forms) && forms.length > 0) {
                            var $row = $('<div>').css({ display: 'flex', gap: '10px', 'align-items': 'center', 'margin': '12px 0' });

                            var $categorySelect = $('<select>', { id: 'cpcff-category-select', css: { 'min-width': '150px' } });
                            $categorySelect.append($('<option>', { value: '', text: cpcff_form_widget_all_forms }));

                            var $formSelect = $('<select>', { id: 'cpcff-form-select', css: { 'flex-grow': 1, 'min-width': 0, 'flex-basis': 0 } });
                            $formSelect.append($('<option>', { value: '', text: cpcff_form_widget_select_form_to_import }));

							var categories = {};

                            forms.forEach(function (form) {
                                if (form && typeof form.id !== 'undefined' && typeof form.name !== 'undefined') {
									if ('category' in form && form.category !== '') categories[form.category] = 1;
                                    $formSelect.append($('<option>', {
                                        value: form.id,
                                        text: '(' + form.id + ') ' + form.name,
										'data-category': form?.category || ''
                                    }));
                                }
                            });
							categories = Object.keys(categories).sort();
							categories.forEach(function(category) {
								$categorySelect.append($('<option>', {
									value: category,
									text: category
								}));
							});

							// Filter form options based on selected category
							$categorySelect.on('change', function() {
								var selectedCategory = $(this).val();
								var currentlySelectedId = $formSelect.val();
								var selectedFormStillExists = false;

								// Clear form options except the placeholder
								$formSelect.find('option:not(:first)').remove();

								// Rebuild options based on selected category
								forms.forEach(function (form) {
									if (form && typeof form.id !== 'undefined' && typeof form.name !== 'undefined') {
										// Show all forms if "All forms" is selected, or filter by category
										if (selectedCategory === '' || (form.category || '') === selectedCategory) {
											$formSelect.append($('<option>', {
												value: form.id,
												text: '(' + form.id + ') ' + form.name,
												'data-category': form?.category || ''
											}));
											// Check if the currently selected form still exists in the filtered list
											if (form.id == currentlySelectedId) {
												selectedFormStillExists = true;
											}
										}
									}
								});

								// Keep the selection if it still exists in the filtered list, otherwise reset
								if (selectedFormStillExists && currentlySelectedId) {
									$formSelect.val(currentlySelectedId);
								} else {
									$formSelect.val('');
								}
							});

                            var $button = $('<button>', {
                                class: 'button-primary',
                                text: cpcff_form_widget_import_form_button,
                                css: {
                                    'white-space': 'nowrap'
                                },
                                click: function (e) {
                                    e.preventDefault();
                                    var selectedId = $formSelect.val();
                                    if (!selectedId) {
                                        alert(cpcff_form_widget_select_form_alert);
                                        return;
                                    }
                                    addLoadingAnimation();
                                    me._developerNotes = cpcff_form_widget_imported_form_prefix + $formSelect.find(':selected').text();
                                    getFormStructure(selectedId);
                                }
                            });

                            $row.append($categorySelect, $formSelect, $button);
                            $content.append($row);
                        } else {
                            $content.append($('<p>', {
                                text: cpcff_form_widget_no_forms_available,
                                css: { margin: '12px 0', color: '#333' }
                            }));
                        }

                        $modal.append($('<div style="display:flex;align-items:center;gap:10px"><span style="font-weight:600;flex-grow:1">Form insertion dialog</span></div>').append($close), $content);
                        $('body').off('keydown', closeModal).on('keydown', closeModal).append($overlay, $modal);
                    }; // End renderCFFFormsModal

                    function getFormStructure(form_id) {
                        $.post(cpcff_form_widget_url, {
                            action: 'cpcff_get_form_fields',
                            form_id: form_id,
                            exclude_form: $('[name="cp_calculatedfieldsf_id"]').val() ?? 0
                        }, function (response) {
                            removeLoadingAnimation();
                            if (response.success && typeof response.data !== 'undefined') {
                                var structure = response.data;
                                if (typeof structure === 'string') {
                                    structure = JSON.parse(structure);
                                }
                                if (
									Object.prototype.toString.call(structure) === '[object Object]' ||
									(Array.isArray(structure) && structure.length)
								) {
                                    updateFormStructure(structure);
                                    closeModal();
                                } else {
									alert(cpcff_form_widget_form_empty_or_inaccessible);
								}
                            }
                        }, 'json').fail(function () {
                            removeLoadingAnimation();
                            alert(cpcff_form_widget_failed_get_structure);
                        });
                    }; // End getFormStructure

                    function updateFormStructure( form_structure ) {
                        function recursive_replacement(src, regexp, replacement) {
                            if (typeof src === 'string') {
                                return src.replace(regexp, replacement);
                            } else if (Array.isArray(src) || typeof src === 'object' && src !== null) {
                                for (let key in src) {
                                    src[key] = recursive_replacement(src[key], regexp, replacement);
                                }
                            }
                            return src;
                        }; // End recursive_replacement

                        let fieldsIndex = me.fBuild.getFieldsIndex();
                        let items = me.fBuild.getItems();

                        // Get next index and field name number.
                        let k = items.length;
                        let n = me.fBuild.getLastFieldNameNumber();

                        // Get max field name number between current and imported form structure.
                        for (var i in form_structure) if (/fieldname/.test(i)) n = Math.max(parseInt(i.replace(/fieldname/g, "")), n);

                        n++;
                        for (let i in form_structure) {
                            let new_name = 'fieldname' + n;
                            let current_name = i;
                            let regexp = new RegExp('\\b' + current_name + '\\b', 'ig');

                            for (let j in form_structure) {
                                form_structure[j] = recursive_replacement(form_structure[j], regexp, new_name);
                            }

                            form_structure[current_name]['index'] = k;
                            fieldsIndex[new_name] = k;
                            k++;
                            n++;
                        }

                        // Replace the fields names, assign parents, and updates the fields list property in the form inserted.
						let remove_from_parents = [];
                        for (let i in form_structure) {
                            let ftype = form_structure[i]['ftype'];
                            if (!(ftype in $.fbuilder.controls) || ftype == 'fPageBreak') {
								if ( 'name' in form_structure[i] ) remove_from_parents.push( form_structure[i]['name'] );
								continue;
							}
                            let obj = new $.fbuilder.controls[ftype]();
                            obj = $.extend(true, {}, obj, form_structure[i]);
                            obj.fBuild = me.fBuild;

                            if ( ! ('parent' in obj) || obj.parent == '') {
                                obj.parent = me.name;
                                if( ! ( 'fields' in me ) || ! Array.isArray(me.fields) ) me.fields = [];
                                me.fields.push(obj.name);
                            }
                            items.push( obj );
                        }

						// Remove invalid controls from parents.
						for ( let i in remove_from_parents ) {
							for ( let j in items ) {
								if ( 'fields' in items[j] && Array.isArray( items[j]['fields'] ) ) {
									items[j]['fields'] = items[j]['fields'].filter(item => item !== remove_from_parents[i]);
								}
							}
						}

                        $.fbuilder.reloadItems(); // Reload the builder with the new items.
                        me.fBuild.saveData("form_structure"); // Save the form structure.
                    }; // End updateFormStructure

                    // Get forms.
                    if (typeof cpcff_form_widget_url == 'undefined') return;

                    addLoadingAnimation();

                    $.post(cpcff_form_widget_url, {
                        action: 'cpcff_get_forms',
                        exclude_form: $('[name="cp_calculatedfieldsf_id"]').val() ?? 0
                    }, function (response) {
                        removeLoadingAnimation();
                        if (response.success && Array.isArray(response.data)) {
                            renderCFFFormsModal(response.data);
                        } else {
                            renderCFFFormsModal([], true);
                        }
                    }, 'json').fail(function () {
                        renderCFFFormsModal([], true);
                        removeLoadingAnimation();
                    });
                }); // End cff-item-added event.

            }

	});