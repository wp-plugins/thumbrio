( function () {

    function removeDoubleProtocol ( src ) {
        return src.replace(/https?:\/\/.*\/(https?:\/\/.*)/, '$1');
    }

    function updateImagesSrc () {
        var innerImages = jQuery('img');
        for ( var i = 0; i < innerImages.length; i++ ) {
            var img    = innerImages[i];
            var newSrc = removeDoubleProtocol( img.src );
            if ( newSrc !== img.src ) {
                img.src = newSrc;
            }
        }
    }

    jQuery( document ).ajaxComplete( function ( event, xhr, settings ) {
        if ( 'responseText' in xhr ) {
            responseText = xhr.responseText;
            var wpSign = responseText.substring(responseText.length-23);
            if (wpSign === 'WP_CUSTOMIZER_SIGNATURE') {
                updateImagesSrc ();
                }
        }
    });

    jQuery( document ).ready( updateImagesSrc );
})();
