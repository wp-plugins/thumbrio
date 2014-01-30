<html>
<head>
    <title>Thumbrio Gallery</title>
    <?php
    require_once (dirname(__FILE__) . '/amazon-s3-thumbrio.php');
    echo "<link rel='stylesheet' type='text/css' media='all' href='" . THUMBRIO_FRONTEND . "/static/css/dropzone.css'>";
    echo "<link rel='stylesheet' type='text/css' media='all' href='" . admin_url('load-styles.php?c=0&dir=ltr&load=admin-bar,buttons,media-views,wp-admin,wp-auth-check&ver=3.7.1') . "'>";
    echo "<link rel='stylesheet' type='text/css' media='all' href='" . admin_url('css/colors-fresh.min.css?ver=3.7.1') . "'>";
    echo "<link rel='stylesheet' type='text/css' media='all' href='" . plugins_url('../static/css/thumbrio.wordpress.css', __FILE__) . "'>";
    echo "<script type='application/javascript' src='" . THUMBRIO_FRONTEND . "/static/js/thirdparty/dropzone.min.js'></script>";
    echo "<script type='application/javascript' src='" . THUMBRIO_FRONTEND . "/js/thumbrio.uploader.js'></script>";
    echo "<script type='application/javascript' src='" . plugins_url('../static/js/hmac_md5.js', __FILE__) . "'></script>";
    echo "<script type='application/javascript' src='" . plugins_url('../static/js/hmac.js', __FILE__) . "'></script>";
    echo "<script type='application/javascript' src='" . plugins_url('../static/js/thumbrio.wordpress.js', __FILE__) . "'></script>";

    function get_current_page() {
        $page = 0;
        if (array_key_exists('page', $_REQUEST)) {
            $page = $_REQUEST['page'];
        }
        return $page;
    }

    $s3 = new Amazon_s3_thumbrio('');
    $s3->set_page(get_current_page());
    ?>
    <style>
        .media-frame-router {
            top: 0px;
            right: 0px;
            left: 0px;
        }
        .thumbrio-media-frame-contents {
            margin-top: 52px;
            margin-left: 16px;
        }
        .thumbrio-gallery {
            overflow-y: hidden;
            width: 942px;
            left: -10px;
            position: relative;
        }
        .thumbrio-submit {
            margin-top: 20px;
        }
    </style>
    <script type="text/javascript">
        window.onload = function () {
            var AMAZON_S3_BUCKET_NAME = "<?php echo $s3->get_user_data('amazon_bucket_name'); ?>";
            var AMAZON_S3_SECRET_KEY = "<?php echo $s3->get_user_data('amazon_secret_key'); ?>";
            var AMAZON_S3_ACCESS_KEY = "<?php echo $s3->get_user_data('amazon_access_key'); ?>";
            var THUMBRIO_API_KEY = "<?php echo $s3->get_user_data('thumbrio_api_key'); ?>";
            var SETTINGS_UPLOADER_DEFAULT = JSON.parse('<?php echo get_option('thumbrio_storage_settings') ?>');
            initializeDropzone(THUMBRIO_API_KEY, AMAZON_S3_SECRET_KEY, AMAZON_S3_BUCKET_NAME, AMAZON_S3_ACCESS_KEY, SETTINGS_UPLOADER_DEFAULT);

            var div = document.getElementsByClassName('thumbrio-media-media-library')[0];
            var buttonElement = div.getElementsByClassName('button')[0];
            var formElement = div.getElementsByTagName('form')[0];
            insertImageIntoDatabase(buttonElement, formElement);
            selectAnImage();
            controlSelectionPanels();

            <?php
            if (!array_key_exists('page', $_REQUEST)) {
                echo "getTab('Upload Files').click();";
            } else {
                echo "getTab('Media Library').click();";
            }
            ?>
        };
    </script>
</head>
<body>
    <div class="media-frame-router">
        <div class="media-router">
            <a href="javascript:;" class="media-menu-item active">Upload Files</a>
            <a href="javascript:;" class="media-menu-item">Media Library</a>
        </div>
    </div>
    <div class="thumbrio-media-frame-contents">
        <div class="thumbrio-media-upload-files">
            <form method="POST" action="admin-post.php">
                <div id="thumbrio-uploader" class="dropzone"></div>
                <input class="thumbrio-submit button media-button button-primary button-large media-button-insert" name="submit" type="submit" value="Upload images" />
                <input type="hidden" name="storage" value="s3" />
                <input type="hidden" name="action" value="Save_images_media" />
            </form>
        </div>
        <div class="thumbrio-media-media-library">
            <form action="admin-post.php" method="POST">
                <?php
                    $s3->print_all_files('thumbrio-gallery', "100x100c", true);
                ?>
                <input type="submit" name="submit2" value="Use images" class="button button-primary"/>
                <input type="hidden" name="storage" value="local" />
                <input type="hidden" name="action" value="Save_images_media" />
            </form>
        </div>
    </div>
</body>
</html>