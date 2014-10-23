(function(thumbrio) {
    'use strict';


    var options = thumbrio.options || {};

    // Set the thumbrio options....
    // tagLabel -- in case of we would like use div instead of img
    // className -- the name class thumbrio work with
    // roundGap -- used for only build images width values are multiples of roundGap
    var defaults = {
        tagLabel: 'img',
        className: 'thumbrio-responsive',
        roundGap: 10,
    };

    // Compute the DRP of viewport
    var getDpr = function() {
        var dpr = window.devicePixelRatio || 1;
        if (dpr > 1.25 && dpr < 1.75) {
            dpr = 1.5;
        } else {
            dpr = Math.max(Math.round(dpr), 1);
        }
        return dpr;
    }();


    var setOptions = function(options) {
        thumbrio.options = defaults;
        if (options) {
            for (var key in options) {
                thumbrio.options[key] = options[key];
            }
        }
    };


    // Get elements in thumbrio's class
    var getImages = function(tag, className) {
        tag = tag || this.options.tagLabel;
        className = className || this.options.className;
        return document.querySelectorAll(tag + '.' + className);
    };


    /**
     *@param {string} A valid Thumbrio URL
     */
    var parseUrl = function(url) {
        var re = /(http:\/\/[a-z.\/]+)(\?size=){0,1}([0-9]*x[0-9]*){0,1}((&amp;[a-z=0-9]*)*)/;
        var matches = re.exec(url);
        var dpr = getDpr;
        return {
            url: matches[0],
            thumbrioSize: matches[2],
            thumbrioEffects: matches[3]
        };
    };


    // Compute the thumbrio size
    // @param size {string}: usual size in thumbrio framework
    var thumbrioSize = function(imgInfo) {
        var width = imgInfo.currentWidth;
        var height = Math.round(imgInfo.height * imgInfo.currentWidth / imgInfo.currentHeight, 1);
        return width + 'x' + height;
    };


    /**
     * Build a  valid thumbrio url based on options. It's not signed
     * @param {Object} thImg
     * thImg = Object { url, dpr, size, effects }
     */
     // FIXME: Generalizar
    var thumbrioUrl = function(imgInfo) {
        var dpr = getDpr;
        var effects = (imgInfo.effects || '') + ((dpr != 1) ? '&dpr=' + dpr : '');
        return imgInfo.url + '?size=' + thumbrioSize(imgInfo) + ( effects ? '&' + effects : '');
    };


    // Get info from element image as defined in wordpress
    // The width and height are attributes.
    // Actualy they express the initial dimensions
    var parseWpImg = function(Img) {
        var imgWidth = Img.getAttribute('width');
        var imgHeight = Img.getAttribute('height');
        var imgPath = Img.getAttribute('data-src');
        return {'currentWidth': Img.offsetWidth,
                'currentHeight': Img.offsetHeight,
                'width': imgWidth,
                'height': imgHeight,
                'url': imgPath};
    };


    var thumbriorize = function() {
        var imgList = this.getImages(),
            img;
        for (var i = 0; i < imgList.length; i++) {
            img = imgList[i];
            var info = parseWpImg(img);
            img.setAttribute('src', thumbrioUrl(info));
        }
    };

    setOptions();
    // Public methods
    thumbrio.run = thumbriorize;
    // Remove these functions as public
    thumbrio.getImages = getImages;
    // thumbrio.parse = parseWpImg;

}(window.thumbrio = window.thumbrio || {}));

window.onload = function() {
    window.thumbrio.run();
};
