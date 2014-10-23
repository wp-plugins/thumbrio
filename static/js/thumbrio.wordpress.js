WP_AUXILIAR = {
    checkStatusId: 0,
    createElement: function(tagName, attributes) {
        var doc = document.createElement(tagName);
        var key;
        for (key in attributes) {
            doc.setAttribute(key, attributes[key]);
        }
        return doc;
    },
    getXHRRequestObject: function() {
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

