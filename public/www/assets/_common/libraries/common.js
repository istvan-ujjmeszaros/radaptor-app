window.SetUrl = function(file_url, b, c, caption)
{
    if (window.opener.CKEDITOR)
    {
        var funcnum = document.location.toString().replace(/.*CKEditorFuncNum=([0-9]*)[^0-9].*/, '$1');
        window.opener.CKEDITOR.tools.callFunction(funcnum, file_url);
        setTimeout('window.close()', 1);
    }
}

function closeDiv($div)
{
    jQuery($div).animate({
        opacity: 'hide',
        height: 'hide'
    }, 'slow');
}

function refreshSystemMessages(URL, context, event)
{
    jQuery.getJSON(
            URL + '?context=' + context + '&event=' + event,
            function(data)
            {
                // Unwrap API envelope if present
                var messages = data;
                if (data && data.ok === true && data.data) {
                    messages = data.data;
                }
                if (messages && !Array.isArray(messages)) {
                    messages = Object.values(messages);
                }
                if (!messages || !messages.length) return;
                jQuery.each(messages, function(i, v)
                {
                    jQuery.gritter.add({
                        title: v.header,
                        text: v.body,
                        image: v.icon,
                        sticky: v.sticky
                    });
                });
            }
    );
}

// Mindig az aktuális oldal url-ét veszi alapul,
// így nincs cross-domain hívás, tehát ez így
// mindig működik...
function getCurrentUrlPath()
{
    urlPath = window.location.href.split("/");

    return urlPath[0] + "//" + urlPath[2];
}

function renderSystemMessages()
{
    var _context = 'systemmessages',
            _event = 'renderSystemMessages';

    refreshSystemMessages(getCurrentUrlPath(), _context, _event);
}

function sprintf()
{
    if (!arguments || arguments.length < 1 || !RegExp)
    {
        return '';
    }
    var str = arguments[0];
    var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
    var a = b = [], numSubstitutions = 0, numMatches = 0;
    while (a = re.exec(str))
    {
        var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
        var pPrecision = a[5], pType = a[6], rightPart = a[7];

        //alert(a + '\n' + [a[0], leftpart, pPad, pJustify, pMinLength, pPrecision);

        numMatches++;
        if (pType == '%')
        {
            subst = '%';
        }
        else
        {
            numSubstitutions++;
            if (numSubstitutions >= arguments.length)
            {
                alert('Error! Not enough function arguments (' + (arguments.length - 1) + ', excluding the string)\nfor the number of substitution parameters in string (' + numSubstitutions + ' so far).');
            }
            var param = arguments[numSubstitutions];
            var pad = '';
            if (pPad && pPad.substr(0, 1) == "'")
                pad = leftpart.substr(1, 1);
            else if (pPad)
                pad = pPad;
            var justifyRight = true;
            if (pJustify && pJustify === "-")
                justifyRight = false;
            var minLength = -1;
            if (pMinLength)
                minLength = parseInt(pMinLength);
            var precision = -1;
            if (pPrecision && pType == 'f')
                precision = parseInt(pPrecision.substring(1));
            var subst = param;
            if (pType == 'b')
                subst = parseInt(param).toString(2);
            else if (pType == 'c')
                subst = String.fromCharCode(parseInt(param));
            else if (pType == 'd')
                subst = parseInt(param) ? parseInt(param) : 0;
            else if (pType == 'u')
                subst = Math.abs(param);
            else if (pType == 'f')
                subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision) : parseFloat(param);
            else if (pType == 'o')
                subst = parseInt(param).toString(8);
            else if (pType == 's')
                subst = param;
            else if (pType == 'x')
                subst = ('' + parseInt(param).toString(16)).toLowerCase();
            else if (pType == 'X')
                subst = ('' + parseInt(param).toString(16)).toUpperCase();
        }
        str = leftpart + subst + rightPart;
    }
    return str;
}

function URLEncode(c)
{
    var o = '';
    var x = 0;
    c = c.toString();
    var r = /(^[a-zA-Z0-9_.]*)/;
    while (x < c.length) {
        var m = r.exec(c.substr(x));
        if (m != null && m.length > 1 && m[1] != '') {
            o += m[1];
            x += m[1].length;
        } else {
            if (c[x] == ' ')
                o += '+';
            else {
                var d = c.charCodeAt(x);
                var h = d.toString(16);
                o += '%' + (h.length < 2 ? '0' : '') + h.toUpperCase();
            }
            x++;
        }
    }
    return o;
}
;

function URLDecode(s)
{
    var o = s;
    var binVal, t;
    var r = /(%[^%]{2})/;
    while ((m = r.exec(o)) != null && m.length > 1 && m[1] != '') {
        b = parseInt(m[1].substr(1), 16);
        t = String.fromCharCode(b);
        o = o.replace(m[1], t);
    }
    return o;
}
;

function getUrlParameter(param_name)
{
    param_name = param_name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regexS = "[\\?&]" + param_name + "=([^&#]*)";
    var regex = new RegExp(regexS);
    var results = regex.exec(window.location.href);
    if (results == null)
        return "";
    else
        return results[1];
}

function getParameter(param_name, str)
{
    param_name = param_name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regexS = "[\\?&]" + param_name + "=([^&#]*)";
    var regex = new RegExp(regexS);
    var results = regex.exec(str);
    if (results == null)
        return "";
    else
        return results[1];
}


function setParameter(url, key, value)
{
    key = escape(key);
    value = escape(value);

    var kvp = url.split('&');

    var i = kvp.length;
    var x;
    while (i--)

    {
        x = kvp[i].split('=');

        if (x[0] == key)
        {
            x[1] = value;
            kvp[i] = x.join('=');
            break;
        }
    }

    if (i < 0) {
        kvp[kvp.length] = [key, value].join('=');
    }

    //this will reload the page, it's likely better to store this until finished
    return kvp.join('&');
}

function urldecode(str)
{
    return decodeURIComponent(str).replace(/\+/g, '%20');
}

function openEditor()
{
    if (now_open)
    {
        now_open = false;
        jQuery('#button_szerkeszt').click();
    }
}

function unique(arrayName)
{
    var newArray = new Array();
    label:for (var i = 0; i < arrayName.length; i++)
    {
        for (var j = 0; j < newArray.length; j++)
        {
            if (newArray[j] == arrayName[i])
                continue label;
        }
        newArray[newArray.length] = arrayName[i];
    }
    return newArray;
}

function publish(id, jstreeId, publishUrl)
{
    jQuery.ajax({
        method: "get",
        url: publishUrl,
        data: "id=" + id,
        dataType: "json",
        cache: false,
        success: function(r)
        {
            var result = (r && r.ok === true && r.data) ? r.data : r;
            if (result && result.parent_data) {
                refreshTree(result.parent_data, jstreeId);
            }
        },
        complete: function()
        {
            renderSystemMessages();
        }
    });
}

function unpublish(id, jstreeId, unpublishUrl)
{
    jQuery.ajax({
        method: "get",
        url: unpublishUrl,
        data: "id=" + id,
        dataType: "json",
        cache: false,
        success: function(r)
        {
            var result = (r && r.ok === true && r.data) ? r.data : r;
            if (result && result.parent_data) {
                refreshTree(result.parent_data, jstreeId);
            }
        },
        complete: function()
        {
            renderSystemMessages();
        }
    });
}

/***********************************************************************/
function strip_tags(input, allowed) {
    // Strips HTML and PHP tags from a string
    //
    // version: 1009.2513
    // discuss at: http://phpjs.org/functions/strip_tags
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Luke Godfrey
    // +      input by: Pul
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +      input by: Alex
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Marc Palau
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Eric Nagel
    // +      input by: Bobby Drake
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Tomasz Wesolowski
    // +      input by: Evertjan Garretsen
    // +    revised by: Rafał Kukawski (http://blog.kukawski.pl/)
    // *     example 1: strip_tags('<p>Kevin</p> <b>van</b> <i>Zonneveld</i>', '<i><b>');
    // *     returns 1: 'Kevin <b>van</b> <i>Zonneveld</i>'
    // *     example 2: strip_tags('<p>Kevin <img src="someimage.png" onmouseover="someFunction()">van <i>Zonneveld</i></p>', '<p>');
    // *     returns 2: '<p>Kevin van Zonneveld</p>'
    // *     example 3: strip_tags("<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>", "<a>");
    // *     returns 3: '<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>'
    // *     example 4: strip_tags('1 < 5 5 > 1');
    // *     returns 4: '1 < 5 5 > 1'
    // *     example 5: strip_tags('1 <br/> 1');
    // *     returns 5: '1  1'
    // *     example 6: strip_tags('1 <br/> 1', '<br>');
    // *     returns 6: '1  1'
    // *     example 7: strip_tags('1 <br/> 1', '<br><br/>');
    // *     returns 7: '1 <br/> 1'
    allowed = (((allowed || "") + "")
            .toLowerCase()
            .match(/<[a-z][a-z0-9]*>/g) || [])
            .join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
    var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
            commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
    return input.replace(commentsAndPhpTags, '').replace(tags, function($0, $1) {
        return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
    });
}
function in_array(needle, haystack, argStrict)
{
    // Checks if the given value exists in the array
    //
    // version: 1009.2513
    // discuss at: http://phpjs.org/functions/in_array
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: vlado houba
    // +   input by: Billy
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: in_array('van', ['Kevin', 'van', 'Zonneveld']);
    // *     returns 1: true
    // *     example 2: in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'});
    // *     returns 2: false
    // *     example 3: in_array(1, ['1', '2', '3']);
    // *     returns 3: true
    // *     example 3: in_array(1, ['1', '2', '3'], false);
    // *     returns 3: true
    // *     example 4: in_array(1, ['1', '2', '3'], true);
    // *     returns 4: false
    var key = '', strict = !!argStrict;

    if (strict) {
        for (key in haystack) {
            if (haystack[key] === needle) {
                return true;
            }
        }
    } else {
        for (key in haystack) {
            if (haystack[key] == needle) {
                return true;
            }
        }
    }

    return false;
}


/* -------------------------------------------------------------------------- */

function resizeDataTable(tableId)
{
    var margin = 60;
    var wrapperDiv = tableId + "_wrapper";
    var windowHeight = jQuery(window).height();

    if (!jQuery(wrapperDiv).offset())
        return;

    var wrapperTop = jQuery(wrapperDiv).offset().top;
    var wrapperHeight = jQuery(wrapperDiv).height();
    var scrollerHeight = jQuery(wrapperDiv + " .dataTables_scrollBody").height();
    var dataTableAdditionalHeight = wrapperHeight - scrollerHeight;
    var newScrollHeight = windowHeight - wrapperTop - dataTableAdditionalHeight - margin;
    //var newScrollHeight = windowHeight - dataTableAdditionalHeight - 50;
    if (newScrollHeight < 100)
        newScrollHeight = 100;
    /*    console.log(
     windowHeight,
     wrapperTop,
     wrapperHeight,
     scrollerHeight,
     dataTableAdditionalHeight,
     newScrollHeight
     );*/

    jQuery(wrapperDiv + " .dataTables_scrollBody").height(newScrollHeight);
}

function getTimeDiffDisplayText(dateText, startText, endText, labels)
{
    var normalizedDate = dateText || '';
    var normalizedStart = startText || '';
    var normalizedEnd = endText || '';
    var dict = labels || {};
    var hourLabel = dict.hour || 'h';
    var minuteLabel = dict.minute || 'm';
    var lessThanOneMinuteLabel = dict.lessThanOneMinute || '<1m';

    var start = new Date(normalizedDate + 'T' + normalizedStart + ':00');
    var end = new Date(normalizedDate + 'T' + normalizedEnd + ':00');

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return '';
    }

    if (end < start) {
        end.setDate(end.getDate() + 1);
    }

    var diffMinutes = Math.floor((end.getTime() - start.getTime()) / 60000);
    var diffHours = Math.floor(diffMinutes / 60);
    var minutesRemainder = diffMinutes % 60;

    if (diffHours > 0) {
        return diffHours + ' ' + hourLabel + ' ' + minutesRemainder + ' ' + minuteLabel;
    }

    if (minutesRemainder === 0) {
        return lessThanOneMinuteLabel;
    }

    return minutesRemainder + ' ' + minuteLabel;
}

function stripTags(s)
{
    return s.replace(/<\/?[^>]+>/gi, '');
}

function zeroPad(num, places) {
    var zero = places - num.toString().length + 1;
    return Array(+(zero > 0 && zero)).join("0") + num;
}

function split(val) {
    return val.split(/,\s*/);
}
function extractLast(term) {
    return split(term).pop();
}

function getScrollTop() {
    var scrOfY = 0;
    if (typeof(window.pageYOffset) === 'number') {
        //Netscape compliant
        scrOfY = window.pageYOffset;
    } else if (document.body && (document.body.scrollTop)) {
        //DOM compliant
        scrOfY = document.body.scrollTop;
    } else if (document.documentElement && (document.documentElement.scrollTop)) {
        //IE6 standards compliant mode
        scrOfY = document.documentElement.scrollTop;
    }
    return scrOfY;
}

/**
 * Compatibility shim for widgettype.jstree.deleteRecursive().
 * Old dina_content templates call this from onclick handlers.
 * Dispatches custom events that Stimulus controllers listen for.
 */
if (typeof(widgettype) != "object")
    var widgettype = {};

if (!widgettype.jstree) {
    widgettype.jstree = {};
}

/**
 * Compatibility shim for refreshTree() called by publish/unpublish.
 * In jsTree 3.x, we simply refresh the tree instance.
 */
function refreshTree(parentData, jstreeId)
{
    var treeEl = document.getElementById(jstreeId);
    if (treeEl && jQuery.fn.jstree && jQuery(treeEl).jstree(true)) {
        jQuery(treeEl).jstree(true).refresh();
    }
}

widgettype.jstree.deleteRecursive = function(jstreeId, nodeIds, deleteUrl)
{
    if (!confirm("Biztosan törölni szeretné?")) return;

    var ids = Array.isArray(nodeIds) ? nodeIds : [nodeIds];

    jQuery.ajax({
        method: "get",
        url: deleteUrl,
        data: { "id[]": ids },
        dataType: "json",
        cache: false,
        success: function(r) {
            var result = (r && r.ok === true && r.data) ? r.data : r;

            // Try to find the Stimulus controller's tree and remove node
            var treeEl = document.getElementById(jstreeId);
            if (treeEl && jQuery.fn.jstree && jQuery(treeEl).jstree(true)) {
                var inst = jQuery(treeEl).jstree(true);
                var lastParentId = null;
                ids.forEach(function(id) {
                    var node = inst.get_node(String(id));
                    if (node) {
                        lastParentId = node.parent;
                        inst.delete_node(node);
                    }
                });
                if (lastParentId && lastParentId !== '#') {
                    inst.select_node(lastParentId);
                }
            }
        },
        complete: function() {
            renderSystemMessages();
        }
    });
}
