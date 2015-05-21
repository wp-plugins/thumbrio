window.onload = function () {
    jQuery('#sync-button')[0].onclick = function () {
            messageDiv = jQuery('#th-message')[0];
            messageDiv.classList.add('updated');
            messageDiv.innerHTML = "<p>The synchronization process is running. Please be patient.<p>";
            data = { action:'sync'};
                jQuery.post('/wp-admin/admin-post.php', data, function (response) {
                    if (/error/.exec(response)) {
                        messageDiv.classList.remove('updated');
                        messageDiv.classList.add('error');
                    } else {
                        messageDiv.classList.remove('error');
                        messageDiv.classList.add('updated');
                    } 
                    messageDiv.innerHTML = response;
                });
    };  
}
