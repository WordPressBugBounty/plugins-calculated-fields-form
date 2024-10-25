/*
* url.js v0.1
* By: CALCULATED FIELD PROGRAMMERS
* Includes operations to interact with the URLs and parameters
* Copyright 2020 CODEPEOPLE
*/

;(function(root){
	var lib = {
		records: {}
	};

	/*** PRIVATE FUNCTIONS ***/

	/*** PUBLIC FUNCTIONS ***/

	lib.cff_url_version = '0.1';

	// getReferrer()
	lib.getReferrer = lib.getreferrer = lib.GETREFERRER = function(){
		return document.referrer || null;
	};

	// generateURL()
	lib.generateURL = lib.generateurl = lib.GENERATEURL = function(url, params, hash){
		var urlComponents = url.split('#'), queryString = '', connector = '';
		if(typeof params == 'object' && params)
		{
			connector = (url.indexOf('?') == -1) ? '?' : '&';
			queryString = jQuery.param(params);
		}
		if(typeof hash == 'string') urlComponents[1] = hash;
		urlComponents[0] += connector+queryString;
		return urlComponents.join('#');
	};

	// redirectToURL()
	lib.redirectToURL = lib.redirecttourl = lib.REDIRECTTOURL = function(url, obj, target){
		let $ = fbuilderjQuery,
			a = $('<a></a>');

		target = target || '_self';
		url += ( obj ? (url.indexOf('?') === -1 ? '?' : '&')+$.param(obj) : '');

		a.attr({'href': url, 'target': target});
		a.appendTo('body');
		a[0].click();
		a.remove();
	};

	// getURL()
	lib.getURL = lib.geturl = lib.GETURL = function(){
		return document.location.href;
	};

	// getURLProtocol()
	lib.getURLProtocol = lib.geturlprotocol = lib.GETURLPROTOCOL = function(){
		return document.location.protocol.toLowerCase();
	};

	// getBaseURL()
	lib.getBaseURL = lib.getbaseurl = lib.GETBASEURL = function(){
		return window.location.protocol + '//' + window.location.host + '/';
	};

	// getURLHash()
	lib.getURLHash = lib.geturlhash = lib.GETURLHASH = function(nohash){
		return window.location.hash.replace((nohash) ? /^#/ : '', '');
	};

	// getURLPath()
	lib.getURLPath = lib.geturlpath = lib.GETURLPATH = function(noslash){
		return window.location.pathname.replace((noslash) ? new RegExp('^\/', 'g') : '', '').replace((noslash) ? new RegExp('\/$','g') : '', '');
	};

	// getURLParameters(url) the url is optional
	lib.getURLParameters = lib.geturlparameters = lib.GETURLPARAMETERS = function(url){
		var qs = url ? url.split('?')[1] : window.location.search.slice(1),
			obj = {};

		function aux (v, to_lower) {
			to_lower = to_lower || false;
			if ( Array.isArray(v) ) {
				for ( let i in v ) {
					v[i] = aux(v[i], to_lower);
				}
			} else if ( typeof v == 'string') return (to_lower ? decodeURIComponent(v).toLowerCase() : decodeURIComponent(v)).replace(/\+/g, ' ');
			return v;
		}

		if(qs)
		{
			qs = qs.split('#')[0];
			var arr = qs.split('&');
			for (var i = 0; i < arr.length; i++)
			{
				var a = arr[i].split('='),
					paramName = a[0],
					paramValue = typeof (a[1]) === 'undefined' ? true : a[1];

				paramName = aux(paramName, true);
				paramValue = aux(paramValue);

				if (paramName.match(/\[(\d+)?\]$/))
				{
					var key = paramName.replace(/\[(\d+)?\]/, '');
					if (!obj[key]) obj[key] = [];
					if (paramName.match(/\[\d+\]$/))
					{
						var index = /\[(\d+)\]/.exec(paramName)[1];
						obj[key][index] = paramValue;
					}
					else
					{
						obj[key].push(paramValue);
					}
				}
				else
				{
					if (!obj[paramName])
					{
						obj[paramName] = paramValue;
					}
					else if (obj[paramName] && typeof obj[paramName] === 'string')
					{
						obj[paramName] = [obj[paramName]];
						obj[paramName].push(paramValue);
					}
					else
					{
						obj[paramName].push(paramValue);
					}
				}
			}
		}
		return obj;
	};

	// getURLParameter(paramName, defaultValue) defaultValue is optional
	lib.getURLParameter = lib.geturlparameter = lib.GETURLPARAMETER = function(paramName, defaultValue){
		var parameters = lib.getURLParameters();
		paramName = paramName.toLowerCase();
		if(paramName in parameters) return parameters[paramName];
		else if(typeof defaultValue != 'undefined') return defaultValue;
		else return null;
	}

	root.CF_URL = lib;

})(this);