	$.fbuilder.controls['fcontainer'] = function(){};
	$.fbuilder.controls['fcontainer'].prototype = {
		fields:[],
		columns:1,
		align:"top",
		rearrange: 0,
		after_show: function(e)
			{
				var e  = e || $('#'+this.name), f,
					to_ignore = 0; // Ignores the RecordSet DS and Hidden fields.
                for(var i = 0, h = this.fields.length; i < h; i++)
				{
					let flag = true;
					f = $('.fields.'+this.fields[i]+this.form_identifier);
					if( f.hasClass('cff-hidden-field') ) { to_ignore++; }
					f = f.detach();
					f.addClass('column'+this.columns);
					if(this.columns > 1)
					{
						if( ( i - to_ignore ) % this.columns == 0 && ! this.rearrange ) {
							f.css('clear', 'left');
							f.appendTo(e);
							if ( i - to_ignore && this.align=="bottom" ) f.before('<div class="cff-row-breaker"></div>');
							flag = false;
						}
					}
					if ( flag ) f.appendTo(e);
				}
			},
		showHideDep:function(toShow, toHide, hiddenByContainer, interval)
			{
                if(typeof hiddenByContainer == 'undefined') hiddenByContainer = {};
				var me = this,
					isHidden = (typeof toHide[me.name] != 'undefined' || typeof hiddenByContainer[me.name] != 'undefined'),
					fId,
					result = [];

				for(var i = 0, h = me.fields.length; i < h; i++)
				{
					if(!/fieldname/i.test(me.fields[i])) continue;
					fId = me.fields[i]+me.form_identifier;
					if(isHidden)
					{
						if(typeof hiddenByContainer[fId] == 'undefined') hiddenByContainer[fId] = {};
						if(typeof hiddenByContainer[fId][me.name] == 'undefined')
						{
							hiddenByContainer[fId][me.name] = {};

							if(typeof toHide[fId] == 'undefined')
							{
								$('.'+fId+' [id*="'+fId+'"],.'+fId).closest('.fields').addClass('ignorefield').hide();
								$('.'+fId+' [id*="'+fId+'"]:not(.ignore)').addClass('ignore').trigger('add-ignore');
								result.push(fId);
							}
						}
					}
					else
					{
						if(typeof hiddenByContainer[fId] != 'undefined')
						{
							delete hiddenByContainer[fId][me.name];
							if($.isEmptyObject(hiddenByContainer[fId]))
							{
								delete hiddenByContainer[fId];
								if(typeof toHide[fId] == 'undefined')
								{
									$('.'+fId+' [id*="'+fId+'"],.'+fId).closest('.fields').removeClass('ignorefield').fadeIn(interval || 0);
									$('.'+fId+' [id*="'+fId+'"].ignore').removeClass('ignore').trigger('remove-ignore');
									result.push(fId);
								}
							}
						}
					}
				}
				return result;
			}
	};