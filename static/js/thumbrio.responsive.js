(function(thumbrio) {
    'use strict';

    var options = thumbrio.options || {};

    // Set the thumbrio options....
    // tagLabel -- in case of we would like use div instead of img
    // classTh -- the name class thumbrio work with
    // roundGap -- used for only build images width values are multiples of roundGap
    var defaults = {
        tagLabel: 'img',
        classTh: 'thumbrio-responsive',
        roundGap: 10,
    };

    /**
     * Compute the DRP of current viewport
     */
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


    /**
     * Get DOM elements in Thumbrio's responsive class
     * @param {string} tag It is the tag that to transform
     *     Actualy we work only with img tag. It could be used in future using div instead of img       
     * @param {string} classTh It is the name of the css class to apply thumbrio transformation
     */  
    var getImages = function(tag, classTh) {
        tag = tag || this.options.tagLabel;
        classTh = classTh || this.options.classTh;
        return document.querySelectorAll(tag + '.' + classTh);
    };


    /**
     * Parse a Thumbrio's url to get the size, effect, subdomain, etc
     * @param {string} url A valid thumbrio URL
     * @return {object} {url, thumbrioSize, thumbrioEffects} all atributes are string. 
     *      Valid Thumbrio query arguments. 
     */
    var parseUrl = function(url) {
        var re = /(https{0,1}:\/\/[a-z.\/]+)(\?size=){0,1}([0-9]*x[0-9]*){0,1}((&amp;[a-z=0-9]*)*)/;
        var matches = re.exec(url);
        var dpr = getDpr;
        return {
            url: matches[0],
            thumbrioSize: matches[2],
            thumbrioEffects: matches[3]
        };
    };

    /**
     * Compute the thumbrio size of a image. Return it in form of query arguments
     * @param {object} imgImg = Object {currentWidth, currentHeight, width, height, url}
     *      The attribute url is useless
     * @return {string} A valid Thumbrio's size expression
     */
    var thumbrioSize = function(imgInfo) {
        var width = imgInfo.currentWidth;
        var height = Math.round( width * imgInfo.height / imgInfo.width, 1);
        return width + 'x' + height;
    };


    /**
     * Build a  valid thumbrio url based on options in imgInfo
     * @param {object} imgImg = Object {currentWidth, currentHeight, width, height, url}
     *      The attribute url is useless     
     * @return {string} a valid thumbrio url correspondig to the inputs
     */
     // TODO: Generalizar
    var thumbrioUrl = function(imgInfo) {
        var dpr = getDpr;
        var effects = (imgInfo.effects || '') + ((dpr != 1) ? '&dpr=' + dpr : '');
        return imgInfo.url + '?size=' + thumbrioSize(imgInfo) + ( effects ? '&' + effects : '');
    };

    /** 
     * Get information from element image as defined in wordpress
     * @param {img element} Img It is img element to parse
     * @return {object} It contains all the information
     */
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

    /**
     * Run the Thumbrio's transformation of image's urls
     */
    var thumbriorize = function() {
        var imgList = this.getImages(),
            img;
        for (var i = 0; i < imgList.length; i++) {
            img = imgList[i];
            var info = parseWpImg(img);
            img.setAttribute('src', thumbrioUrl(info));
            img.removeAttribute('data-src');
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
