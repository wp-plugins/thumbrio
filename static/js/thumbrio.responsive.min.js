(function(thumbrio) {
    'use strict';

    function addEvent(obj, type, fn) {
        if (obj.attachEvent) {
            obj['e' + type + fn] = fn;
            obj[type + fn] = function() {
                obj['e' + type + fn](window.event);
            };
            obj.attachEvent('on' + type, obj[type + fn]);
        } else {
            obj.addEventListener(type, fn, false);
        }
    }

    function removeEvent(obj, type, fn) {
      if (obj.detachEvent) {
        obj.detachEvent('on' + type, obj[type + fn]);
        obj[type + fn] = null;
      } else {
        obj.removeEventListener(type, fn, false);
      }
    }

    var options = thumbrio.options || {};


    // Set the thumbrio options:
    // tagLabel -- in case of we would like use div instead of img
    // classTh -- the classname of a image that thumbrio should process
    // roundGap -- only return images with a width multiply of this value
    var defaults = {
        tagLabel: 'img',
        classTh: 'thumbrio-responsive',
        roundGap: 10
    };

    /**
     * Compute the DPR of current viewport
     */
    var getDpr = function() {
        var dpr = window.devicePixelRatio || 1;
        if (dpr > 1.25 && dpr < 1.75) {
            dpr = 1.5;
        } else {
            dpr = Math.max(Math.round(dpr), 1);
        }
        return dpr;
    };


    var setOptions = function(options) {
        thumbrio.options = defaults;
        if (options) {
            for (var key in options) {
                thumbrio.options[key] = options[key];
            }
        }
    };

    var parseQueryArguments = function(url) {
        var iQuestion = url.indexOf('?');
        if (iQuestion === -1) {
            return {};
        }
        var query = url.substr(iQuestion + 1);
        if (query === '') {
            return {};
        }
        var vars = query.split('&');
        var qa = {};
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            qa[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
        }
        return qa;
    };

    var serializeQueryArguments = function(qa, baseUrl) {
        var baseUrlWoQa = baseUrl.split('?', 1)[0];
        var qaList = [];
        for (var k in qa) {
            qaList.push(k + '=' + qa[k]);
        }
        var qaStr = qaList.join('&');
        if (qaStr) {
            return baseUrlWoQa + '?' + qaStr;
        }
        else {
            return baseUrlWoQa;
        }
    };

    var resetImgSrcFn = function(img, url) {
        return function() {
            if (img.getAttribute('src') !== url) {
                img.setAttribute('src', url);
            }
        };
    };

    /**
     * Run the Thumbrio's transformation of image's urls
     */
    var thumbriorize = function() {
        var opts = this.options;
        var imgs = document.querySelectorAll(opts.tagLabel + '.' + opts.classTh);
        for (var i = 0; i < imgs.length; i++) {
            var img = imgs[i];
            var style = img.currentStyle || getComputedStyle(img);
            var width = parseInt(style.width, 10);
            var delta = width % opts.roundGap;
            width = width + (delta ? (opts.roundGap - delta) : 0);

            // in case of error fetching the Thumbr.io image, fallback to the original
            var originalUrl = img.getAttribute('data-original-src');
            if (originalUrl) {
                addEvent(img, 'error', resetImgSrcFn(img, originalUrl));
            }

            var url = img.getAttribute('data-src');
            if (!url) {
                continue;
            }

            var qa = parseQueryArguments(url);
            qa.size = width + 'x';
            var dpr = getDpr();
            if (dpr !== 1) {
                qa.dpr = dpr;
            }

            var tbUrl = serializeQueryArguments(qa, url);
            img.setAttribute('src', tbUrl);
        }
    };

    setOptions({});
    // Public methods
    thumbrio.run = thumbriorize;

}(window.thumbrio = window.thumbrio || {}));

window.onload = function() {
    window.thumbrio.run();
};
