jQuery(document).ready(function () {
    var currentLength  = jQuery('div.media-item>img').length;
    jQuery('#media-items').on('DOMNodeInserted', function () {
        var computedLength = jQuery('div.media-item>img').length;     
        if  (currentLength < computedLength) {
            var file = jQuery('div.media-item>img:last').attr('src'); 

            console.log(' filename-thumbnail ' +  file);

            jQuery.post('/wp-admin/admin-post.php',
                {
                    action:'upload_image',
                    file:file
                });            
            }
            currentLength = computedLength;
    });
});