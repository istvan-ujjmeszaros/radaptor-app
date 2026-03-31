/************************************************************************************************
* Floatbox v3.24
* December 01, 2008
*
* Copyright (C) 2008 Byron McGregor
* Website: http://randomous.com/tools/floatbox/
* License: Creative Commons Attribution 3.0 License (http://creativecommons.org/licenses/by/3.0/)
* This comment block must be retained in all deployments and distributions
*************************************************************************************************/

function Floatbox() {
this.defaultOptions = {

/***** BEGIN OPTIONS CONFIGURATION *****/
// see docs/options.html for detailed descriptions

/*** <General Options> ***/
theme:          'auto'    ,// 'auto'|'black'|'white'|'blue'|'yellow'|'red'|'custom'
padding:         12       ,// pixels
panelPadding:    8        ,// pixels
outerBorder:     4        ,// pixels
innerBorder:     1        ,// pixels
overlayOpacity:  55       ,// 0-100
controlOpacity:  60       ,// 0-100
autoSizeImages:  true     ,// true|false
autoSizeOther:   false    ,// true|false
resizeImages:    true     ,// true|false
resizeOther:     false    ,// true|false
resizeTool:     'cursor'  ,// 'cursor'|'topleft'|'both'
infoPos:        'bl'      ,// 'tl'|'tc'|'tr'|'bl'|'bc'|'br'
controlPos:     'br'      ,// 'tl'|'tr'|'bl'|'br'
boxLeft:        'auto'    ,// 'auto'|pixels|'[-]xx%'
boxTop:         'auto'    ,// 'auto'|pixels|'[-]xx%'
shadowType:     'drop'    ,// 'drop'|'halo'|'none'
shadowSize:      12       ,// 8|12|16|24
enableDrag:      true     ,// true|false
showCaption:     true     ,// true|false
showItemNumber:  true     ,// true|false
showClose:       true     ,// true|false
hideFlash:       true     ,// true|false
hideJava:        true     ,// true|false
disableScroll:   false    ,// true|false
autoGallery:     false    ,// true|false
preloadAll:      true     ,// true|false
enableCookies:   false    ,// true|false
cookieScope:    'site'    ,// 'site'|'folder'
language:       'auto'    ,// 'auto'|'en'|... (see the languages folder)
graphicsType:   'auto'    ,// 'auto'|'international'|'english'
urlGraphics:    '/floatbox/graphics/'   ,// change this if you install in another folder
urlLanguages:   '/floatbox/languages/'  ,// change this if you install in another folder
/*** </General Options> ***/

/*** <Navigation Options> ***/
navType:           'both'    ,// 'overlay'|'button'|'both'|'none'
navOverlayWidth:    35       ,// 0-50
navOverlayPos:      30       ,// 0-100
showNavOverlay:    'never'   ,// 'always'|'once'|'never'
showHints:         'once'    ,// 'always'|'once'|'never'
enableWrap:         true     ,// true|false
enableKeyboardNav:  true     ,// true|false
outsideClickCloses: true     ,// true|false
numIndexLinks:      0        ,// number, -1 = no limit
indexLinksPanel:   'control' ,// 'info'|'control'
showIndexThumbs:    true     ,// true|false
/*** </Navigation Options> ***/

/*** <Animation Options> ***/
doAnimations:         true   ,// true|false
resizeDuration:       3.5    ,// 0-10
imageFadeDuration:    3.5    ,// 0-10
overlayFadeDuration:  4      ,// 0-10
splitResize:         'no'    ,// 'no'|'auto'|'wh'|'hw'
startAtClick:         true   ,// true|false
zoomImageStart:       true   ,// true|false
liveImageResize:      false  ,// true|false
/*** </Animation Options> ***/

/*** <Slideshow Options> ***/
slideInterval:  4.5    ,// seconds
endTask:       'exit'  ,// 'stop'|'exit'|'loop'
showPlayPause:  true   ,// true|false
startPaused:    false  ,// true|false
pauseOnResize:  true   ,// true|false
pauseOnPrev:    true   ,// true|false
pauseOnNext:    false   // true|false
/*** </Slideshow Options> ***/
};

/*** <New Child Window Options> ***/
// Will inherit from the primary floatbox options unless overridden here
// Add any you like
this.childOptions = {
overlayOpacity:      45,
resizeDuration:       3,
imageFadeDuration:    3,
overlayFadeDuration:  0
};
/*** </New Child Window Options> ***/

/***** END OPTIONS CONFIGURATION *****/
this.init();
}

// Prototype Functions
// init()
// tagAnchors(baseEl)
// tagOneAnchor(anchor)
// fileType(href)
// preloadImages(href, chain)
// start(anchor)
// buildItemArray(a)
// getOptions()
// parseOptionString(str)
// setOptions(pairs)
// buildDOM()
// newNode(nodeType, id, parentNode, title)
// addEventHandlers()
// keydownHandler(e)
// dragonDrop()
// initState()
// hideElements(type, thisWindow)
// getAnchorPos(anchor, useThumb)
// getThumb(anchor)
// initPanels()
// fetchContent(callback, phase)
// updatePanels()
// calcSize(fit, pass)
// setPosition(el, position)
// collapse(callback, phase)
// restore(callback, phase)
// setSize(order)
// showContent(phase)
// objectHTML(href, type, width, height)
// newContent(index)
// end(all)
// zoomIn(phase)
// zoomOut(phase)
// setPause(pause)
// fadeOpacity(el, opacity, duration, callback)
// stepFade(el, thisOp, finishOp, incr, fadeIn, callback)
// resizeGroup(arr, callback)
// stepResize(increment, rate, arr, callback)
// getDisplaySize()
// getDisplayWidth()
// getDisplayHeight()
// getScroll(win)
// getLeftTop(el, local)
// getLayout(el)
// getStyle(el, prop, win)
// clearTimeout(key)
// stretchOverlay()
// encodeHTML(str)
// decodeHTML(str)
// getXMLHttpRequest()
// setInnerHTML(el, strHTML)
// printContents(el, style)
// loadAnchor(href, rev, title)
// goBack()
// resize(width, height)

Floatbox.prototype = {
// things that could be configurable options but are here as constants instead
	panelGap: 22,  // gap between infoPanel and controlPanel
	infoLinkGap: 16,  // between infoLink, printLink and itemNumber
	showHintsTime: 1600,  // minimum milliseconds tooltip hints must be shown before they can be cleared
	zoomPopBorder: 1,  // fbZoomDiv and popup thumbnail border width
	controlSpacing: 8,  // gap between control panel gadgets for English
	minInfoWidth: 80,  // if info panel width is less than this, don't display it
	minIndexWidth: 120,  // minimum width for indexLinks
	ctrlJump: 5,  // page jump size when ctrl+arrowKey is pressed
	slowLoadDelay: 750,  // msecs to wait for initial content before showing the slow-load gif display
	loaderDelay: 200,  // msecs to wait when resizing or swapping before showing the loader gif
	autoSizeSpace: 4,  // pixels to leave free on each side of autosized items
	initialSize: 120,  // width and height of the initial box for split resizing or a slow load
	defaultWidth: '85%',  // if item width can't be determined
	defaultHeight: '82%',  // if item height can't be determined

//************/
// init()
// Constructor
//************/
init: function() {
// pick up options to be used during intialization
	this.setOptions(this.defaultOptions);
	if (typeof fbPageOptions === 'object') this.setOptions(fbPageOptions);
	this.setOptions(this.parseOptionString(location.search.substring(1)));
// 'global' vars (per box)
	this.items = [];
	this.nodeNames = [];
	this.hiddenEls = [];
	this.timeouts = {};
	this.pos = {};
// paths to graphics resources we'll be using
	var path = this.urlGraphics;  // from options
	this.slowZoomImg = path + 'loading_white.gif';  // overlays the zoomDiv image
	this.slowLoadImg = path + 'loading_black.gif';  // appears above the clicked anchor
	this.iframeSrc = path + 'loading_iframe.html';  // initial iframe content until the real fetch happens
	this.resizeUpCursor = path + 'magnify_plus.cur';  // custom cursor appears above the image
	this.resizeDownCursor = path + 'magnify_minus.cur';
	this.notFoundImg = path + '404.jpg';  // show this image if the href'd image can't be found
// browser detects
	var agent = navigator.userAgent,
		version = navigator.appVersion;
	this.mac = version.indexOf('Macintosh') !== -1;
	if (window.opera) {
		this.opera = true;
		this.operaOld = parseFloat(version) < 9.5;  // less than v9.5
		this.operaMac = this.mac;
	} else if (document.all) {
		this.ie = true;
		this.ieOld = parseInt(version.substr(version.indexOf('MSIE') + 5), 10) < 7;  // less than v7.0
		this.ie8b2 = version.indexOf('MSIE 8.0') !== -1 && navigator.appMinorVersion === 'beta 2';  // ie8beta detect
		this.ieXP = parseInt(version.substr(version.indexOf('Windows NT') + 11), 10) < 6;  // less than Vista (6.0)
	} else if (agent.indexOf('Firefox') !== -1) {
		this.ff = true;
		this.ffOld = parseInt(agent.substr(agent.indexOf('Firefox') + 8), 10) < 3;  // less than v3
		this.ffNew = !this.ffOld;
		this.ffMac = this.mac;
	} else if (version.indexOf('WebKit') !== -1) {
		this.webkit = true;
		this.webkitNew = parseInt(version.substr(version.indexOf('WebKit') + 7), 10) >= 500;  // chrome, safari 3.0+
		this.webkitOld = !this.webkitNew;
		this.webkitMac = this.mac;
	}
// child detection
	this.isChild = !!(self.fb && self.fb.fbBox);
	if (!this.isChild) {
		this.fbParent = this.lastChild = this;
// things that are present only on the primary floatbox
		this.anchors = [];
		this.children = [];
		this.preloads = {};
		this.preloads.count = 0;
		this.html = document.documentElement;
		this.bod = document.body || document.getElementsByTagName('body')[0];
		this.rtl = this.getStyle(this.bod, 'direction') === 'rtl' || this.getStyle(this.html, 'direction') === 'rtl';
		this.xhr = this.getXMLHttpRequest();
		// default strings in case xhr doesn't work
		this.strings = {
			hintClose: 'Exit (key: Esc)',
			hintPrev: 'Previous (key: <--)',
			hintNext: 'Next (key: -->)',
			hintPlay: 'Play (key: spacebar)',
			hintPause: 'Pause (key: spacebar)',
			hintResize: 'Resize (key: Tab)',
			imgCount: 'Image %1 of %2',
			nonImgCount: 'Page %1 of %2',
			mixedCount: '(%1 of %2)',
			infoText: 'Info...',
			printText: 'Print...'
		};
  	} else {
// this is a secondary floatbox
		// set parent, lastChild, etc.
		this.fbParent = fb.lastChild;
		fb.lastChild = this;
		fb.children.push(this);  // keep track of the kids (add here, remove in end())
		// stop fbParent's slideshow
		if (this.fbParent.isSlideshow) this.fbParent.setPause(true);
		// point some things to the primary objects
		this.anchors = fb.anchors;
		this.children = fb.children;
		this.html = fb.html;
		this.bod = fb.bod;
		this.rtl = fb.rtl;
		this.xhr = fb.xhr;
		this.strings = fb.strings;
	}
// set international preferences
	this.browserLanguage = (navigator.language || navigator.userLanguage || navigator.systemLanguage || navigator.browserLanguage || 'en').substring(0, 2);
	if (!this.isChild) {
		// localized strings
		var lang = this.language === 'auto' ? this.browserLanguage : this.language;
		if (this.xhr) {
			// get the language definition from an ajax/json response
			var that = this;
			this.xhr.getResponse(this.urlLanguages + lang + '.json', function(xhr) {
				if ((xhr.status === 200 || xhr.status === 203 || xhr.status === 304) && xhr.responseText) {
					var ltArrow = String.fromCharCode(8592),
						rtArrow = String.fromCharCode(8594),
						text = xhr.responseText;
					if (that.ieXP) {  // IE on XP doesn't render unicode without language packs installed
						text = text.replace(ltArrow, '<--').replace(rtArrow, '-->');
					}
					try {
						var obj = eval('(' + text + ')');
						if (obj && obj.hintClose) that.strings = obj;
					} catch(e) {}
				}
				if (that.rtl) {
					if (!/^(ar|he)$/.test(that.language)) {
						that.strings.infoText = that.strings.infoText.replace('...', '');
						that.strings.printText = that.strings.printText.replace('...', '');
					}
					that.strings.hintPrev = that.strings.hintPrev.replace(ltArrow, rtArrow).replace('-->', '<--');
					that.strings.hintNext = that.strings.hintNext.replace(rtArrow, ltArrow).replace('<--', '-->');
					var t = that.strings.hintPrev;
					that.strings.hintPrev = that.strings.hintNext;
					that.strings.hintNext = t;
				}
			});
		}
	}
	// widget graphics
	if (!this.rtl && (this.graphicsType.toLowerCase() === 'english' || (this.graphicsType === 'auto' && this.browserLanguage === 'en'))) {
		this.offPos = 'top left';
		this.onPos = 'bottom left';
	} else {
		this.offPos = 'top right';
		this.onPos = 'bottom right';
		this.controlSpacing = 0;  // the graphics-only widgets are narrower than the ones with text included
	}
// z-index values for floatbox elements
	this.zIndex = {
		base: 90000 + 10*this.children.length,
		fbOverlay: 1,
		fbBox: 2,
		fbCanvas: 3,
		fbMainDiv: 4,
		fbLeftNav: 5,
		fbRightNav: 5,
		fbOverlayPrev: 6,
		fbOverlayNext: 6,
		fbResizer: 7,
		fbZoomDiv: 8,
		fbInfoPanel: 8,  // popups in the caption show on top
		fbControlPanel: 8  // index link popups show on top
	};
// look for autoStart request in the url query string
	var match = /\bautoStart=(.+?)(?:&|$)/i.exec(location.search);
	this.autoHref = match ? match[1] : false;
},  // end init

//**************************************/
// tagAnchors()
// Look for and 'tag' floatboxed anchors
//**************************************/
tagAnchors: function(baseEl) {
	var that = fb.lastChild,
		doOutline = this.ieOld && /^fb/.test(baseEl.id);  // ie6 css bug ignores outline:none
	function tag(tagName) {
		var elements = baseEl.getElementsByTagName(tagName);
		for (var i = 0, len = elements.length; i < len; i++) {
			var el = elements[i],
				revOptions = that.parseOptionString(el.getAttribute('rev')),
				href = revOptions.href || el.getAttribute('href');
			// setup rel attributes on image links if autoGallery is requested
			if (that.autoGallery && that.fileType(href) === 'img' && el.getAttribute('rel') !== 'nofloatbox') {
				el.setAttribute('rel', 'floatbox.autoGallery');
				if (that.autoTitle && !el.getAttribute('title')) el.setAttribute('title', that.autoTitle);
			}
			if (doOutline) el.setAttribute('hideFocus', 'true');  // ie6 css bug ignores outline:none
			that.tagOneAnchor(el, revOptions);
		}
	}
	tag('a');  // <a> elements
	tag('area');  // image map <area> elements
},  // end tagAnchors

//*******************************************************************************/
// tagOneAnchor()
// Set anchor action, determine type, add to anchor array, and look for autoStart
// Param can be a real anchor node or an associative object describing attributes
//*******************************************************************************/
tagOneAnchor: function(anchor, revOptions) {
	var that = this,
		isAnchor = !!anchor.getAttribute;
	if (isAnchor) {
		// build an object out of the anchor's attributes
		var a = {
			rel: anchor.getAttribute('rel'),
			rev: anchor.getAttribute('rev'),
			title: anchor.getAttribute('title'),
			anchor: anchor,  // save the anchor and thumbnail for use in popups and animations
			thumb: this.getThumb(anchor)
		};
		// see if this anchor has a popup thumbnail with it
		var match;
		if (a.thumb && (match = /(?:^|\s)fbPop(up|down)(?:\s|$)/i.exec(anchor.className))) {
			var up = (match[1] === 'up');
			a.popup = true;
			a.thumb.style.borderWidth = this.zoomPopBorder + 'px';
			// move the popup thumb into place on mouseover
			anchor.onmouseover = function () {
				a.thumb.style.display = 'none';  // exclude thumbnail from measurments
				var aPos = that.getLeftTop(this, true),  // local to current offsetParent
					aLeft = aPos.left,
					aTop = aPos.top;
				aPos = that.getLayout(this);  // anchor's top page coordinates
				a.thumb.style.display = '';  // measurable
				// set popup to show just above the anchor
				var relLeft = (aPos.width - a.thumb.offsetWidth)/2,
					relTop = up ? 2 - a.thumb.offsetHeight : aPos.height,
					scroll = that.getScroll(),
					screenRight = scroll.left + that.getDisplayWidth();
				// if popup is offscreen, move it in
				var spill = aPos.left + relLeft + a.thumb.offsetWidth - screenRight;  // pixels past the right screen edge
				if (spill > 0) relLeft -= spill;
				var spill = aPos.left + relLeft - scroll.left;  // pixels before the left screen edge
				if (spill < 0) relLeft -= spill;
				if (up) {
					if (aPos.top + relTop < scroll.top) relTop = aPos.height;
				} else {
					if (aPos.top + relTop + a.thumb.offsetHeight > scroll.top + that.getDisplayHeight()) relTop = 2 - a.thumb.offsetHeight;
				}
				a.thumb.style.left = (aLeft + relLeft) + 'px';
				a.thumb.style.top = (aTop + relTop) + 'px';
			};
			// move the thumb off-screen on mouseout
			anchor.onmouseout = function () {
				a.thumb.style.left = '0';
				a.thumb.style.top = '-9999px';
			};
			// and off-screen for popup anchors that don't have a click action
			if (!anchor.onclick) anchor.onclick = anchor.onmouseout;
		}
	} else {
		var a = anchor;
	}
	// if one of the "pick me" strings is on the rel attribute...
	if (/^(floatbox|gallery|iframe|slideshow|lytebox|lyteshow|lyteframe|lightbox)/i.test(a.rel)) {
		// capture revOptions and href for each anchor
		a.revOptions = revOptions || this.parseOptionString(a.rev);
		a.href = a.revOptions.href || anchor.href || anchor.getAttribute('href');
		// each anchor record is associated with a particular box/level
		a.level = this.children.length + (fb.lastChild.fbBox && !a.revOptions.sameBox ? 1 : 0);
		// don't duplicate anchors on a refresh
		var a_i, i = this.anchors.length;
		while (i--) {
			a_i = this.anchors[i];
			// is this our anchor?
			if (a_i.href === a.href && a_i.rel === a.rel && a_i.rev === a.rev && a_i.title === a.title && a_i.level === a.level) {
				a_i.anchor = anchor;  // update anchor record in case it's been re-generated
				break;
			}
		}
		if (i === -1) {  // not already in the anchor array
			// determine item type
			a.type = a.revOptions.type || this.fileType(a.href);  // use file type if not specified in rev tag
			if (a.type === 'html') {  // is html inline or iframe?  (other types are already set from revOptions.type)
				a.type = 'iframe';  // default
				// see if it's an inline div request
				var match = /#(\w+)/.exec(a.href);
				if (match) {
					var doc = document;  // which doc do we look for this div in?
					if (a.anchor) {  // try the owner doc for this anchor
						doc = a.anchor.ownerDocument || a.anchor.document || doc;
					}
					if (doc === document && this.currentItem && this.currentItem.anchor) {  // or the owner doc for the currentItem (for links in the caption)
						doc = this.currentItem.anchor.ownerDocument || this.currentItem.anchor.document || doc;
					}
					var el = doc.getElementById(match[1]);
					if (el) {  // found the div
						a.type = 'inline';
						a.sourceEl = el;  // save it for later content fectching
					}
				}
			}
			this.anchors.push(a);  // add to the array of anchors that will be used by start()
			// look for autoStart request
			if (this.autoHref) {  // gathered from the query string in init()
				if (a.revOptions.showThis !== false && this.autoHref === a.href.substr(a.href.length - this.autoHref.length)) this.autoStart = a;
			} else if (a.revOptions.autoStart === true) {
				this.autoStart = a;
			} else if (a.revOptions.autoStart === 'once') {
				var match = /fbAutoShown=(.+?)(?:;|$)/.exec(document.cookie),
					val = match ? match[1] : '',
					href = escape(a.href);
				if (val.indexOf(href) === -1) {
					this.autoStart = a;
					document.cookie = 'fbAutoShown=' + val + href + '; path=/';
				}
			}
		}
		if (isAnchor) {  // tagged anchors do this
			anchor.onclick = function(e) {
				e = e || window.event;
				// IE might have to go looking in the frames collection for the event
				if (this.ie && !e) {
					var iframes = self.frames, i = iframes.length;
					while (i-- && !e) {
						try {
							if (typeof iframes[i].window === 'object') e = iframes[i].window.event;
						} catch(err) {}
					}
				}
				if (!(e && (e.ctrlKey || e.metaKey || e.shiftKey)) || a.revOptions.showThis === false || !/img|iframe/.test(a.type)) {
					fb.start(this);
					if (this.ie && e) e.returnValue = false;
					return false;
				}
			};
		}
	}
	return a;
},  // end tagOneAnchor

//*************************************/
// fileType()
// Returns type string for a given href
//*************************************/
fileType: function(href) {
	var s = href.toLowerCase(),
		i = s.indexOf('?');
	if (i !== -1) s = s.substr(0, i);  // strip querystring
	s = s.substr(s.lastIndexOf('.') + 1);  // extract extension
	if (/^(jpe?g|png|gif|bmp)$/.test(s)) return 'img';
	if (s === 'swf' || /^(http:)?\/\/(www.)?youtube.com\/v\//i.test(href)) return 'flash';
	if (/^(mov|mpe?g|movie)$/.test(s)) return 'quicktime';
	return 'html';
},  // fileType

//**************************************/
// preloadImages()
// Preload/cache floatboxed images
// Chains preloading if that's enabled
//**************************************/
preloadImages: function(href, chain) {
	// do all preloading with the primary floatbox (one queue)
	if (this !== fb) return fb.preloadImages(href, chain);
	// turn chaining on or off with the chain param
	if (typeof chain !== 'undefined') arguments.callee.chain = chain;
	// if no href passed and chaining is enabled, look for an image to preload
	if (!href && arguments.callee.chain && (this.preloadAll || !this.preloads.count)) {
		for (var i = 0, len = this.anchors.length; i < len; i++) {
			var a = this.anchors[i];
			if (a.type === 'img' && !this.preloads[a.href]) {
				href = a.href;  // not yet loaded - use this one
				break;
			}
		}
	}
	if (href) {  // from param or from the for loop
		if (this.preloads[href]) {  // already loaded?
			this.preloadImages();  // look for the next one to load
		} else {
			var img = this.preloads[href] = new Image();
			// chain the next image when this one completes or errors out
			img.onerror = function() {
				setTimeout(function() { fb.preloadImages(); }, 50);
				fb.preloads[href] = true;  // let the image object get garbage collected
			};
			img.onload = function() {
				fb.preloads.count++;
				this.onerror();
			};
			img.src = href;
		}
	}
},  // end preloadImages

//*******************************************************/
// start()
// Fired when user clicks on one of the activated anchors
//*******************************************************/
start: function(anchor) {
	// only the last (top-most) child box is allowed to start stuff
	if (this !== fb.lastChild) return fb.lastChild.start(anchor);
	var that = this;
	this.preloadImages('', false);  // suspend image preloading
	if (anchor.getAttribute) {  // param can be an anchor or an object with anchor-like named properties
		var a = {
			rel: anchor.getAttribute('rel'),
			rev: anchor.getAttribute('rev'),
			title: anchor.getAttribute('title')
		};
		a.revOptions = this.parseOptionString(a.rev);
		a.href = a.revOptions.href || anchor.href || anchor.getAttribute('href');
		anchor.blur();  // remove focus (especially for Opera 9.5+)
	} else {
		var a = anchor;
	}
	this.isRestart = !!this.fbBox;
	if (this.isRestart) {
		// new floatbox requested and we're already in one?
		if (!a.revOptions.sameBox) return new Floatbox().start(anchor);  // start in a new floatbox
		this.setOptions(a.revOptions);  // else pick up new options from this rev tag
	} else {
		this.clickedAnchor = anchor.getAttribute ? anchor : false;  // for start and end animations
	}
	a.level = this.children.length + (fb.lastChild.fbBox && !a.revOptions.sameBox ? 1 : 0);  // capture level
	this.itemsShown = 0;  // slideshow counter
	fb.previousAnchor = this.currentItem;  // save previous start anchor (for goBack and pulling defaulted native size)
	this.buildItemArray(a);  // get the array of anchors for this group
	if (!this.itemCount) return;  // bail if we didn't find any
	if (this.itemCount === 1 && this.fbNavControls) this.fbNavControls.style.display = 'none';  // mostly for if loadAnchor has interrupted a group of items
	self.focus();  // grab keyboard handler
	this.revOptions = a.revOptions;  // use clicked anchor's rev options (esp. if showThis=false)
	if (!this.isRestart) {  // if this is a new floatbox, build it
		this.getOptions();
		this.buildDOM();
		this.addEventHandlers();
		this.initState();
	}
	// fetch and show content
	this.collapse();  // sets up the loading gif
	this.updatePanels();  // do this first so it's ready for calcSize
	var fetchAndGo = function() {
		that.fetchContent(function() {
			that.clearTimeout('slowLoad');
			that.calcSize();
		} );
	};
	if (this.fbBox.style.visibility  || this.isRestart) {
		// we're zooming in or restarting, overlay will be shown by the zoom in function, slow load display, or is already there
		fetchAndGo();
	} else {  // normal start
		var offset = this.initialSize/2,
			size = { id: 'fbBox', left: that.pos.fbBox.left - offset, top: that.pos.fbBox.top - offset,
			width: that.initialSize, height: that.initialSize, borderWidth: that.outerBorder };
		if (this.splitResize) {  // show the initial small box for split dimension resizing
			var oncomplete = function() {
				that.setSize(fetchAndGo, size);
			};
		} else {  // set a timer to show the initial small box if content fetch is slow
			this.timeouts.slowLoad = setTimeout(function() {
				that.setSize(size);
			}, this.slowLoadDelay);
			var oncomplete = fetchAndGo;
		}
		// show overlay and carry on from there
		this.fadeOpacity(this.fbOverlay, this.overlayOpacity, this.overlayFadeDuration, oncomplete);
	}
},  // end start

//***************************************************/
// buildItemArray()
// Populate the item array with items from this group
//***************************************************/
buildItemArray: function(a) {
	this.itemCount = this.items.length = this.currentIndex = 0;
	this.justImages = true;
	this.hasImages = false;
	var isSingle = /^(floatbox|gallery|iframe|lytebox|lyteframe|lightbox)$/i.test(a.rel);  // standalone item
	for (var i = 0, len = this.anchors.length; i < len; i++) {
		var a_i = this.anchors[i];
		if (a_i.rel === a.rel && a_i.level === a.level) {  // if rel matches the one from the clicked anchor...
			if (a_i.revOptions.showThis !== false) {
				var isMatch = a_i.rev === a.rev && a_i.title === a.title && a_i.href === a.href.substr(a.href.length - a_i.href.length);
				if (isMatch || !isSingle) {
					a_i.seen = false;  // for the slideshow counter
					// add it to the item array for this run
					this.items.push(a_i);
					if (a_i.type === 'img') {
						this.hasImages = true;
					} else {
						this.justImages = false;
					}
					if (isMatch) this.currentIndex = this.items.length - 1;
				}
			}
		}
	}
	if (a.revOptions.showThis === false && a.href) {
		// a noShow item was clicked, look for a starting item based on href
		i = this.items.length;
		while (i--) {
			var href = this.items[i].href;
			if (href === a.href.substr(a.href.length - href.length)) {
				this.currentIndex = i;
			}
		}
	}
  	this.itemCount = this.items.length;
  	this.currentItem = this.items[this.currentIndex];
},  // end buildItemArray

//*********************************************************/
// getOptions()
// options precedence is:
// 1) querystring from the host page url (good for testing)
// 2) options from the clicked link's rev attribute
// 3) from cookies set by external form or code
// 4) fbPageOptions object defined on host page
// 5) those defined above in defaultOptions
//*********************************************************/
getOptions: function() {
	if (this.isChild) {
		// copy current options from the parent box
		for (var name in this.defaultOptions) {
			if (this.defaultOptions.hasOwnProperty(name)) this[name] = this.fbParent[name];
		}
		// get the child box options overrides from above
		this.setOptions(this.childOptions);
	} else {
		this.setOptions(this.defaultOptions);
	}
	this.doSlideshow = this.loadPageOnClose = this.sameBox = false;  // these will be picked up from rev tag, fbPageOptions, etc
	if (!(this.isChild || this.fbBox)) {
		// options set on the host page
		if (typeof setFloatboxOptions === 'function') setFloatboxOptions();  // for backwards compatability with previous version configs
		if (typeof fbPageOptions === 'object') this.setOptions(fbPageOptions);
		// cookie
		if (this.enableCookies) {
			var match = /fbOptions=(.+?)(;|$)/.exec(document.cookie);
			if (match) this.setOptions(this.parseOptionString(match[1]));
			// set the options cookie for the benefit of external options forms or other code
			// cookie will look like "fbOptions= option1:value1 option2:value2 ..."
			// cookie will contain only options listed in the defaultOptions object
			var strOptions = '';
			for (var name in this.defaultOptions) {
				if (this.defaultOptions.hasOwnProperty(name)) {
					strOptions += ' ' + name + ':' + this[name];
				}
			}
			var strPath = '/';
			if (this.cookieScope === 'folder') {
				strPath = location.pathname;
				strPath = strPath.substring(0, strPath.lastIndexOf('/') + 1);
			}
			document.cookie = 'fbOptions=' + strOptions + '; path=' + strPath;
		}
	}
	// options from the clicked item's rev attribute
	this.setOptions(this.revOptions);
	//options from the page url's querystring
	this.setOptions(this.parseOptionString(location.search.substring(1)));
// set some backwards compatability stuff
	if (this.theme === 'grey') this.theme = 'white';
	if (this.endTask === 'cont') this.endTask = 'loop';
	if (this.navType === 'upper') this.navType = 'overlay';
	if (this.navType === 'lower') this.navType = 'button';
	if (this.upperOpacity) this.controlOpacity = this.upperOpacity;
	if (this.upperNavWidth) this.navOverlayWidth = this.upperNavWidth;
	if (this.upperNavPos) this.navOverlayPos = this.upperNavPos;
	if (this.showUpperNav) this.showNavOverlay = this.showUpperNav;
	if (this.dropShadow) this.shadowType = 'drop';
// adjust options to circumstances
	if (!/^(auto|black|white|blue|yellow|red|custom)$/.test(this.theme)) this.theme='auto';
	if (!/^(overlay|button|both|none)$/i.test(this.navType)) this.navType = 'button';
	if (!/^(auto|wh|hw)$/.test(this.splitResize)) this.splitResize = false;
	if (this.webkitOld && (this.navType === 'overlay' || this.navType === 'both') ) {
		this.navType = 'button';  // nav overlay doesn't work in safari 2
	}
	if (this.itemCount > 1) {
		this.isSlideshow = this.doSlideshow || /^(slideshow|lyteshow)/i.test(this.currentItem.rel);
		var overlayRequest = /overlay|both/i.test(this.navType),
			buttonRequest = /button|both/i.test(this.navType);
		this.navOverlay = this.justImages && overlayRequest;
		this.navButton = buttonRequest || (!this.justImages && overlayRequest);
		this.lclShowItemNumber = this.showItemNumber;
		this.lclNumIndexLinks = this.numIndexLinks;
	} else {
		this.isSlideshow = this.navOverlay = this.navButton = this.lclShowItemNumber = this.lclNumIndexLinks = false;
	}
	this.isPaused = this.startPaused;
	if ((this.lclTheme = this.theme) === 'auto') {
		this.lclTheme = this.currentItem.type === 'img' ? 'black' : /flash|quicktime/.test(this.currentItem.type) ? 'blue' : 'white';
	}
	if (!this.doAnimations) {
		this.resizeDuration = this.imageFadeDuration = this.overlayFadeDuration = 0;
	}
	if (!this.resizeDuration) this.zoomImageStart = false;
	if (!/[tb][lr]/.test(this.controlPos)) this.controlPos = '';
	if (!/[tb][lcr]/.test(this.infoPos)) this.infoPos = '';
	this.controlTop = this.controlPos.charAt(0) === 't';
	this.controlLeft = this.controlPos.charAt(1) === 'l';
	this.infoTop = this.infoPos.charAt(0) === 't';
	this.infoCenter = this.infoPos.charAt(1) === 'c';
	this.infoLeft = this.infoPos.charAt(1) === 'l' || (this.infoCenter && this.controlTop === this.infoTop && !this.controlLeft);
	if (this.infoLeft === this.controlLeft && this.infoTop === this.controlTop) {  // trying to put everything in one corner
		this.infoLeft = true;
		this.controlLeft = false;
	}
	if (this.indexLinksPanel === 'info') {
		this.indexCenter = this.infoCenter;
		this.indexLeft = this.infoLeft;
		this.indexTop = this.infoTop;
	} else {
		this.indexLeft = this.controlLeft;
		this.indexTop = this.controlTop;
	}
	if (!/^(drop|halo|none)$/.test(this.shadowType)) this.shadowType='drop';
	if (!/^(8|12|16|24)$/.test(this.shadowSize + '')) this.shadowSize = 8;
	this.shadowSize = +this.shadowSize;  // make sure it's numeric
	if (this.opera || (this.mac && !this.webkitNew)) {  // these browsers can't do custom cursors
		this.resizeTool = 'topleft';
	} else {
		this.resizeTool = this.resizeTool.toLowerCase();
		if (!/topleft|cursor|both/.test(this.resizeTool)) this.resizeTool = 'cursor';
	}
	if (this.ieOld) this.shadowType = 'none';  // ie6 can't handle transparent pngs
	if (this.padding + this.outerBorder === 0) this.zoomPopBorder = 0;
// adjust opacity to 0-1 decimal values (but don't do it twice for secondary boxes)
	if (this.overlayOpacity > 1) this.overlayOpacity /= 100;
	if (this.controlOpacity > 1) this.controlOpacity /= 100;
},  // end getOptions

//*******************************************************************/
// parseOptionString()
// Return object of name:value pairs from a query string or a rev tag
// e.g., "doSlideshow=true&navType=none"   (queryString syntax)
//   or, "doSlideshow:true navType:none"   (valid rev attribute)
//   or, "doSlideshow:true; navType:none;"  (style/css syntax)
//*******************************************************************/
parseOptionString: function(str) {
	if (!str) return {};
	var quotes = [], match,
		rex = /`([^`]*?)`/g;
	while ((match = rex.exec(str))) quotes.push(match[1]);  // capture all backquoted segments
	if (quotes.length) str = str.replace(rex, '``');  // remove backquoted segments from the string but leave the backquotes to mark the spot
	str = str.replace(/\s*[:=]\s*/g, ':');  // = to :, trim internal spaces
	str = str.replace(/\s*[;&]\s*/g, ' ');  // & and ; to space, trim extra spaces
	str = str.replace(/^\s+|\s+$/g, '');  // trim leading and trailing spaces
	// now we've got "key:value key:value" pairs
	var pairs = {},
		aVars = str.split(' '),
		i = aVars.length;
	while (i--) {
		var aThisVar = aVars[i].split(':'),  // split this name:value pair
			name = aThisVar[0],
			value = aThisVar[1];
		if (typeof value === 'string') {  // parse booleans and numbers out of strings
			if (!isNaN(value)) value = +value;  // convert to a number
			else if (value === 'true') value = true;
			else if (value === 'false') value = false;
		}
		if (value === '``') value = quotes.pop() || '';  // put any backquoted string back in place
		pairs[name] = value;  // add this one to our pairs object
	}
	return pairs;
},  // end parseOptionString

//*********************************************************/
// setOptions()
// Sets floatbox option vars from a name:value pairs object
//*********************************************************/
setOptions: function(pairs) {
	for (var name in pairs) {
		if (pairs.hasOwnProperty(name)) this[name] = pairs[name];
	}
},  // end setOptions

//******************/
// buildDOM()
// Assemble floatbox
//******************/
buildDOM: function() {

// Insert elements into the document body that look like the following:
//	<div id="fbOverlay"></div>
// 	<div id="fbZoomDiv">
// 		<img id="fbZoomImg" />
// 	</div>
//	<div id="fbBox">
// 		<div id="fbShadowTop"></div>
// 		<div id="fbShadowRight"></div>
// 		<div id="fbShadowBottom"></div>
// 		<div id="fbShadowLeft"></div>
// 		<div id="fbShadowCorner"></div>
//		<div id="fbLoader"></div>
//		<div id="fbCanvas">
//			<div id="fbMainDiv">
//				<div|img|iframe id="fbContent" /> 
//				<a id="fbLeftNav"></a>
//				<a id="fbRightNav"></a>
//				<a id="fbOverlayPrev" title="hintPrev"></a>
//				<a id="fbOverlayNext" title="hintNext"></a>
//				<a id="fbResizer" title="hintResize"></a>
//			</div>
// 			<div id="fbInfoPanel">
// 				<div id="fbCaptionDiv">
// 					<span id="fbCaption"></span>
// 				</div>
// 				<div id="fbInfoDiv">
// 					<span id="fbInfoLink"></span>
// 					<span id="fbPrintLink"></span>
// 					<span id="fbItemNumber"></span>
// 				</div>
// 			</div>
// 			<div id="fbControlPanel">
// 				<div id="fbControls">
// 					<div id="fbNavControls">
// 						<a id="fbPrev" title="hintPrev"></a>
// 						<a id="fbNext" title="hintNext"></a>
// 					</div>
// 					<div id="fbSubControls">
// 						<div id="fbPlayPause">
// 							<a id="fbPlay" title="hintPlay"></a>
// 							<a id="fbPause" title="hintPause"></a>
// 						</div>
// 						<a id="fbClose" title="hintClose"></a>
// 					</div>
// 				</div>
// 				<span id="fbIndexLinks"></span>  (maybe attached to fbInfoPanel
// 			</div>
// 		</div>
//	</div>

	this.fbOverlay		= this.newNode('div', 'fbOverlay', this.bod);
	this.fbZoomDiv		= this.newNode('div', 'fbZoomDiv', this.bod);
	this.fbZoomImg		= this.newNode('img', 'fbZoomImg', this.fbZoomDiv);
	this.fbBox			= this.newNode('div', 'fbBox');  // append to body after all the child nodes are in place
	this.fbShadowTop	= this.newNode('div', 'fbShadowTop', this.fbBox);
	this.fbShadowRight	= this.newNode('div', 'fbShadowRight', this.fbBox);
	this.fbShadowBottom	= this.newNode('div', 'fbShadowBottom', this.fbBox);
	this.fbShadowLeft	= this.newNode('div', 'fbShadowLeft', this.fbBox);
	this.fbShadowCorner	= this.newNode('div', 'fbShadowCorner', this.fbBox);
	this.fbLoader		= this.newNode('div', 'fbLoader', this.fbBox);
	this.fbCanvas		= this.newNode('div', 'fbCanvas', this.fbBox);
	this.fbMainDiv		= this.newNode('div', 'fbMainDiv', this.fbCanvas);
	this.fbLeftNav		= this.newNode('a', 'fbLeftNav', this.fbMainDiv);
	this.fbRightNav		= this.newNode('a', 'fbRightNav', this.fbMainDiv);
	this.fbOverlayPrev	= this.newNode('a', 'fbOverlayPrev', this.fbMainDiv, this.strings.hintPrev);
	this.fbOverlayNext	= this.newNode('a', 'fbOverlayNext', this.fbMainDiv, this.strings.hintNext);
	this.fbResizer		= this.newNode('a', 'fbResizer', this.fbMainDiv, this.strings.hintResize);
	this.fbInfoPanel	= this.newNode('div', 'fbInfoPanel', this.fbCanvas);
	this.fbCaptionDiv	= this.newNode('div', 'fbCaptionDiv', this.fbInfoPanel);
	this.fbCaption		= this.newNode('span', 'fbCaption', this.fbCaptionDiv);
	this.fbInfoDiv		= this.newNode('div', 'fbInfoDiv', this.fbInfoPanel);
	if (this.infoLeft || this.infoCenter) {  // ie6 cares about the order when floating these
		this.fbInfoLink		= this.newNode('span', 'fbInfoLink', this.fbInfoDiv);
		this.fbPrintLink	= this.newNode('span', 'fbPrintLink', this.fbInfoDiv);
		this.fbItemNumber	= this.newNode('span', 'fbItemNumber', this.fbInfoDiv);
	} else {
		this.fbItemNumber	= this.newNode('span', 'fbItemNumber', this.fbInfoDiv);
		this.fbPrintLink	= this.newNode('span', 'fbPrintLink', this.fbInfoDiv);
		this.fbInfoLink		= this.newNode('span', 'fbInfoLink', this.fbInfoDiv);
	}
	this.fbControlPanel	= this.newNode('div', 'fbControlPanel', this.fbCanvas);
	this.fbControls		= this.newNode('div', 'fbControls', this.fbControlPanel);
	this.fbNavControls	= this.newNode('div', 'fbNavControls', this.fbControls);
	this.fbPrev			= this.newNode('a', 'fbPrev', this.fbNavControls, this.strings.hintPrev);
	this.fbNext			= this.newNode('a', 'fbNext', this.fbNavControls, this.strings.hintNext);
	this.fbSubControls	= this.newNode('div', 'fbSubControls', this.fbControls);
	this.fbPlayPause	= this.newNode('div', 'fbPlayPause', this.fbSubControls);
	this.fbPlay			= this.newNode('a', 'fbPlay', this.fbPlayPause, this.strings.hintPlay);
	this.fbPause		= this.newNode('a', 'fbPause', this.fbPlayPause, this.strings.hintPause);
	this.fbClose		= this.newNode('a', 'fbClose', this.fbSubControls, this.strings.hintClose);
	this.fbIndexLinks	= this.newNode('span', 'fbIndexLinks', this.indexLinksPanel === 'info' ? this.fbInfoPanel : this.fbControlPanel);
	this.bod.appendChild(this.fbBox);
},  // end buildDOM

//*********************/
// newNode()
// Create a DOM element
//*********************/
newNode: function(nodeType, id, parentNode, title) {
	// remove pre-existing node
	if (this[id] && this[id].parentNode) {
		this[id].parentNode.removeChild(this[id]);
	}
	// create and configure new node
	var node = document.createElement(nodeType);
	node.id = id;
	node.className = id + '_' + (id.indexOf('fbShadow') === -1 ? this.lclTheme : this.shadowType + this.shadowSize);
	if (nodeType === 'a') {
		if (!this.operaOld) node.setAttribute('href', '');  // opera pre 9.5 shows fugly tooltips if there's an href (they won't get the hand cursor either)
		if (this.ieOld) node.setAttribute('hideFocus', 'true');  // ie6 css bug ignores outline:none;
		node.style.outline = 'none';
	} else if (nodeType === 'iframe') {
		node.setAttribute('scrolling', this.itemScroll);  // IE will ignore setting iframe scrolling after the element has been added to the dom
		node.setAttribute('frameBorder', '0');  // note: IE needs the capital B and will ignore any changes to frameborder after the element is added to the DOM
		node.setAttribute('align', 'middle');
		node.src = this.iframeSrc;  // shows the initial loading gif and avoids the IE6 SSL bug
	}
	if (this.isChild && this.fbParent[id]) title = this.fbParent[id].getAttribute('title');  // use parent's in case a show-once title has been cleared
	if (title && this.showHints !== 'never') node.setAttribute('title', title);
	if (this.zIndex[id]) node.style.zIndex = this.zIndex.base + this.zIndex[id];
	node.style.display = 'none';  // everything starts off hidden
	if (parentNode) parentNode.appendChild(node);
	this.nodeNames.push(id);  // keep a list of created nodes for cleanup on end
	return node;
},  // end newNode

//**********************************************/
// addEventHandlers()
// Add event handlers to the floatbox components
//**********************************************/
addEventHandlers: function() {
	var that = this,
// short-hand style closures for event handlers
	leftNav = this.fbLeftNav.style,
	rightNav = this.fbRightNav.style,
	overlayPrev = this.fbOverlayPrev.style,
	overlayNext = this.fbOverlayNext.style,
	prev = this.fbPrev.style,
	next = this.fbNext.style;
// utility function used by mouse events to clear show-once hints
	if (this.showHints === 'once') {
		this.hideHint = function(id) {
			if (that[id].title) {
				that.timeouts[id] = setTimeout(function() {
					that[id].title = that.fbParent[id].title = '';  // for this box and its parent if it's got one
					var id2 = '';
					if (/fbOverlay(Prev|Next)/.test(id)) {  // clear both nav thingy titles together
						id2 = id.replace('Overlay', '');
					} else if (/fb(Prev|Next)/.test(id)) {
						id2 = id.replace('fb', 'fbOverlay');
					}
					if (id2) that[id2].title = that.fbParent[id2].title = '';
				}, that.showHintsTime);
			}
		};
	} else {
		this.hideHint = function() {};
	}
// attach event behaviours to the controls
	this.fbPlay.onclick = function() {
		that.setPause(false);
		if (window.event) event.returnValue = false;
		return false;
	};
	this.fbPause.onclick = function() {
		that.setPause(true);
		if (window.event) event.returnValue = false;
		return false;
	};
	this.fbClose.onclick = function() {
		that.end();
		if (window.event) event.returnValue = false;
		return false;
	};
	if (this.outsideClickCloses) {
		this.fbOverlay.onclick = this.fbShadowTop.onclick = this.fbShadowRight.onclick =
		this.fbShadowBottom.onclick = this.fbShadowLeft.onclick = this.fbShadowCorner.onclick = this.fbClose.onclick;
	}
	this[this.rtl ? 'fbNext' : 'fbPrev'].onclick = function(step) {
		if (typeof step !== 'number') step = 1;
		var newIndex = (that.currentIndex - step) % that.itemCount;  // modulo remainder
		if (newIndex < 0) newIndex += that.itemCount;  // adjust if it's negative
		if (that.enableWrap || newIndex < that.currentIndex) {
			// show previous item and adjust pause state
			that.newContent(newIndex);
			if (that.isSlideshow && that.pauseOnPrev && !that.isPaused) {
				that.setPause(true);
			}
		}
		if (window.event) event.returnValue = false;
		return false;
	};
	this[this.rtl ? 'fbPrev' : 'fbNext'].onclick = function(step) {
		if (typeof step !== 'number') step = 1;
		var newIndex = (that.currentIndex + step) % that.itemCount;
		if (that.enableWrap || newIndex > that.currentIndex) {
			// show next item and adjust pause state
			that.newContent(newIndex);
			if (that.isSlideshow && that.pauseOnNext && !that.isPaused) {
				that.setPause(true);
			}
		}
		if (window.event) event.returnValue = false;
		return false;
	};
// set overlay nav panel mouse actions
	// onclick same as button nav onclick action
	this.fbLeftNav.onclick = this.fbOverlayPrev.onclick = this.fbPrev.onclick;
	this.fbRightNav.onclick = this.fbOverlayNext.onclick = this.fbNext.onclick;
	// mouseover, mousemove
	this.fbLeftNav.onmouseover = this.fbLeftNav.onmousemove =
	this.fbOverlayPrev.onmousemove = function() {
		// if the content panel is not currently fading in, show the overlay prev widget
		if (!that.timeouts.fbCanvas) overlayPrev.visibility = '';
		// if the overlay prev widget is set to not show, light up the button prev instead
		if (that.navButton) prev.backgroundPosition = that.onPos;
		return true;  // block status bar showing of bogus href
	};
	this.fbRightNav.onmouseover = this.fbRightNav.onmousemove =
	this.fbOverlayNext.onmousemove = function() {
		if (!that.timeouts.fbCanvas) overlayNext.visibility = '';
		if (that.navButton) next.backgroundPosition = that.onPos;
		return true;
	};
	this.fbOverlayPrev.onmouseover = this.fbOverlayNext.onmouseover = function() {
		this.onmousemove();
		that.hideHint(this.id);
		return true;
	};
	// mouseout
	this.fbLeftNav.onmouseout = function() {
		// hide the overlay prev widget and turn off highlighting of button prev
		overlayPrev.visibility = 'hidden';
		if (that.navButton) prev.backgroundPosition = that.offPos;
	};
	this.fbRightNav.onmouseout = function() {
		overlayNext.visibility = 'hidden';
		if (that.navButton) next.backgroundPosition = that.offPos;
	};
	this.fbOverlayPrev.onmouseout = this.fbOverlayNext.onmouseout = function() {
		this.style.visibility = 'hidden';
		// cancel the remove title timer. Tooltip did not have enough time to display
		that.clearTimeout(this.id);
	};
	// right-click handler to show the image context menu in place of the nav overlay context menu
	this.fbLeftNav.onmousedown = this.fbRightNav.onmousedown = function(e) {
		e = e || window.event;
		if (e.button === 2) {  // if it's a right-click
			// briefly hide the nav panels so the image will be the topmost event handler
			leftNav.visibility = rightNav.visibility = 'hidden';
			that.timeouts.hideNavOverlay = setTimeout(function() {
				leftNav.visibility = rightNav.visibility = '';
			}, 600);
		}
	};
// mouse actions to clear show-once hints and activate controls background sprite animation
	this.fbPlay.onmouseover = this.fbPause.onmouseover = this.fbClose.onmouseover =
	this.fbPrev.onmouseover = this.fbNext.onmouseover = function() {
		this.style.backgroundPosition = that.onPos;
		that.hideHint(this.id);
		return true;
	};
	this.fbResizer.onmouseover = function() {
		that.hideHint(this.id);
		return true;
	};
	this.fbPlay.onmouseout = this.fbPause.onmouseout = this.fbClose.onmouseout =
	this.fbPrev.onmouseout = this.fbNext.onmouseout = function() {
		this.style.backgroundPosition = that.offPos;
		that.clearTimeout(this.id);
	};
	this.fbResizer.onmouseout = function() {
		that.clearTimeout(this.id);
	};
// turn keyboard handler on or off
	if (this.enableKeyboardNav) {
		if (!document.keydownSet) {
			this.priorOnkeydown = document.onkeydown;
			document.onkeydown = this.keydownHandler;
			document.keydownSet = true;
		}
	} else if (document.keydownSet) {
		document.onkeydown = this.priorOnkeydown;
		document.keydownSet = false;
	}
// block stupid opera spacebar keypress action
	if (this.opera && !document.keypressSet) {
		this.priorOnkeypress = document.onkeypress;
		document.onkeypress = function() { return false; };
		document.keypressSet = true;
	}
// add drag and drop
	if (this.enableDrag) this.fbBox.onmousedown = this.dragonDrop();
},  // end addEventHandlers

//********************************/
// keydownHandler()
// onkeydown event handler
//********************************/
keydownHandler: function(e) {
	e = e || window.event;
	var that = fb.lastChild,
		keyCode = e.keyCode || e.which;
	switch (keyCode) {
// left/right arrow: prev/next item
		case 37: case 39:
			if (that.itemCount > 1) {
				that[keyCode === 37 ? 'fbPrev' : 'fbNext'].onclick((e.ctrlKey || e.metaKey) ? that.ctrlJump : 1);
				if (that.showHints === 'once') {
					// turn off hints, because user already knows
					that.fbPrev.title = that.fbNext.title =
					that.fbOverlayPrev.title = that.fbOverlayNext.title = '';
				}
			}
			return false;  // block horizontal scroll
// spacebar: toggle play/pause
		case 32:
			if (that.isSlideshow) {
				that.setPause(!that.isPaused);
				if (that.showHints === 'once') that.fbPlay.title = that.fbPause.title = '';
			}
			return false;  // block vertical scroll
// tab: resize
		case 9:
			if (that.fbResizer.onclick) {
				that.fbResizer.onclick();
				if (that.showHints === 'once') that.fbResizer.title = '';
			}
			return false;  // block focus change
// esc: exit
		case 27:
			if (that.showHints === 'once') that.fbClose.title = '';  // for next run
			that.end();
			return false;  // don't let esc cancel end() function's loadPageOnClose action
// block enter key reload of active anchor on the launching page
		case 13:
			return false;
	}
},  // end keydownHandler

//****************************/
// dragonDrop()
// Returns mouse drag function
// (with a little help from http://jmvidal.cse.sc.edu/talks/javascriptinbrowser/exampledraggingdocumentelements.xml)
//****************************/
dragonDrop: function() {
	var that = this,
		fbBox = this.fbBox;
	return function(e) { 
		e = e || window.event;
		if (/fb(Box|Canvas|Info|Caption|Item|Control|Index)/.test((e.target || e.srcElement).id)) {
			var startX = e.clientX,
				startY = e.clientY,
				box = that.fbBox.style,
				content = that.fbContent.style,
				pos = that.pos.fbBox,
				boxX = pos.left,
				boxY = pos.top;
			pos.dx = pos.dy = 0;
			var moveHandler = function(e) {
				// hide iframes so their document won't start capturing mouse events (ie and opera pass mouse events through to the parent doc)
				if (that.currentItem.type === 'iframe' && !(that.ie || that.opera) && !content.visibility) content.visibility = 'hidden';
				if (that.isSlideshow && !that.isPaused) that.setPause(true);
				e = e || window.event;
				pos.dx = e.clientX - startX;  // save move delta
				pos.dy = e.clientY - startY;
				box.left = (boxX + pos.dx) + 'px';  // update box position
				box.top = (boxY + pos.dy) + 'px';
				(e.stopPropagation && e.stopPropagation()) || (e.cancelBubble = true);  // block event propagation
				// consider absence of movement a mouseup event
				that.clearTimeout('dragonDrop');  // cancel the last timer
				that.timeouts.dragonDrop = setTimeout(upHandler, 1500);  // and set a new one
				return false;
			};
			var upHandler = function(e) {
				that.clearTimeout('dragonDrop');  // cancel the timer
				e = e || window.event;
				// unregister the capturing event handlers
				if (document.removeEventListener) {  // DOM event model
					document.removeEventListener("mouseup", upHandler, true);
					document.removeEventListener("mousemove", moveHandler, true);
				} else if (fbBox.detachEvent) {  // IE 5+ Event Model
					fbBox.detachEvent("onlosecapture", upHandler);
					fbBox.detachEvent("onmouseup", upHandler);
					fbBox.detachEvent("onmousemove", moveHandler);
					fbBox.releaseCapture();
				}
				if (e) (e.stopPropagation && e.stopPropagation()) || (e.cancelBubble = true);  // block event propagation
				pos.left += pos.dx;  // update the position record
				pos.top += pos.dy;
				content.visibility = '';  // turn iframe content back on
				return false;
			};
			// register the capturing event handlers
			if (document.addEventListener) {  // DOM Level 2 event model
				document.addEventListener("mousemove", moveHandler, true);
				document.addEventListener("mouseup", upHandler, true);
			} else if (fbBox.attachEvent) {  // IE 5+ Event Model
				fbBox.setCapture();
				fbBox.attachEvent("onmousemove", moveHandler);
				fbBox.attachEvent("onmouseup", upHandler);
				// treat loss of mouse capture as a mouseup event
				fbBox.attachEvent("onlosecapture", upHandler);
			}
			return false;
		}
	};
},  // end dragonDrop

//*********************************************/
// initState()
//*********************************************/
initState: function() {
	var that = this,
// short names for styles
		box = this.fbBox.style,
		mainDiv = this.fbMainDiv.style,
		canvas = this.fbCanvas.style,
		zoomDiv = this.fbZoomDiv.style,
		zoomImg = this.fbZoomImg.style;
// set starting dimensions
	if (this.currentItem.popup) this.currentItem.anchor.onmouseover();  // on some browsers the popup won't go away on its on underneath the box
	var anchorPos = this.getAnchorPos(this.clickedAnchor, this.currentItem.anchor === this.clickedAnchor && this.currentItem.type === 'img');
	if (anchorPos.width) {  // it's a thumbnail and we're zooming from it
		this.pos.fbZoomDiv = anchorPos;
		zoomDiv.borderWidth = this.zoomPopBorder + 'px';
		zoomDiv.left = (anchorPos.left - this.zoomPopBorder) + 'px';
		zoomDiv.top = (anchorPos.top - this.zoomPopBorder) + 'px';
		zoomDiv.width = (this.fbZoomImg.width = anchorPos.width) + 'px';
		zoomDiv.height = (this.fbZoomImg.height = anchorPos.height) + 'px';
		this.fbZoomImg.src = anchorPos.src;
		box.visibility = 'hidden';
		// show the loading gif over the thumbnail if the image is slow to load
		this.timeouts.slowLoad = setTimeout(function() {
			if (that.fbOverlay.style.display) that.fadeOpacity(that.fbOverlay, that.overlayOpacity, that.overlayFadeDuration);
			that.fbZoomImg.src = that.slowZoomImg;
			zoomDiv.display = zoomImg.display = '';
		}, this.slowLoadDelay);
	} else {  // non-zoomed start
		this.pos.fbBox = anchorPos;
		this.pos.fbBox.borderWidth = 0;
		this.pos.fbMainDiv = { width:0, height:0 };
	}
// configure elements
	box.position = 'absolute';
	box.left = box.top = box.width = box.height = box.borderWidth = '0';
	mainDiv.borderWidth = this.innerBorder + 'px';
	mainDiv.left = this.padding + 'px';
	this.fbControlPanel.style[this.controlLeft ? 'left' : 'right'] =
	this.fbInfoPanel.style[this.infoLeft ? 'left' : 'right'] = Math.max(this.padding, this.panelPadding) + 'px';
	canvas.visibility = 'hidden';
	box.display = canvas.display = '';
	if (this.shadowType === 'none') {
		this.shadowSize = 0;
	} else {
		var shadowTop = this.fbShadowTop.style,
			shadowRight = this.fbShadowRight.style,
			shadowBottom = this.fbShadowBottom.style,
			shadowLeft = this.fbShadowLeft.style,
			shadowCorner = this.fbShadowCorner.style;
		// position divs outside the box
		shadowRight.top = shadowBottom.left = shadowLeft.top = -this.outerBorder + 'px';
		// push the right and bottom out past the box
		shadowRight.paddingRight = shadowBottom.paddingBottom =
		shadowCorner.paddingRight = shadowCorner.paddingBottom = (this.outerBorder + this.shadowSize) + 'px';
		if (this.shadowType === 'halo') {
			// add outerborder width to shadow divs
			shadowTop.paddingRight = shadowRight.paddingBottom =
			shadowBottom.paddingRight = shadowLeft.paddingBottom = (this.outerBorder*2 + this.shadowSize) + 'px';
			// position divs outside the box
			shadowTop.top = shadowTop.left = shadowRight.top = shadowLeft.left = -(this.outerBorder + this.shadowSize) + 'px';
		} else {  // drop
			shadowBottom.backgroundPosition = 'bottom left';
			// add outerBorder widths
			shadowRight.paddingBottom = shadowBottom.paddingRight = this.outerBorder*2 + 'px';
		}
	}
	if (this.navOverlay) {
		if (fb.showNavOverlay === 'never' || (fb.showNavOverlay === 'once' && fb.navOverlayShown)) {  //navOverlayShown persists through restarts
			fb.showNavOverlay = false;  // using fb instead of this to make it universal across all boxes
		} else {
			this.fbOverlayPrev.style.backgroundPosition = this.fbOverlayNext.style.backgroundPosition = this.onPos;
			this.fadeOpacity(this.fbOverlayPrev, this.controlOpacity);
			this.fadeOpacity(this.fbOverlayNext, this.controlOpacity);
		}
	}
	this.initPanels();
	this.lastShown = false;
// hide flash and java 'cause they can bleed through in some circumstances
	if (this.hideFlash) this.hideElements('flash');
	if (this.hideJava) this.hideElements('applet');
// ie6 always shows selects on top, doesn't handle position:fixed and doesn't respect height/width:100% against the body
	if (this.ieOld) {
		this.hideElements('select');
		this.fbOverlay.style.position = 'absolute';
		this.stretchOverlay()();
		attachEvent('onresize', this.stretchOverlay());
		attachEvent('onscroll', this.stretchOverlay());
	}
},  // end initState

//**************************************************************/
// hideElements()
// Hides elements so they don't appear above the overlay and box
// (For flash, java and the ie6 select z-index bug)
//**************************************************************/
hideElements: function(type, thisWindow) {
	// thisWindow is used to recurse through all frames under the base window (usually top)
	if (!thisWindow) {  // first call?  start with the floatbox host window
		this.hideElements(type, self);
	} else {  // we've called in with a window object
		var tagName, tagNames = type === 'flash' ? ['object', 'embed'] : [type];
		try {  // this has gakked on some ie machines
			while ((tagName = tagNames.pop())) {
				var els = thisWindow.document.getElementsByTagName(tagName),
					i = els.length;
				while (i--) {
					var el = els[i];
					if (el.style.visibility !== 'hidden' && (tagName !== 'object' ||
					(el.getAttribute('type') && el.getAttribute('type').toLowerCase() === 'application/x-shockwave-flash') ||
					(el.getAttribute('classid') && el.getAttribute('classid').toLowerCase() === 'clsid:d27cdb6e-ae6d-11cf-96b8-444553540000') ||
					/data\s*=\s*"?[^>"]+\.swf\b/i.test(el.innerHTML) ||
					/param\s+name\s*=\s*"?(movie|src)("|\s)[^>]+\.swf\b/i.test(el.innerHTML))) {
						this.hiddenEls.push(el);
						el.style.visibility = 'hidden';
					}
				}
			}
		} catch(e) {}
		// recurse all the child frames
		var iframes = thisWindow.frames, i = iframes.length;
		while (i--) {
			try {  // opera will gak if iframe src is from a different domain
				if (typeof iframes[i].window === 'object') this.hideElements(type, iframes[i].window);
			} catch(e) {}
		}
	}
},  // end hideElements

//**********************************************************/
// getAnchorPos()
// Gets the position we want to use for starting and ending,
// not always the real anchor pos
//**********************************************************/
getAnchorPos: function(anchor, useThumb) {
	var display = this.getDisplaySize(),
		scroll = this.getScroll(),
		noAnchorPos = { left: display.width/2 + scroll.left, top: display.height/3 + scroll.top, width: 0, height: 0 };
	// if this anchor points to an image, look for a corresponding thumbnail
	var thumb = useThumb ? this.getThumb(anchor) : false;
	if (thumb && this.zoomImageStart) {
		var pos = this.getLeftTop(thumb),
			border = (thumb.offsetWidth - thumb.width)/2;
		pos.left += border;
		pos.top += border;
		pos.width = thumb.width;
		pos.height = thumb.height;
		pos.src = thumb.src;  // passed back to initState so it can be used as the starting image
	} else if (this.startAtClick && anchor && anchor.offsetWidth && anchor.tagName.toLowerCase() === 'a') {
		var pos = this.getLayout(thumb || anchor);
	} else {
		return noAnchorPos;  // not a real anchor, or we're not starting from anchors
	}
	// off screen? (maybe fired by code)
	var centerPos = { left: pos.left + pos.width/2, top: pos.top + pos.height/2, width: 0, height: 0 };
	if (centerPos.left < scroll.left || centerPos.left > (scroll.left + display.width) ||
	centerPos.top < scroll.top || centerPos.top > (scroll.top + display.height)) {
		return noAnchorPos;
	}
	return (thumb && this.zoomImageStart ? pos : centerPos);
},  // end getAnchorPos

//*********************************************/
// getThumb()
// Returns thumbnail element from inside anchor
//*********************************************/
getThumb: function(anchor) {
	var nodes = anchor && anchor.childNodes, i = (nodes && nodes.length) || 0;
	while (i--) {  // look for a child node img
		if ((nodes[i].tagName || '').toLowerCase() === 'img') return nodes[i];
	}
	return false;
},  // end getThumb

//************************************************/
// initPanels()
// Setup static aspects of info and control panels
//************************************************/
initPanels: function() {
// infoPanel
	var infoPanel = this.fbInfoPanel.style,
		infoLink = this.fbInfoLink.style,
		printLink = this.fbPrintLink.style,
		itemNumber = this.fbItemNumber.style;
	if (this.infoCenter) {
		var infoPos = ' posCenter';
		infoPanel.textAlign = 'center';  // ie6 won't pick the new text-align up from the css
		infoLink.paddingLeft = printLink.paddingLeft = itemNumber.paddingLeft = 
		infoLink.paddingRight = printLink.paddingRight = itemNumber.paddingRight = (this.infoLinkGap/2) + 'px';
	} else if (this.infoLeft) {
		var infoPos = ' posLeft';
		infoPanel.textAlign = 'left';
		infoLink.paddingRight = printLink.paddingRight = this.infoLinkGap + 'px';
	} else {
		var infoPos = ' posRight';
		infoPanel.textAlign = 'right';
		infoLink.paddingLeft = printLink.paddingLeft = this.infoLinkGap + 'px';
	}
	this.fbInfoPanel.className += infoPos;
	this.fbInfoDiv.className += infoPos;
	infoPanel.width = '400px';  // a typical value to start measuring from
// controlPanel
	var controlPanel = this.fbControlPanel.style,
		controls = this.fbControls.style,
		subControls = this.fbSubControls.style;
	if (this.controlLeft) {
		var controlPos = ' posLeft';
		controlPanel.textAlign = 'left';
	} else {
		var controlPos = ' posRight';
		controlPanel.textAlign = 'right';  // must over-ride this with left in the css for fbPlayPause on IE
		controls.right = '0';  // ie6 helper
	}
	this.fbControlPanel.className += controlPos;
	this.fbSubControls.className += controlPos;
	if (!this.ieOld) this.fbControls.className += controlPos;
// nav buttons
	if (this.navButton) {
		var prev = this.fbPrev.style,
			next = this.fbNext.style,
			navControls = this.fbNavControls.style;
		prev.backgroundPosition = next.backgroundPosition = this.offPos;
		navControls['padding' + (this.controlLeft ? 'Left' : 'Right')] = this.controlSpacing + 'px';
		this.fbNavControls.className += controlPos;
		controlPanel.display = navControls.display = prev.display = next.display = '';
	}
// other buttons
	var width = 0;
	if (this.showClose) {
		var close = this.fbClose.style;
		close.backgroundPosition = this.offPos;
		this.fbClose.className += controlPos;
		controlPanel.display = controls.display = subControls.display = close.display = '';
		width = this.fbClose.offsetWidth;
	}
	if (this.showPlayPause && this.isSlideshow) {
		var play = this.fbPlay.style,
			pause = this.fbPause.style,
			playPause = this.fbPlayPause.style;
		play.backgroundPosition = pause.backgroundPosition = this.offPos;
		playPause['padding' + (this.controlLeft ? 'Left' : 'Right')] = this.controlSpacing + 'px';
		this.fbPlayPause.className += controlPos;
		controlPanel.display = controls.display = subControls.display = playPause.display = play.display = pause.display = '';
		play.top = this.isPaused ? '' : '-9999px';
		pause.top = this.isPaused ? '-9999px' : '';
		width += this.fbPlayPause.offsetWidth;
	}
// set widths based on gathered measurements
	subControls.width = width + 'px';
	controlPanel.width = controls.width = (width + this.fbNavControls.offsetWidth) + 'px';
// index links
	if (this.lclNumIndexLinks) {
		var indexLinks = this.fbIndexLinks.style;
		if (this.indexLinksPanel === 'info') {
			this.fbIndexLinks.className += infoPos;
			infoPanel.display = '';
			if (this.showIndexThumbs) infoPanel.overflow = 'visible';
		} else {
			this.fbIndexLinks.className += controlPos;
			controlPanel.display = '';
			if (this.showIndexThumbs) controlPanel.overflow = 'visible';
			indexLinks['padding' + (this.indexLeft ? 'Left' : 'Right')] = '2px';  // somewhat aligned with English close button
		}
		indexLinks.width = '250px';  // typical value to start measuring from
		indexLinks.display = '';
	}
},  // end initPanels

//*****************************************/
// fetchContent()
// Load currentItem into fbContent
// Also captures nativeWidth & nativeHeight
//*****************************************/
fetchContent: function(callback, phase) {
	var that = this;
	if (!phase) {  // phase 0
		// discard previous content
		if (this.fbContent) {
			this.fbMainDiv.removeChild(this.fbContent);
			delete this.fbContent;
			// Opera needs a timer break here to successfully remove fbContent
			return this.timeouts.fetch = setTimeout(function() { that.fetchContent(callback, 1); }, 10);
		}
	}
	// phase 1
	var item = this.currentItem;
	item.nativeWidth = item.revOptions.width;
	item.nativeHeight = item.revOptions.height;
	if (item.type !== 'img') {
		item.nativeWidth = item.nativeWidth || (fb.previousAnchor && fb.previousAnchor.nativeWidth) || this.defaultWidth;
		item.nativeHeight = item.nativeHeight || (fb.previousAnchor && fb.previousAnchor.nativeHeight) || this.defaultHeight;
	}
	if (this.ieOld) this.fbMainDiv.style.backgroundColor = item.type === 'img' ? '#000' : '';  // ie6 jpg fade fix
	// itemScroll is used by newNode when setting up an iframe
	this.itemScroll = item.revOptions.scrolling || item.revOptions.scroll || 'auto';
	if (/img|iframe/.test(item.type)) {
		this.fbContent = this.newNode(item.type, 'fbContent', this.fbMainDiv);
		if (item.type === 'img') {
			var loader = new Image();
			loader.onload = function() {
				item.nativeWidth = item.nativeWidth || loader.width;  // preference to dimensions in the rev option
				item.nativeHeight = item.nativeHeight || loader.height;
				that.fbContent.src = loader.src;
				if (callback) callback();
			};
			loader.onerror = function() {  // if the image can't be found show the 404 image
				if (this.src !== that.notFoundImg) this.src = that.notFoundImg;
			};
			loader.src = item.href;
		}
		// iframe src is set at show time so as not to interfere with animated resizing
	} else {  // some kind of html content
		// set scroller preference via overflow
		this.fbContent = this.newNode('div', 'fbContent', this.fbMainDiv);
		this.fbContent.style.overflow = this.itemScroll === 'yes' ? 'scroll' : (this.itemScroll === 'no' ? 'hidden' : 'auto');
		if (item.type === 'inline') {
			var el = item.sourceEl.cloneNode(true);  // copy the source element (probably a hidden div)
			el.style.display = el.style.visibility = '';
			try { this.fbContent.appendChild(el); }  // stick the clone in our content div
			catch(e) { this.setInnerHTML(this.fbContent, el.innerHTML); }
			this.tagAnchors(this.fbContent);  // light up any floatbox links in the new content
		} else if (item.type === 'ajax') {
			// insert fetched html into our div
			this.xhr.getResponse(item.href, function(xhr) {
				if ((xhr.status === 200 || xhr.status === 203 || xhr.status === 304) && xhr.responseText) {
					that.setInnerHTML(that.fbContent, xhr.responseText);
					that.tagAnchors(that.fbContent);  // light up any floatbox links in the ajax content
				} else {
					that.setInnerHTML(that.fbContent, '<p style="color:#000; background:#fff; margin:1em; padding:1em;">' +
					'Unable to fetch content from ' + item.href + '</p>');  // in case it don't work
				}
			});
		}
		// flash and quicktime will be inserted at show time when we have the potentially resized or max/% dimensions
	}
	this.fbContent.style.border = '0';  // override css conflicts
	this.fbContent.style.display = '';
	// do oncomplete action, except the image will fire it via onload
	if (item.type !== 'img' && callback) callback();
},  // end fetchContent

//********************************/
// updatePanels()
// Caption, info link, item number
//********************************/
updatePanels: function() {
	// style shortnames
	var infoPanel = this.fbInfoPanel.style,
		captionDiv = this.fbCaptionDiv.style,
		caption = this.fbCaption.style,
		infoDiv = this.fbInfoDiv.style,
		infoLink = this.fbInfoLink.style,
		printLink = this.fbPrintLink.style,
		itemNumber = this.fbItemNumber.style,
		item = this.currentItem,
		str;
	infoPanel.display = captionDiv.display = caption.display = infoDiv.display = infoLink.display = printLink.display = itemNumber.display = 'none';
	if (this.showCaption) {
		// caption can come from the rev options or use the title
		str = item.revOptions.caption || item.title || '';
		if (str === 'href') {
			str = this.encodeHTML(this.currentItem.href);
		} else {
			str = this.decodeHTML(str).replace(/&/g, '&amp;');  // bare ampersands are always toxic
		}
		// update caption even if it's blank to clear previous caption out
		if (this.setInnerHTML(this.fbCaption, str) && str) infoPanel.display = captionDiv.display = caption.display = '';
	}
	// info link requested for this item
	if (item.revOptions.info) {
		str = this.encodeHTML(this.decodeHTML(item.revOptions.info));  // encode but don't double encode
		var options = item.revOptions.infoOptions || '';
		if (options) options = this.encodeHTML(this.decodeHTML(options));
		// build a standard floatbox enabled anchor to do the work
		str = '<a href="' + str + '" rel="floatbox" rev="' + options + '"><b>' +
		(item.revOptions.infoText || this.strings.infoText) + '</b></a>';
		if (this.setInnerHTML(this.fbInfoLink, str)) infoPanel.display = infoDiv.display = infoLink.display = '';
	}
	// print button requested for this item
	if (item.revOptions.showPrint) {
		var css = item.revOptions.printCSS || '';
		// anchor with onclick action of running printContents function
		str = '<a href="' + this.encodeHTML(this.currentItem.href) +
		'" onclick="fb.printContents(null, \'' + css + '\'); if (window.event) event.returnValue = false; return false;"><b>' +
		(item.revOptions.printText || this.strings.printText) + '</b></a>';
		if (this.setInnerHTML(this.fbPrintLink, str)) infoPanel.display = infoDiv.display = printLink.display = '';
	}
	// image x of y display
	if (this.lclShowItemNumber) {
		str = this.justImages ? this.strings.imgCount : (this.hasImages ? this.strings.mixedCount : this.strings.nonImgCount);
		str = str.replace('%1', this.currentIndex + 1);
		str = str.replace('%2', this.itemCount);
		if (this.setInnerHTML(this.fbItemNumber, str)) infoPanel.display = infoDiv.display = itemNumber.display = '';
	}
	// set width of div that holds the above items (IE is missing the padding for some measurements (under different circumstances for different versions))
	var w = this.fbInfoLink.offsetWidth + this.fbPrintLink.offsetWidth + this.fbItemNumber.offsetWidth;
	if (this.ie) {
		if (this.fbInfoLink.offsetWidth) w += this.infoLinkGap;
		if (this.fbPrintLink.offsetWidth) w += this.infoLinkGap;
		if (this.fbItemNumber.offsetWidth) w += this.infoLinkGap;
	}
	infoDiv.width = w + 'px';
	// index links requested
	if (this.lclNumIndexLinks) {
		str = '';
		// calc index for items within range of current item based on requested number of index links
		var max = this.itemCount - 1,
			loRange, hiRange;
		if (this.lclNumIndexLinks === -1) {  // -1 means no restriction on link count
			loRange = 0;
			hiRange = max;
		} else {
			var range = Math.floor(this.lclNumIndexLinks/2) - 1;
			loRange = this.currentIndex - range;
			hiRange = this.currentIndex + range;
			if (loRange <= 0) hiRange += Math.min(1 - loRange, range);
			if (this.currentIndex === 0) hiRange++;
			if (hiRange - max >= 0) loRange -= Math.min(1 + hiRange - max, range);
			if (this.currentIndex === max) loRange--;
		}
		var pos = this.indexTop ? 'down' : 'up',  // popup thumbnails always go towards the center of the box
			i = 0;
		while (i < this.itemCount) {  // iterate each item and build a series of links
			if (i !== 0 && i < loRange) {  // not in range?
				str += '... ';
				i = loRange;  // jump to first in-range item
			} else if (i !== max && i > hiRange) {  // out of range?
				str += '... ';
				i = max;  // jump to last item
			} else {
				if (i !== this.currentIndex) {  // build a clickable anchor for this item
					var item = this.items[i];
					str += '<a class="fbPop' + pos + '" rel="nofloatbox" href="' + item.href +
					'" onclick="fb.newContent(' + i + '); if (window.event) event.returnValue = false; return false;">' + ++i;
					try {  // IE can throw an access denied error after reloading an iframe
						if (this.showIndexThumbs && item.thumb) {
							str += '<img src="' + item.thumb.src + '" />';
						}
					} catch(e) {}
					str += '</a> ';
				} else {
					str += ++i + ' ';  // current item is not clickable
				}
			}
		}
		if (this.setInnerHTML(this.fbIndexLinks, str)) {
			if (this.indexLinksPanel === 'info') {
				infoPanel.display = '';
			} else {
				this.tagAnchors(this.fbIndexLinks);  // to set the popup thumbs
			}
		}
	}
	// light up any floatboxed anchors in the info panel
	if (!infoPanel.display) this.tagAnchors(this.fbInfoPanel);
},  // end updatePanels

//********************************************/
// calcSize()
// Floatbox dimensions
// Side effect of setting infoPanel dimensions
//********************************************/
calcSize: function(fit, pass) {
	var that = this;
	if (!this.fbBox) return;  // might have been closed during the content fetch
	var boxX, boxY, boxW, boxH, mainW, mainH ;  // things we need to calc for setSize
// use options preference if fit is not specified
	if (typeof fit === 'undefined') {
		fit = this.currentItem.type === 'img' ? this.autoSizeImages : this.autoSizeOther;
	}
// style short-names
	var box = this.fbBox.style,
		infoPanel = this.fbInfoPanel.style,
		controlPanel = this.fbControlPanel.style,
		indexLinks = this.fbIndexLinks.style,
		captionDiv = this.fbCaptionDiv.style,
		itemNumber = this.fbItemNumber.style;
// capture current screen dimensions (showContent will compare against these)
	if (!pass) {
		this.displaySize = this.getDisplaySize();
		// caption and itemNumber may have been turned off for the previous item
		if (this.showCaption && this.fbCaption.innerHTML) captionDiv.display = '';
		if (this.lclShowItemNumber) itemNumber.display = '';
	}
// determine upper and lower panel heights including padding
	this.upperSpace = Math.max(this.infoTop ? this.fbInfoPanel.offsetHeight : 0, this.controlTop ? this.fbControlPanel.offsetHeight : 0);
	this.lowerSpace = Math.max(this.infoTop ? 0 : this.fbInfoPanel.offsetHeight, this.controlTop ? 0 : this.fbControlPanel.offsetHeight);
	if (this.upperSpace) this.upperSpace += 2*this.panelPadding;
	if (this.lowerSpace) this.lowerSpace += 2*this.panelPadding;
	this.upperSpace = Math.max(this.upperSpace, this.padding);
	this.lowerSpace = Math.max(this.lowerSpace, this.padding);
// get max content dimensions that will fit the current window
	// extra autoSize space is shadow + 1/2 autoSizeSpace, or autoSizeSpace if no shadow
	var extraSpace;
	if (this.shadowType === 'none') {
		extraSpace = 2*this.autoSizeSpace;
	} else if (this.shadowType === 'halo') {
		extraSpace = 2*this.shadowSize + this.autoSizeSpace;
	} else {
		extraSpace = this.shadowSize + 1.5*this.autoSizeSpace;
	}
	var pad = 2*(this.outerBorder + this.innerBorder) + extraSpace,
		maxW = Math.floor(this.displaySize.width - pad - 2*this.padding),
		maxH = Math.floor(this.displaySize.height - pad - this.upperSpace - this.lowerSpace),
		hardW = false, hardH = false;
// get content width & height, translate 'max' and '%' dimensions
	mainW = this.currentItem.nativeWidth + '';
	if (mainW === 'max') {
		mainW = maxW;
	} else if (mainW.substr(mainW.length - 1) === '%') {
		mainW = Math.floor(maxW * parseInt(mainW, 10) / 100);
	} else {
		mainW = parseInt(mainW, 10);  // strip 'px' off if it's there
		hardW = true;
	}
	mainH = this.currentItem.nativeHeight + '';
	if (mainH === 'max') {
		mainH = maxH;
	} else if (mainH.substr(mainH.length - 1) === '%') {
		mainH = Math.floor(maxH * parseInt(mainH, 10) / 100);
	} else {
		mainH = parseInt(mainH, 10);
		hardH = true;
	}
// scale content down if requested and needed
	this.scaledBy = this.oversizedBy = 0;
	if (fit) {
		var scaleW = maxW/mainW,
			scaleH = maxH/mainH,
			fullW = mainW, fullH = mainH;
		if (hardW && hardH) scaleW = scaleH = Math.min(scaleW, scaleH);
		if (scaleW < 1) mainW = Math.round(mainW * scaleW);
		if (scaleH < 1) mainH = Math.round(mainH * scaleH);
		this.scaledBy = Math.max(fullW - mainW, fullH - mainH);
		// undo tiny shrinkage
		if (this.scaledBy && this.scaledBy < this.outerBorder + extraSpace + this.panelPadding) {
			mainW = fullW;
			mainH = fullH;
			this.scaledBy = 0;
		} 
	}
// box dimensions (not including outerBorder or shadow)
	boxW = mainW + 2*(this.innerBorder + this.padding);
	boxH = mainH + 2*this.innerBorder + this.upperSpace + this.lowerSpace;
// if infoPanel or controlPanel height changes due to wrapping, recalc everything
	// capture current height
	var infoH = this.fbInfoPanel.offsetHeight,
		controlH = this.fbControlPanel.offsetHeight;
	// calc and set infoPanel width
	var infoW = boxW - 2*Math.max(this.padding, this.panelPadding);  // full width
	if (this.infoTop === this.controlTop && this.fbControls.offsetWidth) {
		infoW -= this.fbControls.offsetWidth + this.panelGap;  // minus controls if they're in the same panel
	}
	if (infoW < 0) infoW = 0;
	infoPanel.width = infoW + 'px';
	// calc and set indexLinks width
	if (!this.lclNumIndexLinks) {
		var indexW = 0;
	} else if (this.indexLinksPanel === 'info' || this.infoTop !== this.controlTop) {
		var indexW = infoW;
	} else if (this.indexLinksPanel !== 'info' && this.infoTop === this.controlTop && this.infoCenter) {
		var indexW = Math.max(this.minIndexWidth, this.fbControls.offsetWidth);
	} else {
		var infoUsed = Math.max(this.fbCaption.offsetWidth, this.fbInfoLink.offsetWidth + this.fbPrintLink.offsetWidth + this.fbItemNumber.offsetWidth);
		var indexW = Math.max(this.minIndexWidth, this.fbControls.offsetWidth, (boxW - infoUsed - 2*Math.max(this.padding, this.panelPadding)));
		if (infoUsed) indexW -= this.panelGap;
	}
	if (indexW) indexLinks.width = (indexW - (this.indexLinksPanel !== 'info' ? 2 : 0)) + 'px';  // we padded 2px in initPanels
	// set controlPanel width
	controlPanel.width = Math.max(indexW, this.fbControls.offsetWidth) + 'px';
	// check for change
	var changed = this.fbInfoPanel.offsetHeight !== infoH || this.fbControlPanel.offsetHeight !== controlH;
	if (this.showCaption) {  // turn off caption if there's not enough room
		if (this.minInfoWidth > infoW && !captionDiv.display) {
			captionDiv.display = 'none';
			changed = true;
		}
	}
	if (this.lclShowItemNumber) {  // turn off item number if it doesn't fit
		if (this.fbInfoLink.offsetWidth + this.fbPrintLink.offsetWidth + this.fbItemNumber.offsetWidth > infoW && !itemNumber.display) {
			itemNumber.display = 'none';
			changed = true;
		}
	}
	if (changed && pass !== 3) return this.calcSize(fit, (pass || 0) + 1);  // recalc max of 3 passes
// capture oversize
	if (!fit) this.oversizedBy = Math.max(boxW - this.displaySize.width, boxH - this.displaySize.height) + 2*this.outerBorder + extraSpace;
	if (this.oversizedBy < 0) this.oversizedBy = 0;
// autoSizeSpace adjustment for shadows
	if (this.shadowType === 'halo') {
		extraSpace = this.shadowSize + this.autoSizeSpace/2;
	} else {
		extraSpace = this.autoSizeSpace;
	}
// box left
	if (typeof this.boxLeft === 'number') {  // absolute pixel location requested
		boxX = this.boxLeft;
	} else if (mainW === maxW) {
		boxX = extraSpace;
	} else {  // center horizontally
		var freeSpace = this.displaySize.width - boxW - 2*this.outerBorder;
		boxX = Math.floor(freeSpace/2);
		if (boxX < this.autoSizeSpace) {
			boxX = this.autoSizeSpace;
		} else {
			// apply percentage offset if requested
			if (typeof this.boxLeft === 'string' && this.boxLeft.substr(this.boxLeft.length - 1) === '%') {
				boxX += parseInt(this.boxLeft, 10)/100 * boxX;
			}
		}
	}
// box top
	if (typeof this.boxTop === 'number') {  // absolute pixel location requested
		boxY = this.boxTop;
	} else if (mainH === maxH) {
		boxY = extraSpace;
	} else {  // center vertically, normally 1/3 free space above and 2/3 below, sliding to 50/50 as the box approaches screen size
		var freeSpace = this.displaySize.height - boxH - 2*this.outerBorder,
			ratio = freeSpace / this.displaySize.height, factor;
		if (ratio <= 0.15) {
			factor = 2;
		} else if (ratio >= 0.3) {
			factor = 3;
		} else {
			factor = 1 + ratio/0.15;
		}
		boxY = Math.floor(freeSpace/factor);
		if (boxY < this.autoSizeSpace) {
			boxY = this.autoSizeSpace;
		} else {
			// apply percentage offset if requested
			if (typeof this.boxTop === 'string' && this.boxTop.substr(this.boxTop.length - 1) === '%') {
				boxY += parseInt(this.boxTop, 10)/100 * boxY;
			}
		}
	}
// add screen scroll values to left and top
// first take fb components out of flow in case they are stretching the body and scroll bars
	var boxPosition = box.position;  // save current state
	if (this.ieOld) {  // ie6 can't do position fixed
		box.display = 'none';
		this.stretchOverlay()();  // the overlay might be stretching the body
	} else {  // display=none has ugly flashes in FF2 and IE8
		this.setPosition(this.fbBox, 'fixed');
	}
	var scroll = this.getScroll();
	this.setPosition(this.fbBox, boxPosition);
	box.display = '';
	boxX += scroll.left;
	boxY += scroll.top;
// child window position
	if (this.isChild) {  // half way to the parent, if parent is higher and lefter
		var rex = /max|%/i,
			pos = this.fbParent.pos.fbBox,
			childX = rex.test(this.currentItem.nativeWidth) ? 99999 : (pos.left + boxX)/2,
			childY = rex.test(this.currentItem.nativeHeight) ? 99999 : (pos.top + boxY)/2;
		if (scroll.left < childX && scroll.top < childY) {
			boxX = Math.min(boxX, childX);
			boxY = Math.min(boxY, childY);
		}
	}
// resize then zoom or show
	var split = (pos = this.pos.fbBox) && !this.liveResize && this.splitResize;
	if (split === 'auto') split = boxW - pos.width <= boxH - pos.height ? 'wh' : 'hw';
	var oncomplete2 = function() {
		that.fbBox.style.visibility ? that.zoomIn() : that.showContent();
	};
	var oncomplete = function() {
		that.setSize(split,
			{ id: 'fbBox', left: boxX, top: boxY, width: boxW, height: boxH, borderWidth: that.outerBorder },
			{ id: 'fbMainDiv', width: mainW, height: mainH, top: that.upperSpace },
			function() { that.timeouts.showContent = setTimeout(oncomplete2, 10); }
		);
	};
	this.timeouts.setSize = setTimeout(oncomplete, 10);
},  // end calcSize

//************************************************/
// setPosition()
// Set position type and adjust scroll values
//************************************************/
setPosition: function(el, position) {
	if (el.style.position === position) return;
	var scroll = this.getScroll();
	if (position === 'fixed') {  // remove scroll values for fixed
		scroll.left = -scroll.left;
		scroll.top = -scroll.top;
	}
	if (this.pos[el.id]) {  // adjust the position record
		this.pos[el.id].left += scroll.left;
		this.pos[el.id].top += scroll.top;
	}
	// add or subtract scroll and set position
	el.style.left = (el.offsetLeft + scroll.left) + 'px';
	el.style.top = (el.offsetTop + scroll.top) + 'px';
	el.style.position = position;
},  // end setPosition

//****************************************************/
// collapse()
// Preps floatbox bits for content and/or size changes
//****************************************************/
collapse: function(callback, phase) {
	var that = this;
	if (!phase) {
// need to switch away from fixed positioning else animations are very jerky
		this.setPosition(this.fbBox, 'absolute');
// resizer
		this.fbResizer.onclick = null;
		this.fbResizer.style.display = 'none';
		if (this.fbContent) {
			this.fbContent.onclick = null;
			this.fbContent.style.cursor = '';
		}
// nav overlay
		if (this.navOverlay) {
			this.fbLeftNav.style.display = this.fbRightNav.style.display =
			this.fbOverlayPrev.style.display = this.fbOverlayNext.style.display = 'none';
		}
// content
		var opacity = 0, duration = 0;
		if (this.currentItem.type === 'img' && !this.fbCanvas.style.visibility) {
			if (this.currentItem === this.lastShown && this.liveImageResize) opacity = 1;
			duration = this.imageFadeDuration;
		}
		this.liveResize = (opacity === 1);  // capturing this so we don't split the resize and can speed up resizing in resize()
		var oncomplete = function() { that.collapse(callback, 1); };
		return this.fadeOpacity(this.fbCanvas, opacity, duration, oncomplete);
	}
	// phase 1
// loader
	if (!this.liveResize) {  // show the loader gif after a short interval
		this.fbMainDiv.style.display = 'none';
		if (this.fbContent) this.fbContent.style.display = 'none';
		this.clearTimeout('loader');  // in case one is already waiting to fire
		this.timeouts.loader = setTimeout(function() { that.fbLoader.style.display = ''; }, this.loaderDelay);
	}
// info and control panels
	var infoPanel = this.fbInfoPanel.style,
		controlPanel = this.fbControlPanel.style;
	infoPanel.visibility = controlPanel.visibility = 'hidden';
	infoPanel.left = controlPanel.left = '0';
	infoPanel.top = controlPanel.top = '-9999px';
	if (callback) callback();
},  // end collapse

//************************************************************/
// restore()
// Put back floatbox bits after a collapse, swap and/or resize
//************************************************************/
restore: function(callback, phase) {
	var that = this;
	if (!phase) {
// drop shadow
		if (this.fbShadowRight.style.display && this.shadowType !== 'none') {
			this.fbShadowRight.style.display = this.fbShadowBottom.style.display = '';
			if (this.shadowType === 'halo') {
				this.fbShadowTop.style.display = this.fbShadowLeft.style.display = '';
			} else {  // drop
				this.fbShadowCorner.style.display = '';
			}
		}
// info and control panels
		var infoPanel = this.fbInfoPanel.style,
			controlPanel = this.fbControlPanel.style,
			pad = this.upperSpace + this.pos.fbMainDiv.height + 2*this.innerBorder;
		infoPanel.top = (((this.infoTop ? this.upperSpace : this.lowerSpace) - this.fbInfoPanel.offsetHeight) / 2 - 1 + (this.infoTop ? 0 : pad)) + 'px';
		controlPanel.top = (((this.controlTop ? this.upperSpace : this.lowerSpace) - this.fbControlPanel.offsetHeight) / 2 + (this.controlTop ? 0 : pad)) + 'px';
		var pad = Math.max(this.padding, this.panelPadding) + 'px';
		infoPanel.left = [this.infoLeft ? pad : ''];
		controlPanel.left = [this.controlLeft ? pad : ''];
		infoPanel.visibility = controlPanel.visibility = '';
// content
		this.clearTimeout('loader');
		this.fbLoader.style.display = 'none';
		this.fbMainDiv.style.display = this.fbContent.style.display = '';
		var duration = (this.currentItem.type === 'img' && !this.fbCanvas.style.visibility) ? this.imageFadeDuration : 0,
			oncomplete = function() { that.restore(callback, 1); };
		return this.fadeOpacity(this.fbCanvas, 1, duration, oncomplete);
	}
	// phase 1
// resizer
	if (this.currentItem.type === 'img' ? this.resizeImages : this.resizeOther) {
		var scale = 0;
		// tolerances for when to ignore need for scaling were determined by experiment
		if (this.scaledBy > 35) {
			scale = 1;  // scale up
		} else if (this.oversizedBy > 28){
			scale = -1;  // scale down
		}
		if (scale) {  // either direction
			this.fbResizer.onclick = function() {
				if (that.isSlideshow && that.pauseOnResize && !that.isPaused) {
					that.setPause(true);
				}
				that.collapse(function() { that.calcSize(scale === -1); });  // true = scale to fit, false = show native size
				if (window.event) event.returnValue = false;
				return false;
			};
			if (this.currentItem.type === 'img' && /cursor|both/.test(this.resizeTool)) {
				// show the resize cursor (opera can't do it)
				this.fbContent.style.cursor = 'url(' + (scale === -1 ? this.resizeDownCursor : this.resizeUpCursor) +'), default';
				this.fbContent.onclick = this.fbResizer.onclick;
			}
			if (this.currentItem.type !== 'img' || /topleft|both/.test(this.resizeTool)) {
				// show the resize gadget
				this.fbResizer.style.backgroundPosition = (scale === -1 ? 'bottom' : 'top');
				this.fadeOpacity(this.fbResizer, this.controlOpacity);
			}
		}
	}
// nav overlay
	if (this.navOverlay) {
		var leftNav = this.fbLeftNav.style,
			rightNav = this.fbRightNav.style,
			overlayPrev = this.fbOverlayPrev.style,
			overlayNext = this.fbOverlayNext.style;
		leftNav.width = rightNav.width = Math.max(this.navOverlayWidth/100 * this.pos.fbMainDiv.width, this.fbOverlayPrev.offsetWidth) + 'px';
		leftNav.display = rightNav.display = '';
		if (fb.showNavOverlay) {
			overlayPrev.visibility = overlayNext.visibility = 'hidden'; // to reappear on panel mouse movement
			overlayPrev.display = overlayNext.display = '';
			overlayPrev.top = overlayNext.top = ((this.pos.fbMainDiv.height - this.fbOverlayPrev.offsetHeight) * this.navOverlayPos/100) + 'px';
		}
	}
	if (callback) callback();
},  // end restore

//*************************************************************/
// setSize()
// Takes a bunch of objects describing resizing to be done
// and passes array of objects to resizeGroup to make it happen
//*************************************************************/
setSize: function(order) {
// looking for objects containing id,left,top,width,height,borderWidth (only id is mandatory)
	var that = this,
		oncomplete = function() {},  // default, override by passing a function as any argument
		arr = [[], []],  // 1st and 2nd pass resize arrays
		defer = {},  // things to defer to the 2nd pass
		node,
		i = arguments.length;
	if (order === 'wh') {
		defer.top = 1;
		defer.height = 1;
	} else if (order === 'hw') {
		defer.left = 1;
		defer.width = 1;
	}
	while (i--) {
		if (typeof arguments[i] === 'object' && (node = this[arguments[i].id])) {
			var obj = arguments[i];
			if (!this.pos[obj.id]) this.pos[obj.id] = {};  // current/starting positions for different elements are in this.pos
			for (var property in obj) {
				if (obj.hasOwnProperty(property) && property !== 'id') {
					var idx = defer[property] || 0;
					var start = this.pos[obj.id][property];  // pull current value
					if (typeof start !== 'number' || node.style.display || node.style.visibility) {
						// if no current start val or the element can't be seen, set start = end (no animation)
						start = obj[property];
					}
					arr[idx].push({ node: node, property: property, start: start, finish: obj[property] });  // one object for each attribute to be set
					if (obj.id === 'fbMainDiv') {  // resize content along with the main div
						arr[idx].push({ node: this.fbContent, property: property, start: start, finish: obj[property] });
					}
					if (obj.id === 'fbZoomDiv' && /\b(width|height)\b/i.test(property)) {  // set the zoom image width/height to match
						arr[idx].push({ node: this.fbZoomImg, property: property, start: start, finish: obj[property] });
					}
					this.pos[obj.id][property] = obj[property];  // update the record of current positions
				}
			}
		} else if (typeof arguments[i] === 'function') {
			oncomplete = arguments[i];
		}
	}
	// resize group 1 with a callback to resize group 2 with a callback of the oncomplete action
	this.resizeGroup(arr[0], function() { that.resizeGroup(arr[1], oncomplete); });
},  // end setSize

//*******************************************************************/
// showContent()
// The last thing that happens after fetching, sizing, restoring etc.
//*******************************************************************/
showContent: function(phase) {
	var that = this;
	if (!phase) {  // phase 0
		var displaySize = this.getDisplaySize();
// if a scrollbar has come or gone, we might need some further resizing or positioning done (firefox is not displaying new scroll bars until after things settle down)
		if (!this.resized) {  // once only thanks
			var vscrollChanged = displaySize.width !== this.displaySize.width,
				hscrollChanged = displaySize.height !== this.displaySize.height;
			if ((vscrollChanged && Math.abs(this.pos.fbBox.width - displaySize.width) < 50) ||
			(hscrollChanged && Math.abs(this.pos.fbBox.height - displaySize.height) < 50)) {
				this.resized = true;
				return this.calcSize(this.scaledBy);
			}
		}
		this.resized = false;  // for next time
		self.focus();  // reclaim keyboard handler
		if (this.ieOld) this.stretchOverlay()();
// fixed positioning if requested or if ff2 is showing iframe or quicktime content, but only if the browser can handle fixed and the content fits the window
// FF2 has problems in floatbox absolute divs: 1) no mouse events for flash, 2) no video for quicktime, 3) no blinking cursor in form fields
// Safari 2.x will clear out the fixed contents if the page is scrolled
// IE8 beta 2 has various layout problems with fixed positioning - hopefully fixed in the production release
		if ((this.disableScroll || (this.ffOld && /iframe|quicktime/i.test(this.currentItem.type))) && !(this.ieOld || this.webkitOld || this.ie8b2)) {
			if (this.pos.fbBox.width <= displaySize.width && this.pos.fbBox.height <= displaySize.height) {
				this.setPosition(this.fbBox, 'fixed');
			}
		}
// fetch content that was deferred
		if (this.currentItem.type === 'iframe') {
			this.fbContent.src = this.currentItem.href;
		} else if (/flash|quicktime/.test(this.currentItem.type)) {
			this.setInnerHTML(this.fbContent, this.objectHTML(this.currentItem.href,
			this.currentItem.type, this.pos.fbMainDiv.width, this.pos.fbMainDiv.height));
		}
// determine neighbour items
		this.prevIndex = this.currentIndex ? this.currentIndex - 1 : this.itemCount - 1;
		this.nextIndex = this.currentIndex < this.itemCount - 1 ? this.currentIndex + 1 : 0;
		var prevHref = this.enableWrap || this.currentIndex !== 0 ? this.items[this.prevIndex].href : '',
			nextHref = this.enableWrap || this.currentIndex !== this.itemCount - 1 ?  this.items[this.nextIndex].href : '';
// toggle nav gadgets based on wrap status & update nav hrefs (for the browser status bar display)
		if (this.navButton) {
			// set hand cursor and tooltip behaviours
			if (prevHref) {
				if (!this.operaOld) this.fbPrev.href = prevHref;  // opera prior to 9.5 gets nothing because of stupid big href url tooltips
				this.fbPrev.title = this.fbOverlayPrev.title;
			} else {
				this.fbPrev.removeAttribute('href');
				this.fbPrev.title = '';
			}
			if (nextHref) {
				if (!this.operaOld) this.fbNext.href = nextHref;
				this.fbNext.title = this.fbOverlayNext.title;
			} else {
				this.fbNext.removeAttribute('href');
				this.fbNext.title = '';
			}
			// set background image through the style sheet
			var prevOn = this.fbPrev.className.replace('_off', ''),
				nextOn = this.fbNext.className.replace('_off', '');
			this.fbPrev.className = prevOn + (prevHref ? '' : '_off');
			this.fbNext.className = nextOn + (nextHref ? '' : '_off');
		}
		if (this.navOverlay) {
			if (!this.operaOld) {
				this.fbLeftNav.href = this.fbOverlayPrev.href = prevHref;
				this.fbRightNav.href = this.fbOverlayNext.href = nextHref;
			}
			this.fbLeftNav.style.visibility = prevHref ? '' : 'hidden';
			this.fbRightNav.style.visibility = nextHref ? '' : 'hidden';
			fb.navOverlayShown = true;  // showNavOverlay=once handler
		}
// light up the content
		this.fbCanvas.style.visibility = '';
		return this.restore(function() {
			that.timeouts.showContent = setTimeout(function() { that.showContent(1); }, 10);
		} );
	}
// phase 1
	this.lastShown = this.currentItem;
// flag that we've seen this one and increment shown count if this is the first viewing of this item
	if (!this.currentItem.seen) {
		this.currentItem.seen = true;
		this.itemsShown++;
	}
// set next slideshow event timer
	if (this.isSlideshow && !this.isPaused) {
		this.timeouts.slideshow = setTimeout(function() {
			if (that.endTask === 'loop' || that.itemsShown < that.itemCount) {
				that.newContent(that.nextIndex);
			} else if (that.endTask === 'exit') {
				that.end();
			} else {  // that.endTask = 'stop' or unknown value
				that.setPause(true);
				var i = that.itemCount;
				while (i--) that.items[i].seen = false;
				that.itemsShown = 0;
			}
		}, this.slideInterval*1000);
	}
// resume preloading
	this.timeouts.preload = setTimeout(function() {
			that.preloadImages(nextHref || prevHref || '', true);
	}, 10);
},  // end showContent

//******************************************/
// objectHTML()
// Returns html object code for a given href
//******************************************/
objectHTML: function(href, type, width, height) {
	if (type === 'flash') {
		var classid = 'classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"',
			mime = 'type="application/x-shockwave-flash"',
			pluginurl = 'http://get.adobe.com/flashplayer/',
			// pick up wmode, bgcolor and scale from query string (if they're there)
			match = /\bwmode=(\w+?)\b/i.exec(href),
			wmode = match ? match[1] : 'window',  // most old browsers need wmode=window
			match = /\bbgcolor=(#\w+?)\b/i.exec(href),
			bgcolor = match ? match[1] : '',
			match = /\bscale=(\w+?)\b/i.exec(href),
			scale = match ? match[1] : 'exactfit',
			params = { wmode:wmode, bgcolor:bgcolor, scale:scale, quality:'high',
			flashvars:'autoplay=1&amp;ap=true&amp;border=0&amp;rel=0' };  // autoplay for youtube, (some) yahoo and google. ap for msn. border and rel(ated) for youtube
		if (this.ffOld) params.wmode = this.ffMac ? 'window' : 'opaque';  // ff2 on xp requires wmode=opaque for some flash, on mac it needs mode=window
		if (this.ffNew && href.indexOf('YV_YEP.swf') !== -1) params.wmode = 'window';  // ff3 requires wmode=window for some flash (only old yahoo that I've noticed)
	} else {  // quicktime
		var classid = 'classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"',
			mime = 'type="video/quicktime"',
			pluginurl = 'http://www.apple.com/quicktime/download/',
			params = { autoplay:'true', controller:'true', showlogo:'false', scale:'tofit' };
	}
	var html = '<object id="fbObject" name="fbObject" width="' + width + '" height="' + height + '" ';
	if (this.ie) {  // use active-x
		html += classid + '>';
		params[type === 'flash' ? 'movie' : 'src'] = this.encodeHTML(href);
	} else {  // use plugin
		html += mime + ' data="' + this.encodeHTML(href) + '">';
	}
	for (var name in params) {
		if (params.hasOwnProperty(name)) {
			html += '<param name="' + name + '" value="' + params[name] + '" />';
		}
	}
	if (type === 'quicktime' && this.webkitMac) {
		// safari on mac is not showing quicktime without an embed in place
		html += '<embed src="' + this.encodeHTML(href) +
		'" width="' + width + '" height="' + height + '" autoplay="true" controller="true" showlogo="false" scale="tofit" pluginspage="' +
		pluginurl + '"></embed></object>';
	} else {
		// object alternate content (hard-coded English unfortunately)
		html += '<p style="color:#000; background:#fff; margin:1em; padding:1em;">' +
		(type === 'flash' ? 'Flash' : 'QuickTime') + ' player is required to view this content.' +
		'<br /><a href="' + pluginurl + '">download player</a></p></object>';
	}
	return html;
},  // end objectHTML

//**************************************************************/
// newContent()
// Called by prev/next events, slideshow timer or external code.
// Index is position in the items array.
//**************************************************************/
newContent: function(index) {
	var that = this;
	this.clearTimeout('slideshow');
	this.clearTimeout('resize');
	this.currentIndex = index;
	fb.previousAnchor = this.currentItem;
	this.currentItem = this.items[index];  // new item to show
	// clear showing of 1st-image-only overlay nav gadgets
	if (this.showNavOverlay == 'once' && this.navOverlayShown) this.showNavOverlay = false;
	// chain in the new item load
	var oncomplete = function() {
		that.updatePanels();
		that.fetchContent(function() { that.calcSize(); });
	};
	this.collapse(function() {
		that.timeouts.fetch = setTimeout(oncomplete, 10);
	} );
},  // end newContent

//*******************************************************/
// end()
// Close down floatbox
// Called by event handlers, slideshow exit or externally
// iFrame content can call parent.fb.end() to terminate
// Div content and caption can call fb.end() to terminate
//*******************************************************/
end: function(all) {
// close in stack order
	if (this !== fb.lastChild) return fb.lastChild.end(all);
	var that = this;
	this.endAll = this.endAll || all;
	this.fbOverlay.onclick = null;
	if (this.isChild) {
		if (this.endAll) this.imageFadeDuration = this.overlayFadeDuration = this.resizeDuration = 0;
	} else {  // restore this document's keyboard handlers
		if (document.keydownSet) {
			document.onkeydown = this.priorOnkeydown;
			document.keydownSet = false;
		}
		if (document.keypressSet) {
			document.onkeypress = this.priorOnkeypress;
			document.keypressSet = false;
		}
		parent.focus();  // keyboard handler goes back to parent doc
	}
	if (this.ieOld) {
		detachEvent('onresize', this.stretchOverlay());
		detachEvent('onscroll', this.stretchOverlay());
	}
// clear any pending timeouts
	for (var key in this.timeouts) {
		if (this.timeouts.hasOwnProperty(key)) this.clearTimeout(key);
	}
// if the zoomImg is still coming in, abandon it
	if (this.fbBox.style.visibility) {
		if (!this.lastShown) this.fbZoomDiv.style.display = 'none';
// maybe animate down to a thumbnail
	} else if (this.currentItem.type === 'img' && this.zoomImageStart) {
		if (this.currentItem.popup) this.currentItem.anchor.onmouseover();  // put the popup back so we can zoom out to it
		var anchorPos = this.getAnchorPos(this.currentItem.anchor, true);
		if (this.currentItem.popup) this.currentItem.anchor.onmouseout();  // re-hide the popup
		if (anchorPos.width) {
			this.fbZoomDiv.style.borderWidth = this.zoomPopBorder + 'px';
			anchorPos.left -= this.zoomPopBorder;
			anchorPos.top -= this.zoomPopBorder;
			this.pos.thumb = anchorPos;
			return this.zoomOut();
		}
	}
// if we didn't zoom out, shrink the box
	if (!this.fbBox.style.visibility) {
		var anchorPos = this.getAnchorPos(this.currentItem.anchor, !this.currentItem.popup),
			offset = this.initialSize/2,
			initialPos = { id: 'fbBox', left: anchorPos.left - offset, top: anchorPos.top - offset, width: this.initialSize, height: this.initialSize },
			zeroPos = { id: 'fbBox', left: anchorPos.left, top: anchorPos.top, width: 0, height: 0, borderWidth: 0 },
			split = this.splitResize;
		if (split === 'wh') {  // reverse order for animating down
			split = 'hw';
		} else if (split === 'hw') {
			split = 'wh';
		} else if (split === 'auto') {
			split = this.pos.fbBox.width <= this.pos.fbBox.height ? 'hw' : 'wh';
		}
		var oncomplete3 = function() {
			setTimeout(function() {
				that.fbBox.style.visibility = 'hidden';  // flags that we've done this section
				that.end();
			}, 10);
		};
		if (split) {
			var oncomplete2 = function() {
				that.setSize(split, initialPos, function() {  // split shrink to small box
					that.setSize(zeroPos, oncomplete3);  // small box to zero
				});
			};
		} else {
			var oncomplete2 = function() {
				that.setSize(zeroPos, oncomplete3);  // shrink straight to zero
			};
		}
		var oncomplete = function() {
			// prep for shrinkage
			if (that.fbContent) {  // discard content
				that.fbMainDiv.removeChild(that.fbContent);
				delete that.fbContent;
			}
			that.fbLoader.style.display = '';
			that.fbCanvas.style.display = that.fbShadowTop.style.display = that.fbShadowRight.style.display =
			that.fbShadowBottom.style.display = that.fbShadowLeft.style.display = that.fbShadowCorner.style.display = 'none';
			oncomplete2();
		};
		// size down to zero and come back
		return this.collapse(oncomplete);
	}
	this.fbBox.style.display = 'none';
// discard tagged anchors associated with this box (they will all be at the end of the array)
	var level = this.children.length + 1,
		i = this.anchors.length;
	while(i && this.anchors[i-1].level >= level) i--;
	this.anchors.length = i;
//  update child info
	if (this.isChild) this.children.length--;
	fb.lastChild = this.children[this.children.length-1] || fb;
// do this when the overlay finishes fading out
	var oncomplete2 = function() {
		setTimeout(function() {
			// discard all floatbox elements
			while (that.nodeNames.length) {
				var id = that.nodeNames.pop();
				if (that[id] && that[id].parentNode) {
					that[id].parentNode.removeChild(that[id]);
					delete that[id];
				}
			}
			// do end tasks
			if (that.endAll && that.isChild) {
				return fb.end(true);
			} else if (that.loadPageOnClose) {
				if (that.loadPageOnClose === 'self' || that.loadPageOnClose === 'this') {
					location.reload(true);
				} else if (that.loadPageOnClose === 'back') {
					history.back();
				} else {
					location.replace(that.loadPageOnClose);
				}
			}
		}, 10);
	};
	var oncomplete = function() {
		// unhide previously hidden elements (maybe there are none)
		while(that.hiddenEls.length) {
			var el = that.hiddenEls.pop();
			el.style.visibility = 'visible';
			if (this.ffOld && this.ffMac) {  // ff2/mac helper
				el.focus();
				el.blur();
			}
		}
		var overlay = that.fbOverlay.style;
		overlay.display = 'none';
		overlay.width = overlay.height = '0';  // helps the mouseover reshow of the popup
		var duration = that.currentItem.popup ? 6.5 : 0;
		that.fbZoomDiv.style.opacity = '1';  // set initial opacity for the fade
		that.fadeOpacity( that.fbZoomDiv, 0, duration, oncomplete2);
		that.currentItem = fb.previousAnchor = null;
	};
	this.fadeOpacity(this.fbOverlay, 0, this.overlayFadeDuration, oncomplete);
},  // end end

//********************************/
// zoomIn()
// Animated start from a thumbnail
//********************************/
zoomIn: function(phase) {
	var that = this,
		zoomDiv = this.fbZoomDiv.style;
	if (!phase) {  // phase 0
// show the overlay and animate zoomDiv up to the size and position where the content div will be
		// starting position and initial src was set in initState
		this.clearTimeout('slowLoad');
		zoomDiv.display = this.fbZoomImg.style.display = '';
		if (this.currentItem.popup) this.currentItem.anchor.onmouseout();  // turn off the popup if there is one
		var pad = this.outerBorder + this.innerBorder - this.zoomPopBorder;
		var oncomplete = function () {
			that.fbZoomImg.src = that.currentItem.href;
			that.setSize(
				{ id: 'fbZoomDiv', width: that.pos.fbMainDiv.width, height: that.pos.fbMainDiv.height,
				left: that.pos.fbBox.left + pad + that.padding, top: that.pos.fbBox.top + pad + that.upperSpace },
				function() { that.zoomIn(1); } );
		};
		return this.fadeOpacity(this.fbOverlay, this.overlayOpacity, this.overlayFadeDuration, oncomplete);
	}
	if (phase === 1) {
// animate box from exactly behind zoomDiv to its final size
		var boxPos = {  // capture current (hidden) position to return back to
			left: this.pos.fbBox.left, top: this.pos.fbBox.top,
			width: this.pos.fbBox.width, height: this.pos.fbBox.height
		};
		var pad = 2*(this.zoomPopBorder - this.outerBorder);
		this.pos.fbBox = {  // set starting position
			left: this.pos.fbZoomDiv.left, top: this.pos.fbZoomDiv.top,
			width: this.pos.fbZoomDiv.width + pad, height: this.pos.fbZoomDiv.height + pad
		};
		this.fbBox.style.visibility = '';
		var oncomplete = function() {
			that.restore(function() { that.zoomIn(2); });
		};
		return this.setSize(
			{ id: 'fbBox', left: boxPos.left, top: boxPos.top,
			width: boxPos.width, height: boxPos.height},
			oncomplete);
	}
	// phase 2
// turn off zoomDiv and resume showing content
	var show = function() {
		zoomDiv.display = 'none';
		that.fbZoomImg.src = '';
		zoomDiv.left = zoomDiv.top = zoomDiv.width = zoomDiv.height = that.fbZoomImg.width = that.fbZoomImg.height = '0';
		that.showContent();
	};
	this.timeouts.showContent = setTimeout(show, 10);
},  // end zoomIn

//********************************/
// zoomOut()
// Animated end down to a thumnail
//********************************/
zoomOut: function(phase) {
	var that = this;
	if (!phase) {  // phase 0
// place zoomDiv over top of current content
		this.fbZoomImg.src = this.currentItem.href;
		var pad = this.outerBorder + this.innerBorder - this.zoomPopBorder;
		this.setSize(
		{ id: 'fbZoomDiv', width: this.pos.fbMainDiv.width, height: this.pos.fbMainDiv.height,
		left: this.pos.fbBox.left + pad + this.padding, top: this.pos.fbBox.top + pad + this.upperSpace },
		function() { that.zoomOut(1); } );
	}
	if (phase === 1) {
// show zoomDiv and hide the 'normal' content
		this.fbZoomDiv.style.display = this.fbZoomImg.style.display = '';
		this.fbCanvas.style.visibility = 'hidden';
		return this.collapse(function() { that.zoomOut(2); });
	}
	if (phase === 2) {
// shrink box to behind zoomDiv
		var pad = 2*(this.zoomPopBorder - this.outerBorder);
		return this.setSize(
			{ id: 'fbBox', left: this.pos.fbZoomDiv.left, top: this.pos.fbZoomDiv.top,
			width: this.pos.fbZoomDiv.width + pad, height: this.pos.fbZoomDiv.height + pad },
			function() { that.zoomOut(3); }
		);
	}
	// phase 3
// shrink zoomDiv down to the thumbnail
	this.fbBox.style.visibility = 'hidden';  // a flag to end() that says we've zoomed
	var end = function() {
		that.fbZoomImg.src = that.pos.thumb.src;
		that.end();
	};
	this.setSize(
		{ id: 'fbZoomDiv', left: this.pos.thumb.left, top: this.pos.thumb.top,
		width: this.pos.thumb.width, height: this.pos.thumb.height },
		end);
},  // end zoomOut

//********************************************/
// setPause()
// Sets slideshow state to paused or playing
// and displays the appropriate control button
//********************************************/
setPause: function(pause) {
	this.isPaused = pause;
	if (pause) {
		this.clearTimeout('slideshow');  // clear pending slideshow event
	} else {
		this.newContent(this.nextIndex);  // launch the next item
	}
	if (this.showPlayPause) {  // show the appropriate control
		this.fbPlay.style.top = pause ? '' : '-9999px';
		this.fbPause.style.top = pause ? '-9999px' : '';
	}
},  // end setPause

//**************************************************************/
// fadeOpacity()
// Changes opacity in graduated steps through timers
// This is a setup function for stepFade() which does the work
// Can fade in or out
//**************************************************************/
fadeOpacity: function(el, opacity, duration, callback) {
	var startOp = +(el.style.opacity || 0);
	duration = duration || 0;
	// block any currently running fade for this element
	this.clearTimeout['fade' + el.id];
// fading in?
	var fadeIn = (startOp <= opacity && opacity > 0);
// calc the % increment for each iteration
	if (duration > 10) duration = 10;
	if (duration < 0) duration = 0;
	if (duration === 0) {
		startOp = opacity;
		var incr = 1;  // won't get used, but keep it here for cleanliness
	} else {
// magic log math that yields nice increments.  duration=1 -> incr=50%, 5 -> 9%, 10 -> 1%
		var root = Math.pow(100, 0.1),
			power = duration + ((10 - duration)/9) * (Math.log(2)/Math.log(root) - 1),
			incr = 1/Math.pow(root, power);
	}
	if (fadeIn) {
		el.style.display = el.style.visibility = '';
	} else {
		incr = -incr;
	}
// set initial opacity values and next timer event
	this.stepFade(el, startOp, opacity, incr, fadeIn, callback);
},  // end fadeOpacity

//********************************************************************/
// stepFade()
// Worker bee function for fadeOpacity()
// Applies opacity styles and maybe sets timer for next fade increment
//********************************************************************/
stepFade: function(el, thisOp, finishOp, incr, fadeIn, callback) {
	if (!el) return;  // safety for mid-fade ends
	var that = this;
// don't go beyond the finish state
	if ((fadeIn && thisOp >= finishOp) || (!fadeIn && thisOp <= finishOp)) thisOp = finishOp;
// set opacity styles
	if (this.ie) el.style.filter = 'alpha(opacity=' + thisOp*100 + ')';
	el.style.opacity = thisOp + '';
	if (thisOp === finishOp) {
// we're done. clear ie filter and run on-complete code
		if (this.ie && finishOp >= 1) el.style.removeAttribute('filter');  // fix for IE alpha opacity filter bug (from lytebox)
		if (callback) callback();
	} else {
// set timer for next step of opacity fade
		this.timeouts['fade' + el.id] = setTimeout(function() { that.stepFade(el, thisOp + incr, finishOp, incr, fadeIn, callback); }, 20);
	}
},  // end stepFade

//************************************************************************************************/
// resizeGroup()
// Does a graduated change of a group of pixel attributes together as a unit
// The set of objects, attributes and values to be set are in the passed arr parameter
// This is a setup function for stepResize() which does the actual property changes and timer sets
//************************************************************************************************/
resizeGroup: function(arr, callback) {
// resize everything in the array together (for smooth effect)
// arr is an array of objects with keys of { node, property, start, finish }
	var i = arr.length;
	if (!i) return callback ? callback() : null;
// clear any pending resizes
	this.clearTimeout('resize');
// calc maximum size differential
	var diff = 0;
	while (i--) {
		diff = Math.max(diff, Math.abs(arr[i].finish - arr[i].start));
	}
	var duration = this.resizeDuration * (this.liveResize ? 0.65 : 1);  // speed up live resizing
// resize rate is a log function of the diff size. makes a nice balance of speed and time.
// rate is the fractional amount of diff to do on each iteration (e.g., .1 for 10 increments).
	var rate = diff && duration ? Math.pow(Math.max(1, 2.2 - duration/10), (Math.log(diff))) / diff : 1;
// calc pixel differential for each element
	i = arr.length;
	while (i--) arr[i].diff = arr[i].finish - arr[i].start;
// set initial size and timers for resize steps
	this.stepResize(rate, rate, arr, callback);
},  // end resizeGroup

//****************************************************************/
// stepResize()
// Worker bee function for resizeGroup()
// Applies dimension styles and sets timer for next size increment
//****************************************************************/
stepResize: function(increment, rate, arr, callback) {
	var that = this;
// apply size changes to the elements listed in the passed array
	if (increment > 1) increment = 1;  // don't go beyond final value
// for each element in this array, extract the parameters and apply this iteration's size changes
	var i = arr.length;
	while (i--) {
		var node = arr[i].node,
			prop = arr[i].property,
			val = Math.round(arr[i].start + arr[i].diff * increment),
			tag = node.tagName.toLowerCase();
		if (tag === 'img' || tag === 'iframe') {  // width and height are set directly for these two
			node[prop] = val;
		} else {  // everything else is style
			node.style[prop] = val + 'px';
		}
	}
// are we done?
	if (increment >= 1) {
		delete this.timeouts.resize;
		// run requested on-complete code
		if (callback) callback();
	} else {
// set a timer for the next iteration
		this.timeouts.resize = setTimeout(function() { that.stepResize(increment + rate, rate, arr, callback); }, 20);
	}
},  // end stepResize

//******************************************************/
// getDisplaySize()
// getDisplayWidth()
// getDisplayHeight()
// Width and height of the browser's current view portal
//******************************************************/
getDisplaySize: function() {
	return { width: this.getDisplayWidth(), height: this.getDisplayHeight() };
},  // end getDisplaySize

getDisplayWidth: function() {
	// width is easy.  If the documentElement width is given and not 0, it is correct.  Otherwise the body width is correct.
	return this.html.clientWidth || this.bod.clientWidth;
},  // end getDisplayWidth

getDisplayHeight: function() {
	if (this.webkitOld) return window.innerHeight;
	if (!this.html.clientHeight || this.operaOld || document.compatMode === 'BackCompat') {
		// IEMac, Opera pre 9.5, others w. no doctype
		// For opera pre 9.5, body.clientHeight is the closest measurement but there's
		// a bug that excludes the body border from the reported height.
		// Removed code to adjust for body border now that 9.5 is out (body borders are rare and small, pre 9.5 will soon become rare)
		return this.bod.clientHeight;
	}
	// all others with doctypes
	return this.html.clientHeight;
},  // end getDisplayHeight

//********************************************************/
// getScroll()
// Return pixels by which the window is currently scrolled
//********************************************************/
getScroll: function(win) {
	if (!(win && win.document)) win = self;
	var doc = win.document,
		html = doc.documentElement,
		bod = doc.body || doc.getElementsByTagName('body')[0],
		left = win.pageXOffset || bod.scrollLeft || doc.documentElement.scrollLeft || 0;
	// IE adjustment on rtl pages (see http://www.hiermenuscentral.com/bulletins/9/5.html)
	if (this.ie && this.rtl) left -= html.scrollWidth - html.clientWidth;
	return {
		left: left,
		top: win.pageYOffset || bod.scrollTop || doc.documentElement.scrollTop || 0
	};
},  // end getScroll

//**********************************************************/
// getLeftTop()
// Returns left & top coordinates of an element
// Iterates through parent objects adding up all the offsets
// If not local, iframe offsets will be added in too
// (the hardest and messiest function in floatbox)
//**********************************************************/
getLeftTop: function(el, local) {
	var left = el.offsetLeft || 0,
		top = el.offsetTop || 0,
		doc = el.ownerDocument || el.document,
		bod = doc.body || doc.getElementsByTagName('body')[0],
		win = doc.defaultView || doc.parentWindow || doc.contentWindow,  // win is this element's owner window (except on safari pre 3.x)
		scroll = this.getScroll(win),
		position = this.getStyle(el, 'position', win),
		// flag if el is not in the base page layout flow
		rex = /absolute|fixed/,
		elFlow = !rex.test(position),
		inFlow = elFlow,
		node = el;
	if (position === 'fixed') {  // scroll values not included in fixed offsets
		left += scroll.left;
		top += scroll.top;
	}
	while (position !== 'fixed' && (node = node.offsetParent)) {  // for each offset parent
		var borderLeft = 0,
			borderTop = 0,
			nodeFlow = true,
			position = this.getStyle(node, 'position', win),
			nodeFlow = !rex.test(position);
		if (this.opera) {
			if (local && node !== bod) {
				// add in scroller values and take out border widths (applies to local positioning too)
				left += node.scrollLeft - node.clientLeft;
				top += node.scrollTop - node.clientTop;
			}
		} else if (this.ie) {
			if (node.currentStyle.hasLayout && node !== doc.documentElement) {
				// borders to be added in later (not for local position)
				borderLeft = node.clientLeft;
				borderTop = node.clientTop;
			}
		} else {  // FF, Chrome & Safari
			// borders to be added in later (not for local position)
			borderLeft = parseInt(this.getStyle(node, 'border-left-width', win), 10);
			borderTop = parseInt(this.getStyle(node, 'border-top-width', win), 10);
			if (this.ff && node === el.offsetParent && !nodeFlow && (this.ffOld || !elFlow)) {  // FF adjustment
				// no clue why, but this is needed and it works
				// applies to both local and top positioning
				left += borderLeft;
				top += borderTop;
			}
		}
		if (!nodeFlow) {
			// if local coords were requested, stop at an absolute or fixed parent element
			if (local) return { left: left, top: top };
			inFlow = false;  // flag that we're not in the base layout flow
		}
		// add in offsets and borders
		if (node.offsetLeft > 0) left += node.offsetLeft;  // don't add in -ve offsets from ie6 in rtl mode
		left += borderLeft;
		top += node.offsetTop + borderTop;
		if (position === 'fixed') {  // scroll values not included in fixed offsets
			left += scroll.left;
			top += scroll.top;
		}
		if (!(this.opera && elFlow) && node !== bod && node !== doc.documentElement) {
			// take out scroll values
			left -= node.scrollLeft;
			top -= node.scrollTop;
		}
	}
	if (this.ff && inFlow) {  // add in body border for FF in layout flow
		left += parseInt(this.getStyle(bod, 'border-left-width', win), 10);
		top += parseInt(this.getStyle(bod, 'border-top-width', win), 10);
	}
// add in containing iframe offset
	// hack to get the true window object for Safari pre 3.x
	// from http://code.google.com/p/doctype/wiki/ArticleParentWindow
	if (this.webkitOld) {
		var scriptElement = doc.createElement('script');
		scriptElement.innerHTML = 'document.parentWindow=self';
		doc.documentElement.appendChild(scriptElement);
		doc.documentElement.removeChild(scriptElement);
		win = doc.parentWindow;
	}
	// if el is in an iframe, find that iframe
	if (!local && win !== self) {
		var iframes = win.parent.document.getElementsByTagName('iframe'),
			i = iframes.length;
		while (i--) {
			var node = iframes[i],
				idoc = false;
			try {  // in case of cross-domain script blocking
				idoc = node.contentDocument || node.contentWindow;  // iframe's document, or maybe its window
				idoc = idoc.document || idoc;  // now it's the doc for sure
			} catch(e) {}
			// if this iframe contains the element we are measuring...
			if (idoc === doc || (typeof idoc !== 'object' && node.src === win.location.href.substr(win.location.href.length - node.src.length))) {
				if (this.webkitOld) win = doc.defaultView;  // set win var back for Safari 2.x for getStyle calls
				// add iframe offsets and scroll value
				var pos = this.getLeftTop(node);
				left += pos.left - scroll.left;
				top += pos.top - scroll.top;
				if (this.ie || this.opera) {
					// add iframe border and padding for IE and Opera
					var padLeft = 0, padTop = 0;
					if (!this.ie || elFlow) {  // but don't add iframe padding for ie out-of-flow elements
						padLeft = parseInt(this.getStyle(node, 'padding-left', win), 10);
						padTop = parseInt(this.getStyle(node, 'padding-top', win), 10);
					}
					left += node.clientLeft + padLeft;
					top += node.clientTop + padTop;
				} else {
					// add iframe border and padding for FF, Chrome and Safari
					left += parseInt(this.getStyle(node, 'border-left-width', win), 10) +
					parseInt(this.getStyle(node, 'padding-left', win), 10);
					top += parseInt(this.getStyle(node, 'border-top-width', win), 10) +
					parseInt(this.getStyle(node, 'padding-top', win), 10);
				}
				break;
			}
		}
	}
	return { left: left, top: top };
},  // end getLeftTop

//******************************************************/
// getStyle()
// Returns current/computed style for element's property
//******************************************************/
getStyle: function(el, prop, win) {
	if (!(el && prop)) return '';
	if (!win) {
		var doc = el.ownerDocument || el.document;
		win = doc.defaultView || doc.parentWindow || doc.contentWindow;  // win is this element's owner window (except on safari pre 3.x)
	}
	if (el.currentStyle) {
		return el.currentStyle[prop.replace(/-(\w)/g, function(match, p1) { return p1.toUpperCase(); })] || '';
	} else {
		if (!win) {
			var doc = el.ownerDocument || el.document;
			win = doc.defaultView || doc.parentWindow || doc.contentWindow;  // win is this element's owner window (except on safari pre 3.x)
		}
		return (win.getComputedStyle && win.getComputedStyle(el, '').getPropertyValue(prop)) || '';
	}
},  // end getStyle

//********************************************************/
// getLayout()
// Adds width and height (including borders) to getLeftTop
//********************************************************/
getLayout: function(el) {
	var lay = this.getLeftTop(el);
	lay.width = el.offsetWidth;
	lay.height = el.offsetHeight;
	return lay;
},  // end getLayout

//**************************************/
// clearTimeout()
// Cancels pending timeout of type 'key'
//**************************************/
clearTimeout: function(key) {
	if (this.timeouts[key]) {
		clearTimeout(this.timeouts[key]);
		delete this.timeouts[key];
	}
},  // end clearTimeout

//********************************/
// stretchOverlay()
// IE6 window resize event handler
//********************************/
stretchOverlay: function() {
	var that = this;
	return function() {
// avoid repeated screen flashes by waiting until browser resizing is complete before redrawing the overlay
		if (arguments.length === 1) {  // it's from a resize event
			that.clearTimeout('stretch');  // cancel pending timeout
			that.timeouts.stretch = setTimeout(function() { that.stretchOverlay()(); }, 25);  // set a new one for a bit later
		} else {  // called directly or from a surviving timer
			delete that.timeouts.stretch;
			if (!that.fbBox) return;  // floatbox may have ended
			var width = that.fbBox.offsetLeft + that.fbBox.offsetWidth,
				height = that.fbBox.offsetTop + that.fbBox.offsetHeight,
				display = that.getDisplaySize(),
				scroll = that.getScroll(),
				overlay = that.fbOverlay.style;
			overlay.width = overlay.height = '0';  //shrink the overlay before measuring the doc body
			// adjust for rtl goofy measurements
			var rtlAdjust = (that.rtl && scroll.left) ? that.html.clientWidth - that.html.scrollWidth : 0;
			overlay.left = rtlAdjust + 'px';
			// size overlay up to the biggest dimensions we can measure
			overlay.width = Math.max(width, that.bod.scrollWidth, that.bod.clientWidth, that.html.clientWidth, display.width + scroll.left) + 'px';
			overlay.height = Math.max(height, that.bod.scrollHeight, that.bod.clientHeight, that.html.clientHeight, display.height + scroll.top) + 'px';
		}
	};
},  // end stretchOverlay

//***************************/
// encodeHTML(), decodeHTML()
// encode/decode &<>"'
//***************************/
encodeHTML: function(str) {
	return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');  // &amp; first
},
decodeHTML: function(str) {
	return str.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'").replace(/&#39;/g, "'");  // &amp; last
},

//****************************************************/
// getXMLHttpRequest()
// Browser transparent setup of XMLHttpRequest object.
// Returns getResponse function.
//****************************************************/
getXMLHttpRequest: function() {
	var xhr, that = this;
	// get the object
	if (window.XMLHttpRequest) {  // manly browsers
		if (!(xhr = new XMLHttpRequest())) return false;
	} else {  // old ie
		try { xhr = new ActiveXObject("Msxml2.XMLHTTP"); } catch(e) {
			try { xhr = new ActiveXObject("Microsoft.XMLHTTP"); } catch(e) { return false; }
		}
	}
	return {
		// call .getResponse() with a url to fetch and a callback function to execute
		// callback can access the xhr object through its passed parameter
		getResponse: function(url, callback) {
			try {
				xhr.open('GET', url, true);  // asynchronous
				xhr.setRequestHeader('If-Modified-Since', 'Thu, 1 Jan 1970 00:00:00 GMT');
				xhr.setRequestHeader('Cache-Control', 'no-cache');
				xhr.onreadystatechange = function() {
					if (xhr.readyState === 4) {
						xhr.onreadystatechange = function() {};
						callback(xhr);
					}
				};
				xhr.send(null);
			} catch(e) {}
		}
	};
},  // end getXMLHttpRequest

//*******************************************/
// setInnnerHTML()
// Uses dom calls to simulate .innerHTML
// (necessary for xhtml-xml application docs)
//*******************************************/
setInnerHTML: function(el, strHTML) {
	try {
		// empty the element
		var range = document.createRange();
		range.selectNodeContents(el);
		range.deleteContents();
		// insert new elements
		if (strHTML) {
			var xmlDiv = new DOMParser().parseFromString('<div xmlns="http://www.w3.org/1999/xhtml">' + strHTML + '</div>', 'application/xhtml+xml'),
				childNodes = xmlDiv.documentElement.childNodes;
			for (var i = 0, len = childNodes.length; i < len; i++) {
				el.appendChild(document.importNode(childNodes[i], true));
			}
		}
		return true;
	} catch (e) {}
	// if the dom methods didn't work, try setting good ol' innerHTML
	try {
		el.innerHTML = strHTML;
		return true;
	} catch(e) {}
	return false;
},  // end setInnerHTML

//***********************************************************/
// printContents()
// Print an element or the current contents from a new window
//***********************************************************/
printContents: function(el, style) {
	if (el && el.offsetWidth) {  // existing element passed
		var width = el.offsetWidth,
			height = el.offsetHeight;
	} else {  // use floatbox content if no element specified
		el = fb.lastChild.fbContent;
		var pos = fb.lastChild.pos.fbMainDiv,
			width = pos.width,
			height = pos.height;
	}
	// create a new browser instance
	var win = window.open('', '', 'width=' + width + ', height=' + height),
		doc = win && win.document;
	if (!doc) {  // was it blocked?
		alert('Popup windows are being blocked by your browser.\nUnable to print.');
		return false;
	}
	// optional passed style can be a css file path or a string of style definitions
	if (/\.css$/i.test(style)) {
		style = '<link rel="stylesheet" type="text/css" href="' + style + '" />';
	} else {
		style = '<style type="text/css"> html,body{border:0;margin:0;padding:0;}' + (style || '') + '</style>';
	}
	// put content into a div so we can read it with innerHTML for browsers that don't support outerHTML
	var div = document.createElement('div');
	div.appendChild(el.cloneNode(true));
	doc.open('text/html');
	doc.write('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"' +
	' "http://www.w3.org/TR/html4/loose.dtd"><html><head>' +
	style + '</head><body><div>' + div.innerHTML + '</div></body></html>');
	doc.close();
	// timeout to give the new window a chance to render
	setTimeout(function() { win && win.print(); win && win.close(); }, 200);
	return true;
},  // end printContents

//**********************************************/
// loadAnchor()
// Utility API function
// Adds a new anchor to floatbox's collection of
// activated anchors and then displays it.
// First parameter can be an anchor element.
//**********************************************/
loadAnchor: function(href, rev, title) {
	if (href.setAttribute) {  // it's an anchor, not a string
		var anchor = href;
		if (!anchor.getAttribute('rel')) anchor.setAttribute('rel', 'floatbox');
		fb.lastChild.start(this.tagOneAnchor(anchor));
	} else {
		fb.lastChild.start(this.tagOneAnchor({ href: href, rev: rev, title: title, rel: 'floatbox' }));
	}
},  // end loadAnchor

//*************************************/
// goBack()
// Utility API function
// Reverts to the previous started item
//*************************************/
goBack: function() {
	var a = fb.previousAnchor;
	if (a) this.loadAnchor(a.href, a.rev + ' sameBox:true', a.title);
},  // end goBack

//****************************/
// resize()
// Utility API function
// Resize the top box in place
//****************************/
resize: function(width, height) {
	var changed = false;
	if (width && fb.lastChild.currentItem && fb.lastChild.currentItem.nativeWidth != width) {
		fb.lastChild.currentItem.nativeWidth = width;
		changed = true;
	}
	if (height && fb.lastChild.currentItem && fb.lastChild.currentItem.nativeHeight != height) {
		fb.lastChild.currentItem.nativeHeight = height;
		changed = true;
	}
	if (changed) fb.lastChild.calcSize(false);
}  // end resize

};  // end Floatbox.prototype

//***************************/
// initfb()
// Create the floatbox object
//***************************/
function initfb() {
	if (arguments.callee.done) return;  // init once only
	var fbWindow = 'self';  // will try to attach as far up the iframe tree as we can
	if (self !== parent) {
		try {  // permission denied if cross-site
			if (self.location.host === parent.location.host && self.location.protocol === parent.location.protocol) fbWindow = 'parent';
		} catch(e) {}
		// parent-first initialization (every page in the nested iframe chain must have floatbox.js included)
		if (fbWindow === 'parent' && !parent.fb) return setTimeout(initfb, 50);
	}
	arguments.callee.done = true;
	// quirks mode not allowed
	if (document.compatMode === 'BackCompat') {
		alert('Floatbox does not support quirks mode.\nPage needs to have a valid a doc type.');
		return;
	}
	fb = (fbWindow === 'self' ? new Floatbox() : parent.fb);  // global var fb points as far up the iframe chain as it can go (restricted to same domain)
	fb.tagAnchors(self.document.body || self.document.getElementsByTagName('body')[0]);  // attach behaviours to the anchors in this document
	if (fb.autoStart) {  // run autoStart if requested
		fb.start(fb.autoStart);
		if (typeof fb !== 'undefined') delete fb.autoStart;
	} else {  // if not auto-starting, start the image preload chain
		fb.preloadImages('', true);
	}
}  // end initfb 

//****************************************/
// Initialize floatbox as soon as possible
//****************************************/
if (document.addEventListener) {  // most modern browsers
	document.addEventListener('DOMContentLoaded', initfb, false);
}
(function() {
	/*@cc_on  // IE via conditional compilation
	if (document.body) {
		try {
			document.createElement('div').doScroll('left');  // doScroll doesn't until the dom is fully loaded
			return initfb();
		} catch(e) {}
	}
	/*@if (false) @*/  // older webkit & khtml browsers
	if (/loaded|complete/.test(document.readyState)) return initfb();
	/*@end @*/
	if (!initfb.done) setTimeout(arguments.callee, 50);  // conditional in case .readyState is not supported and window.onload has fired
})();
fb_prevOnload = window.onload;
window.onload = function() {  // all browsers, but should fire later than the above attempts
	if (arguments.callee.done) return;  // in case js file has been included twice on a page (block infinite loop through fb_prevOnload)
	arguments.callee.done = true;
	if (typeof fb_prevOnload === 'function') fb_prevOnload();
	initfb();
};
