WP_AUXILIAR = {
    createElement: function(tagName, attributes) {
        var doc = document.createElement(tagName);
        var key;
        for (key in attributes) {
            doc.setAttribute(key, attributes[key]);
        }
        return doc;
    },
};


/* *************************************************************************
 * Thumbr.io Settings
 * *************************************************************************
*/

function controlAdmin(thFields) {
    var elementsSettings = {
        // iframe: document.querySelector('#hiddeniframe'),
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


