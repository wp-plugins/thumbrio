WP_AUXILIAR = {
    checkStatusId: 0,
    createElement: function (tagName, attributes) {
        var doc = document.createElement(tagName);
        var key;
        for (key in attributes) {
            doc.setAttribute(key, attributes[key]);
        }
        return doc;
    },
    _thumbrioUrlencode: function(str) {
        function _thumbrioEscapeSingle(c) {
            return '%' + c.charCodeAt(0).toString(16).toUpperCase();
        }
        return encodeURIComponent(str).replace(/[~!'()\*]/g, _thumbrioEscapeSingle).replace(/%2F/g, '/');
    },
    thumbrio: function(secretKey, url, queryArguments, baseUrl, signed, apiKey) {
        var encodedUrl = WP_AUXILIAR._thumbrioUrlencode(url.replace(/^http:\/\//, ''));
        var path = encodedUrl;
        var token = '';
        if (!queryArguments) {
            queryArguments = '';
        }
        if (queryArguments) {
            if (queryArguments[0] !== '?') {
                path += '?';
            }
            path += queryArguments;
        }
        // some bots (msnbot-media) "fix" the url changing // by /, so even if
        // it's legal it's troublesome to use // in a URL.
        if (apiKey) {
            apiKey = apiKey + '/';
            path = apiKey + path.replace(/\/\//g, '%2F%2F') + '/x/thumb.png';
        }
        path = apiKey + path.replace(/\/\//g, '%2F%2F');
        if (signed) {
            token = hex_hmac_md5(secretKey, baseUrl + path) + '/';
        }
        return baseUrl + token + path;
    },
    buildUrl: function(infoUrl, secretKey, baseDomain, signatureRequired, apiKey){
        function stringifyQueryArguments(queries) {
            var query;
            var res = '';
            for (query in queries) {
                res += query + '=' + queries[query] + '&';
            }
            return res.slice(0, res.length - 1);
        }
        var queryArguments = stringifyQueryArguments(infoUrl.queryArguments);
        return WP_AUXILIAR.thumbrio(
            secretKey, unescape(infoUrl.url), queryArguments,
            baseDomain, signatureRequired, apiKey);
    },
    parseUrl: function(url, signed) {
        function parseQueryArguments(query) {
            var queries = query.match(/[^?\?\&]+/gi);
            var key, value, i;
            var res = {};
            for (i = 0; i < queries.length; i++) {
                key = queries[i].match(/^[^\=]+/);
                if (key && !key[0].match(/^__.+/)) {
                    res[key[0]] = queries[i].substr(key[0].length + 1);
                }
            }
            return res;
        }
        var newUrl;
        newUrl = url;
        var queryArguments = newUrl.match(/\?[^\?]+$/);
        if (queryArguments){
            queryArguments = parseQueryArguments(queryArguments[0]);
            newUrl = newUrl.replace(new RegExp('\\?[^\\?]+$'), '');
        }
        var domain = newUrl.match('^(https?\:\/\/[^\/]+)\/');
        if (domain) {
            domain = domain[1];
            newUrl = newUrl.replace(new RegExp('^https?\\:\\/\\/[^\\/]+\\/'), '');
        }
        var signature = null;
        if (signed) {
            signature = newUrl.match('^([^/]+)\/');
            if (signature) {
                signature = signature[1];
                newUrl = newUrl.replace(new RegExp('^[^\\/]+\\/'), '');
            }
        }
        return {
            domain: domain,
            hmac_token: signature,
            url: newUrl,
            queryArguments: queryArguments
        };
    },
    getXHRRequestObject: function () {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
        }
        return xmlhttp;
    },
    removeElementsClass: function(element, classname) {
        var previousMessages = element.getElementsByClassName(classname);
        var i;
        for (i = previousMessages.length - 1; i >= 0; i--) {
            previousMessages[i].remove();
        }
    },
    updateUrl: function (src, signed, secretKey, baseDomain, query_arguments, apiKey) {
        var infoUrl = WP_AUXILIAR.parseUrl(src, signed);
        var key;
        infoUrl['queryArguments']['thumbrio-edit'] = infoUrl['queryArguments']['thumbrio-apply'];
        if (query_arguments) {
            for (key in query_arguments) {
                infoUrl['queryArguments'][key] = query_arguments[key];
            }
        }
        return WP_AUXILIAR.buildUrl(infoUrl, secretKey, baseDomain, signed, apiKey);
    },
    getToken: function() {
        return new Date().getTime();
    },
    addNonSignedArgument: function (url) {
        var token = '__v=' + WP_AUXILIAR.getToken();
        if (url.indexOf('?') >= 0) {
            if (url.indexOf('?') < (url.length - 1)){
                url += '&';
            }
        } else {
            url += '?';
        }
        url += token;
        return url;
    }
};

/* *************************************************************************
 * Thumbr.io Settings
 * *************************************************************************
*/
function saveUserAndPassword(thumbrioService) {
    var elementsSettings = {
        boxSubmit: document.querySelector('.submit'),
        btnSubmit: document.querySelector('#submit'),
        form: document.querySelector('form'),
        iframe: document.querySelector('#hiddeniframe'),
        classnameOk: 'updated',
        classnameError: 'error',
        classnameResult: 'wordpress-value',
        messageOk: '<strong>Everything went ok</strong>. Now, all your images will be served by your domain of thumbr.io',
        unexpectedErrorMessage: '<strong>There was an unexpected error</strong>',
    };
    function addElement2Form(classname, type, name, value) {
        var element = WP_AUXILIAR.createElement('input', {'type': type, 'name': name, 'value': value, 'class': classname});
        elementsSettings.form.appendChild(element);
    }
    function appendMessage(classname, message) {
        if (!document.querySelector('.wrap .' + classname)) {
            var element = WP_AUXILIAR.createElement('div', {'id': 'message',  'class': classname});
            element.innerHTML = message;

            var wrapper = document.querySelector('.wrap');
            var oldMessage = wrapper.querySelector('#message');
            if (oldMessage)
                oldMessage.remove();

            wrapper.insertBefore(element, wrapper.children[0]);
            WP_AUXILIAR.removeElementsClass(elementsSettings.boxSubmit, 'quick_status');
        }
    }
    function responseHttp(xhr) {
        if (xhr.readyState == 4 && xhr.status == 200) {
            appendMessage(elementsSettings.classnameOk, elementsSettings.messageOk);

            // DO THE POST
            elementsSettings.form.onsubmit = null;
            elementsSettings.form.submit.click();
            elementsSettings.form.onsubmit = onSubmitFunction;
            return false;
        } else if (xhr.readyState === 4) {
            var message = xhr.response;
            if (message.length === 0)
                message = elementsSettings.unexpectedErrorMessage;
            appendMessage(elementsSettings.classnameError, '<strong>Error</strong>: ' + message);
        }
    }
    function onSubmitFunction(ev) {
        WP_AUXILIAR.removeElementsClass(document, 'error');
        WP_AUXILIAR.removeElementsClass(document, 'updated');

        // Call to thumbrioService
        var dataInfo = {
            webdir: encodeURIComponent(document.querySelector('input[name="thumbrio_webdir"]').value),
            baseUrl: encodeURIComponent(document.querySelector('input[name="thumbrio_subdomain"]').value)
        };

        if (dataInfo.webdir.length === 0 || dataInfo.baseUrl.length === 0) {
            if (dataInfo.webdir.length === 0) {
                appendMessage(elementsSettings.classnameError, 'The field <strong>webdir</strong> is mandatory. ' +
                    'To consult all your subdomains click <a href="https://www.thumbr.io/profile/hostname">here</a>.');
            } else {
                appendMessage(elementsSettings.classnameError, 'The field <strong>subdomain</strong> is mandatory. ' +
                    'To consult all your subdomains click <a href="https://www.thumbr.io/profile/hostname">here</a>.');
            }
            return false;
        }
        var xhr = WP_AUXILIAR.getXHRRequestObject();
        xhr.onreadystatechange = function () {responseHttp(xhr);};
        xhr.open('GET', thumbrioService + '?subdomain=' + dataInfo.baseUrl + '.thumbr.io' + '&webdir=' + dataInfo.webdir);
        xhr.send();
        return false;
    }
    elementsSettings.form.onsubmit = onSubmitFunction;
}

