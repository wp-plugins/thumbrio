function saveUserAndPassword() {
    function addElement2Form(type, name, value) {
        var element = document.createElement('input');
        element.setAttribute('type', type);
        element.setAttribute('name', name);
        element.setAttribute('value', value);
        document.getElementsByTagName('form')[0].appendChild(element);
    }
    function addErrorMessage(name) {
        var error = document.getElementsByClassName('errorMessage');
        var element;
        if (error.length === 0) {
            element = document.createElement('p');
            element.innerHTML = name;
            element.setAttribute('class', 'errorMessage');
            document.getElementsByTagName('form')[0].appendChild(element);
        } else {
            element = error[0];
            element.innerHTML = name;
        }
    }
    function getXHRRequestObject () {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
        }
        return xmlhttp;
    }
    function _thumbrioUrlencode(str) {
        function _thumbrioEscapeSingle(c) {
            return '%' + c.charCodeAt(0).toString(16).toUpperCase();
        }
        return encodeURIComponent(str).replace(/[~!'()\*]/g, _thumbrioEscapeSingle).replace(/%2F/g, '/');
    }
    document.forms[0].onsubmit = function (ev) {
        var len = document.forms[0].getElementsByTagName('input').length;
        var ok = document.forms[0].getElementsByTagName('input')[len - 1].type === 'hidden';
        if (ok) {
            return true;
        }
        var xhr = getXHRRequestObject();
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var res = JSON.parse(xhr.response);
                addElement2Form('hidden', 'thumbrio_amazon_s3_bucket_name', res.bucket_name);
                addElement2Form('hidden', 'thumbrio_amazon_s3_secret_key', res.private_key);
                addElement2Form('hidden', 'thumbrio_amazon_s3_access_key', res.public_key);
                addElement2Form('hidden', 'thumbrio_storage_settings', res.storage_settings);
                document.forms[0].submit.click();
            } else if (xhr.readyState == 4) {
                addErrorMessage('<strong>Error</strong>: ' + xhr.response);
            }
        };
        if (document.getElementsByName('amazon_s3_bucket_name').length > 0) {
            return true;
        }
        var apiKey = document.getElementsByName('thumbrio_api_key')[0].value;
        var secretKey = document.getElementsByName('thumbrio_secret_key')[0].value;
        var url = 'https://www.thumbr.io/get/default_amazon_data?api_key=' + apiKey;
        var string2Sign = _thumbrioUrlencode(url);

        xhr.open('GET', url + '&signature=' + hex_hmac_md5(secretKey, string2Sign.replace(/\/\//g, '%2F%2F')));
        xhr.send();
        return false;
    };
}

function thumbrio(apiKey, secretKey, url, size, thumbName, queryArguments, baseUrl) {
    function _thumbrioUrlencode(str) {
        function _thumbrioEscapeSingle(c) {
            return '%' + c.charCodeAt(0).toString(16).toUpperCase();
        }
        var encodedStr = encodeURIComponent(str);
        return encodedStr.replace(/[~!'()\*]/g, _thumbrioEscapeSingle).replace(/%2F/g, '/');
    }
    thumbName = thumbName || 'thumb.png';
    var encodedUrl = _thumbrioUrlencode(url.replace(/^http:\/\//, ''));
    var encodedSize = _thumbrioUrlencode(size);
    var encodedThumbName = _thumbrioUrlencode(thumbName);
    var path = encodedUrl + '/' + encodedSize + '/' + encodedThumbName;
    if (queryArguments) {
        if (queryArguments[0] == '?') {
            path += queryArguments;
        }
        else {
            path += '?' + queryArguments;
        }
    }
    // We should add the API to the URL when we use the non customized
    // thumbr.io domains
    path = apiKey + '/' + path;
    // some bots (msnbot-media) "fix" the url changing // by /, so even if
    // it's legal it's troublesome to use // in a URL.
    path = path.replace(/\/\//g, '%2F%2F');
    // In node.js: var token = crypto.createHmac('md5', secretKey)
    //                  .update(baseUrl + path).digest('hex');
    var token = hex_hmac_md5(secretKey, baseUrl + path);
    return baseUrl + token + '/' + path;
}
function selectAnImage() {
    var gallery = document.body.getElementsByClassName('thumbrio-gallery')[0];
    var listImages = gallery.getElementsByTagName('img');
    var i, className;
    for (i = 0; i < listImages.length; i++) {
        listImages[i].onclick = function () {
            className = this.getAttribute('class');
            if (className && className.match(/selected/)) {
                className = className.replace(/selected/gi, '').replace(/ +/gi, ' ');
                this.style.margin = '10px';
            } else {
                className = (className + ' selected').replace(/ +/gi, ' ').replace(/^ +/gi, '');
                this.style.margin = '4px';
            }
            this.setAttribute('class', className);
        };
    }
}
function insertImageIntoDatabase(submitButton, formElement) {
    submitButton.onclick = function() {
        function isSelected(image) {
            var className = image.getAttribute('class');
            if (className && className.match(/selected/)) {
                return true;
            }
            return false;
        }
        function addElement(tag, type, name, value) {
            var element = document.createElement(tag);
            element.setAttribute('type', type);
            element.setAttribute('value', value);
            element.setAttribute('name', name);
            return element;
        }
        function isUnique (imageSrc, inputs) {
            var i = 0;
            for (i = 0; i < inputs.length; i++) {
                if (imageSrc === inputs[i].getAttribute('value')) {
                    return false;
                }
            }
            return true;
        }
        var src;
        var listImages = formElement.getElementsByTagName('img');
        var inputsHidden = formElement.getElementsByTagName('input');
        for (i = 0; i < listImages.length; i++) {
            src = listImages[i].getAttribute('alt');
            if (isSelected(listImages[i]) && isUnique(src, inputsHidden)) {
                formElement.appendChild(addElement('input', 'hidden', 'thumbrio_file_names[]', src));
            }
        }
        return true;
    };
}
function initializeDropzone(apiKey, secretKey, bucketName, accessKey, settingUploaderDefault) {
    function applyUploader(settings_uploader_default) {
        var aux = document.getElementsByClassName('dropzone');
        var constraintsDropzone, uploaderDropzone1;
        if (aux) {
            constraintsDropzone = {
                addRemoveLinks: true,
                view: 'tb-upload-large',
            };
            uploaderDropzone1 = new UploaderThumbrio(aux[0], constraintsDropzone);
            uploaderDropzone1.setApiKey(apiKey);
        }
    }
    if (!secretKey || !bucketName || !accessKey || !settingUploaderDefault) {
        document.getElementById('thumbrio-uploader-menu').style.display = 'none';
        document.getElementById('thumbrio-gallery-menu').style.display = 'none';
        return false;
    }
    applyUploader();
}
function initializeEditorImages() {
    function buildEditor(url, size) {
        var element = document.createElement('div');
        element.setAttribute('class', 'editorThumbrit');
        element.setAttribute('data-image-url', url);
        element.style.width = size + 'px';
        element.style.height = size + 'px';
        element.style['background-color'] = '#000';
        return element;
    }
    function buildUrl(infoUrl){
        function stringifyQueryArguments(queries) {
            var query;
            var res = '';
            for (query in queries) {
                res += query + '=' + queries[query] + '&';
            }
            return res.slice(0, res.length - 1);
        }
        var queryArguments = stringifyQueryArguments(infoUrl.queryArguments);
        return thumbrio(
            infoUrl.apiKey, SECRET_KEY_THUMBRIO, unescape(infoUrl.url), infoUrl.options,
            infoUrl.seoName, queryArguments, BASE_URL_THUMBRIO);
    }
    function parseUrl(url) {
        function parseQueryArguments(query) {
            var queries = query.match(/[^?\?\&]+/gi);
            var key, value, i;
            var res = {};
            for (i = 0; i < queries.length; i++) {
                key = queries[i].match(/^[^\=]+/);
                if (key)
                    res[key[0]] = queries[i].substr(key[0].length + 1);
            }
            return res;
        }
        var newUrl;
        newUrl = url.replace(/^https?\:\/\/([^\/]+\/){2}/, '');
        var apiKey = newUrl.match(/^[^\/]+/)[0];
        newUrl = newUrl.replace(/^[^\/]+\//, '');
        var queryArguments = newUrl.match(/\?[^\?]+$/);
        if (queryArguments){
            queryArguments = parseQueryArguments(queryArguments[0]);
            newUrl = newUrl.replace(/\?[^\?]+$/, '');
        }
        var seoName = newUrl.match(/[^\/]+\.[^\/\.]+$/)[0];
        newUrl = newUrl.replace(/\/[^\/]+\.[^\/\.]+$/, '');
        var options = newUrl.match(/[^\/]+$/)[0];
        newUrl = newUrl.replace(/\/[^\/]+$/, '');
        return {
            url: newUrl,
            options: options,
            queryArguments: queryArguments,
            seoName: seoName,
            apiKey: apiKey
        };
    }
    // Change src in image
    var image = document.getElementsByClassName('thumbnail')[0];
    var infoUrl = parseUrl(image.src);
    infoUrl['queryArguments']['thumbrit-edit'] = infoUrl['queryArguments']['thumbrit-apply'];
    image.parentElement.appendChild(buildEditor(buildUrl(infoUrl), 600));

    // Hide the button edit
    document.getElementsByClassName('button')[3].style.display = 'none';
    image.style.display = 'none';
    var options = {
        showPanelsOn: 'hover',
        elements: '.editorThumbrit'
    };
    window.editorThumbrit('initialize', options);
}

function getTab(tabName) {
    var menuItems = document.getElementsByClassName('media-menu-item');
    var i;
    for (i = 0; i < menuItems.length; i++) {
        if (menuItems[i].textContent == tabName) {
            return menuItems[i];
        }
    }
    return null;
}
function controlSelectionPanels () {
    function addClass(element, newClass) {
        var className = element.getAttribute('class');
        className = className.replace(RegExp(newClass, 'gi'), '') + ' ' + newClass;
        element.setAttribute('class', className);
    }
    function removeClass(element, class2remove) {
        var className = element.getAttribute('class');
        className = className.replace(RegExp(class2remove, 'gi'), '');
        element.setAttribute('class', className);
    }
    var uploadFilesTab = getTab('Upload Files');
    var mediaLibraryTab = getTab('Media Library');
    uploadFilesTab.onclick = function () {
        document.body.getElementsByClassName('thumbrio-media-upload-files')[0].style.display = 'block';
        document.body.getElementsByClassName('thumbrio-media-media-library')[0].style.display = 'none';
        removeClass(mediaLibraryTab, 'active');
        addClass(this, 'active');
    };
    mediaLibraryTab.onclick = function () {
        document.body.getElementsByClassName('thumbrio-media-upload-files')[0].style.display = 'none';
        document.body.getElementsByClassName('thumbrio-media-media-library')[0].style.display = 'block';
        removeClass(uploadFilesTab, 'active');
        addClass(this, 'active');
    };
}
