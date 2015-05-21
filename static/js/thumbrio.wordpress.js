WP_AUXILIAR = {
    createElement: function(tagName, attributes) {
        var doc = document.createElement(tagName);
        var key;
        for (key in attributes) {
            doc.setAttribute(key, attributes[key]);
        }
        return doc;
    },
    makeHttpRequest: function (method, url, headers, formdata, async, callback) {
        var xmlhttp, k;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
        }
        xmlhttp.onreadystatechange = function() {
            callback(xmlhttp);
        };
        xmlhttp.open(method, url, async);
        if (headers) {
            for (k in headers) {
                xmlhttp.setRequestHeader(k, headers[k]);
            }
        }
        if (formdata) {
            var formdataStr = '';
            for (k in formdata) {
                formdataStr += k + '=' + formdata[k] + '&';
            }
            formdataStr = formdataStr.slice(0, formdataStr.length - 1);
            xmlhttp.send(formdataStr);
        } else {
            xmlhttp.send();
        }
        return xmlhttp;
    },
    showMessage: function (messageText, ok) {
        var classname = ok ? 'updated' : 'error';
        // Erase Thumbrio-notice
        var messageDiv = document.getElementById('message');
        if (!messageDiv) {
            messageDiv = WP_AUXILIAR.createElement('div', {'id': 'message', 'class': classname +' below-h2'});
            h2Title = document.querySelector('.wrap>h2');
            h2Title.parentNode.insertBefore(messageDiv, h2Title.nextSibling);
        }
        messageDiv.innerHTML = '<p>' + messageText + '</p>';
        // Used to redraw the message div
        messageDiv.style.display = 'none';
        messageDiv.style.display = 'block';
    }
};

/// TODO: REwrite the code 
/* *************************************************************************
 * ADD BUTTON SYNCHRONIZE
 * *************************************************************************
*/

function createButtonSynchronize(imgSrc, siteUrl) {
    var callbackSynchronizeBucket = function (xmlhttp) {
        if (xmlhttp.readyState == 4 && xmlhttp.status > 0) {
            var response = xmlhttp.response;
            var objResponse = JSON.parse(response);
            var ok = true;
            var message;
            console.log(objResponse.text);
            switch(String(objResponse.text)) {
                case '0':
                    message = 'No new image was added.'
                    break;
                case '1':
                    message = 'A new image was added.'
                    break;
                case 'error':
                    message = 'There was an error during synchronization.'
                    ok = false;
                    break;
                default:
                    message = objResponse.text + ' new images were added.'
            }
            WP_AUXILIAR.showMessage(message, ok);
            setTimeout(function() {location.search = '';}, 1000);
            return false;
        }
    }
    var btnDoAction = document.querySelector('#doaction');
    var html = (
        "\n\t<button id=\"thumbrio-synchronize-btn\" class='button action'>" +
        "\n\t\t<img src=\"" + imgSrc + "\" width='30' height='30' style='display:inline-block; vertical-align:inherit;'/>" + 
        "Synchronize" +
        "\n\t</button>"
    );
    btnDoAction.insertAdjacentHTML('afterend', html);
    var btn = document.getElementById('thumbrio-synchronize-btn');
    btn.onclick = function (ev) {
        ev.preventDefault();
        var data = {'action': 'sync'};
        var headers = {'Content-type': "application/x-www-form-urlencoded"};
        var xmlhttp = WP_AUXILIAR.makeHttpRequest('POST', siteUrl + '/wp-admin/admin-post.php', headers, data, true, callbackSynchronizeBucket);
        console.log('Request send');
        WP_AUXILIAR.showMessage('Synchronizing, please be patient. It could take several minutes', true);
    };
}


/* *************************************************************************
 * Thumbr.io Settings
 * *************************************************************************
*/

function controlAdmin(thFields) {
    var elementsSettings = {
        passwordField: document.querySelector('input[name="adminpass"]'),
        password2Field: document.querySelector('input[name="adminpass-repeated"]'),
        adminemailField: document.querySelector('input[name="adminemail"]'),
        formSignup: document.querySelector('#signup-form'),

        classnameOk: 'updated',
        classnameError: 'error',
    };
    function appendMessage(classname, message) {
        var element = document.querySelector('.wrap .' + classname);
        if (!element) {
            element = WP_AUXILIAR.createElement('div', {'id': 'message',  'class': classname});
            element.innerHTML = message;
            var wrapper = document.querySelector('.wrap');
            var oldMessage = wrapper.querySelector('#message');
            if (oldMessage)
                oldMessage.remove();
            wrapper.insertBefore(element, wrapper.children[0]);
        } else {
            element.innerHTML = message;
        }
    }
    elementsSettings.passwordField.oninvalid = function() {
        if (this.validity.patternMismatch) {
            this.setCustomValidity('Type a password greater than 8 characters');
        } else {
            this.setCustomValidity('');
        }
        return true;
    };
    elementsSettings.password2Field.oninvalid = function() {
        if (this.validity.patternMismatch) {
            this.setCustomValidity('Type a password greater than 8 characters');
        } else {
            this.setCustomValidity('');
        }
        return true;
    };
    elementsSettings.adminemailField.oninvalid = function() {
        if (this.validity.patternMismatch) {
            this.setCustomValidity('Type a valid email');
        } else {
            this.setCustomValidity('');
        }
        return true;
    };
    elementsSettings.formSignup.onsubmit = function(e) {
        if (elementsSettings.passwordField.value != elementsSettings.password2Field.value) {
            appendMessage(elementsSettings.classnameError, 'These passwords do not match');
            e.preventDefault();
            return false;
        }
    };
}




