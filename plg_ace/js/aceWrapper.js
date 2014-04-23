
//fix strange joomla mootools behavior
if (console) {
	console.log(this.MooTools);
}

if (this.MooTools.build=='ab8ea8824dc3b24b6666867a2c4ed58ebb762cf0') {
	delete Function.prototype.bind;

	Function.implement({

		/*<!ES5-bind>*/
		bind: function(that){
			var self = this,
				args = arguments.length > 1 ? Array.slice(arguments, 1) : null,
				F = function(){};

			var bound = function(){
				var context = that, length = arguments.length;
				if (this instanceof bound){
					F.prototype = self.prototype;
					context = new F;
				}
				var result = (!args && !length)
					? self.call(context)
					: self.apply(context, args && length ? args.concat(Array.slice(arguments)) : args || arguments);
				return context == that ? result : context;
			};
			return bound;
		},
		/*</!ES5-bind>*/
	});
}


var AceWrapper = new Class({
	Implements: [Options],

	aceEditor: null,

	options: {
		editorid: "editor",
		theme: "ace/theme/dawn",
		mode: "ace/mode/html",
		showPrintMargin: false,
		useWrapMode: true,
		wrapModeButtonId: 'softwrap_',
		wrapmode: 'off',
	},

	// do the initialization
	initialize: function(options) {

	    this.setOptions(options);
	    this.setOptions(this.loadFromCookie());

	    this.aceEditor = ace.edit(this.options.editorid);

	    this.aceEditor.setTheme(this.options.theme);
	    this.aceEditor.setShowPrintMargin(this.options.showPrintMargin);
	    this.aceEditor.getSession().setMode(this.options.mode);
	    this.aceEditor.getSession().setUseWrapMode(this.options.useWrapMode);

	    $(this.options.wrapModeButtonId).addEvent('click',this.wrapModeClick.bind(this));
	    $(this.options.wrapModeButtonId).set('data-wrap',this.options.wrapmode);

	    this.wrapModeClick();
	},

	wrapModeClick: function(e) {
		var button = $(this.options.wrapModeButtonId);
		var value = button.get('data-wrap');
		var session = this.aceEditor.session;
    	var renderer = this.aceEditor.renderer;
	    switch (value) {
	        case "off":
	            session.setUseWrapMode(false);
	            renderer.setPrintMarginColumn(80);
	            button.set('data-wrap','free');
	            this.options.wrapmode='off';
	            break;
	        case "free":
	            session.setUseWrapMode(true);
	            session.setWrapLimitRange(null, null);
	            renderer.setPrintMarginColumn(80);
	            button.set('data-wrap','100');
	            this.options.wrapmode='free';
	            break;
	        default:
	            session.setUseWrapMode(true);
	            var col = parseInt(value, 10);
	            session.setWrapLimitRange(col, col);
	            renderer.setPrintMarginColumn(col);
	            button.set('data-wrap','off');
	            this.options.wrapmode='100';
	    }
	    this.saveToCookie();

	},

	saveToCookie: function() {
		var options = {
			wrapmode: this.options.wrapmode,
		}
		var myCookie = Cookie.write('plg_editors_ace_wrapper', JSON.encode(options));
	},

	loadFromCookie: function() {
		var myCookie = Cookie.read('plg_editors_ace_wrapper');
		if (myCookie) {
			try{
		   		return JSON.decode(myCookie);
		   	} catch(e) {}
		}
		return {};
	},

});


