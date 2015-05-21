<?php

class Thumbrio {
    const URL_WP_SIGNUP          = 'https://www.thumbr.io/wordpress/signup';
    const URL_WP_LOGIN           = 'https://www.thumbr.io/wordpress/login';
    const URL_WP_PROFILE         = 'https://www.thumbr.io/profile';
    const URL_WP_ACTIVATE_DOMAIN = 'https://www.thumbr.io/wordpress/domain/activate';
    const URL_WP_CREATE_DOMAIN   = 'https://www.thumbr.io/wordpress/domain/create';
    const URL_WP_SELECT_DOMAIN   = 'https://www.thumbr.io/wordpress/domain/select';
    const URL_WP_SHOW_DOMAIN     = 'https://www.thumbr.io/wordpress/domain/show_all';

    function __construct() {
        $this->refresh_values();
        $this->local_webdir = thumbrio_get_local_webdir();
        $this->service = null;
        $this->query_array();
        $this->main_url = get_option('siteurl') . '/wp-admin/options-general.php?page=thumbrio';
    }

    function refresh_values() {
        $this->username  = get_option(OPTION_EMAIL);
        $this->subdomain = get_option(OPTION_SUBDOMAIN);
        $this->webdir    = get_option(OPTION_WEBDIR);
        $this->amazon    = array(
             'private_key' => get_option(OPTION_AMAZON_PRKEY),
             'public_key'  => get_option(OPTION_AMAZON_PUKEY),
             'region'      => get_option(OPTION_AMAZON_REGION)
             );
        $this->thumbrio  = array(
             'private_key' => get_option(OPTION_PRIVATE_KEY),
             'public_key'  => get_option(OPTION_PUBLIC_KEY)
             );
    }

    function get_real_webdir() {
        return ($this->webdir ? $this->webdir : $this->local_webdir);
    }

    function webdir_is_local() {
        $webdir = $this->get_real_webdir();
        return ($this->local_webdir == $webdir);
    }

    /*
     * *******************************
     * Parse a query string to array
     * *******************************
     */
    function query_array() {
        $valid_arguments = array('subdomain', 'email', 'webdir', 'signup', 'signin', 'error', 'amazon', 'thumbrio', 'change');
        $query_string = $_SERVER['QUERY_STRING'];

        preg_match_all('/[?&]([^=?&]+=[^=?&]+)/', urldecode($query_string), $matches);
        foreach ($matches[1] as $match) {
            preg_match('/([^=?&]+)=([^=?&]+)/', $match, $key_value);
            $temp[$key_value[1]] = $key_value[2];
        }
        foreach ($valid_arguments as $key) {
            $query_array[$key] = (isset($temp[$key])? urldecode($temp[$key]) : null);
        }
        return $query_array;
    }

    function save_values($values) {
        $valid_options = array (
            OPTION_EMAIL,
            OPTION_SUBDOMAIN,
            OPTION_WEBDIR,
            OPTION_AMAZON_PRKEY,
            OPTION_AMAZON_PUKEY,
            OPTION_AMAZON_REGION,
            OPTION_PRIVATE_KEY,
            OPTION_PUBLIC_KEY);
        foreach ($valid_options as $key) {
            if ( isset($values[$key]) ) {
                update_option($key, $values[$key]);
            }
        }
        $this->refresh_values();
    }

    function get_current_service() {
        $query_array = $this->query_array();

        $this->service = 'initial';
        if (isset($query_array['signup'])) {
            $this->service = 'signup';
        } elseif (isset($query_array['signin'])) {
            $this->service = 'login';
        } elseif (isset($query_array['change'])) {
            $this->service = 'change';
        } elseif (get_option(OPTION_SUBDOMAIN)) {
            $this->service = 'main';
        } elseif (get_option(OPTION_PRIVATE_KEY)) {
            $this->service = 'setup';
        }
    }

    /* ***********************************
     * PAGES
     *
     *************************************
     */
    function show_panel() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
            <div class="wrap">
        <?php
        $this->error();

        $this->get_current_service();

        switch ($this->service) {
            case 'initial':
                $this->page_signup_or_signin();
                break;
            case 'login':
                $this->page_signup_and_login(false);
                break;
            case 'main':
                $this->page_main();
                break;
            case 'setup':
                $this->page_get_origin(true);
                break;
            case 'change':
                $this->page_get_origin(false);
                break;
            case 'signup':
            default:
                $this->page_signup_and_login(true);
                break;
        }
        ?>
            </div>
        <?php
    }

    private function page_signup_or_signin () {
        $signup_url = $this->main_url.'&signup=1';
        $signin_url = $this->main_url.'&signin=1';
        ?>
        <div class="th-container">
            <h2>Set up Thumbr.io plugin</h2>
            <p>This plugin makes the images in your blog responsive, adapting them to the
                size and resolution of your users' devices. It depends on a service
                (<a href="http://wwww.thumbr.io">Thumbr.io</a>) to resize dinamically
                your images.
            </p>
            <div id="th-button-container" class="th-button-container">
                <p>
                   <a class='button button-primary' href="<?php echo $signup_url; ?>">Sign up</a>
                   or
                   <a href="<?php echo $signin_url; ?>">Log in</a> Thumbr.io to enable this plugin.
                </p>
            </div>
        </div>
        <?php
    }

    private function page_signup_and_login ($is_signup) {
        $cancel_url = $this->main_url;
        ?>
        <div class="th-container">
            <div id='th-configuration'>
                <h2>Sign <?php echo ($is_signup? 'Up' : 'In'); ?> Thumbr.io </h2>
                <div class="font-small" style="display:<?php echo($is_signup ? 'initial' : 'none'); ?>">
                    <p> You're signing up for a free account in <a href="https://www.thumbr.io">Thumbr.io</a>. You can serve up
                        to 1 GB / month. </p>
                </div>
                <div class="font-small" style="display:<?php echo($is_signup ? 'none' : 'initial'); ?>">
                    <p> Please introduce your credentials to access to your Thumbr.io account </p>
                </div>
                <div id="th-admin-loging">
                    <form id="signin-form" method="post" action="admin-post.php">
                        <?php settings_fields('thumbrio-group');?>
                        <input type="hidden" name="action" value="sign" />
                        <input type="hidden" name="type"   value="<?php echo($is_signup ? 'signup' : 'signin'); ?>" />
                        <input type="hidden" name="main_url" value="<?php echo $this->main_url; ?>">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="adminemail">Email</label></th>
                                    <td>
                                        <input name="adminemail" type="text"
                                            id="adminemail"
                                            class="regular-text"
                                            required="required"
                                            pattern="[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?"
                                            value="<?php echo (get_option(OPTION_EMAIL))? '' : get_option('admin_email'); ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="adminpass">Password</label></th>
                                    <td>
                                        <input name="adminpass"
                                            id="adminpass"
                                            type="password"
                                            required="required"
                                            class="regular-text"
                                            value=""
                                        />
                                    </td>
                                </tr>
                                <tr style="display:<?= ($is_signup) ? 'table row' : 'none' ?>">
                                    <th scope="row"><label for="adminpass-repeated" >Password</label></th>
                                    <td>
                                        <input name="adminpass-repeated"
                                            id="adminpass-repeated"
                                            type="password"
                                            class="regular-text"
                                            <?= ($is_signup) ? 'required="required"':'' ?>
                                            value=""
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="th-button-container">
                            <div class="th-row-form">
                                    <div class="th-button-container">
                                    <?php submit_button (($is_signup ? 'Create' : 'Log in'), 'primary th-button',
                                        'submit-user', false, array('id' => 'th-submit-button')); ?>
                                    </div>
                                    <div>&nbsp;or&nbsp;</div>
                                    <div class="th-button-container">
                                        <a href="<?php echo $cancel_url; ?>">Cancel</a>
                                    </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    // TODO: Erase TEST button
    private function page_main () {
        $refresh_url = $this->main_url;
        ?>
        <div class="th-container">
            <h2>Welcome <?= htmlentities(get_option(OPTION_EMAIL)); ?> to Thumbr.io</h2>
            <div id='th-message'></div>
            <p>
            Your images from
            <strong>
                <?= htmlentities($this->get_real_webdir()); ?>
            </strong>
            are served by <a href="http://www.thumbr.io">Thumbr.io</a> through
            <strong>
                <?= htmlentities($this->subdomain) ?>.
            </strong>
            </p>
        <?php
            if (!$this->webdir_is_local()) {
                ?>
                    <script type='text/javascript' src ='<?=THUMBRIO_SYNCHRO_JS?>'></script>
                    <p>
                        If you have previously uploaded images push the
                        <a id='sync-button' class='button button-primary'>synchronization</a>
                        button to update the media library. Your images will not be downloaded, just the information in the
                        database is updated. Afterwards you could use your images as usual.
                        Please, be patient this procedure could take a few minutes.
                    </p>
                <?php
            }
        ?>
            <p></p>
            <div id='amazon-option'>
                <a class='button button-primary' href='<?="$this->main_url&change=1"?>'>Change your settings</a>
                <p class="th-remark">Use this button to get access to your Thumbr.io configuration.</p>
            </div>
            <p></p>
            <div id='amazon-option'>
                Follow this link to get access to your
                <a class='no-button no-button-primary' href='<?=Thumbrio::URL_WP_PROFILE?>'>Thumbr.io's profile</a>.
                There you could see details about the use of the service, and change to a paid plan if needed.
            </div>

        </div>
        <?php

        if (!$this->webdir_is_local()) {
            ?>
                <script type='text/javascript' src ='<?=THUMBRIO_SYNCHRO_JS?>'></script>
            <?php
        }
    }

    /**
     * Page: Get Storage Origin
     *
     * Initial Or Changing Settings
     */
    private function page_get_origin($first_time) {
        $response_data  = $this->get_thumbrio_subdomains();
        $list_domains = $response_data['list_domains'];
        ?>
        <script type='text/javascript' src ='<?=THUMBRIO_ORIGIN_JS?>'></script>
        <div class="th-container">
            <?php
                if ($first_time) {
                    ?>
                    <h2>Welcome <?= htmlentities(get_option(OPTION_EMAIL))?> to Thumbr.io</h2>
                    <div>
                        <p>Thumbr.io can serve the images stored in your local folder, in an Amazon S3 bucket
                        or from any other origin configured in your Thumbr.io profile.</p>
                    </div>
                <?php
                } else {
                    ?>
                    <h2>Manage your Thumbr.io plugin's settings</h2>
                    <div>
                        <p class='th-inter'><span class='th-label-text'>Thumbr.io account </span>
                           <strong> <?= htmlentities(get_option(OPTION_EMAIL)) ?></strong></p>
                        <p class='th-inter'><span class='th-label-text'>Image storage folder </span>
                           <strong> <?= htmlentities($this->get_real_webdir()) ?></strong></p>
                        <p class='th-inter'><span class='th-label-text'>Thumbr.io subdomain </span>
                           <strong> <?= htmlentities($this->subdomain) ?></strong></p>
                        <p></p>
                        <div >
                        <p></p>
                        </div>
                    </div>
                    <?php
                }
            ?>
            <p>Please, select an option to <?php echo ($first_time? 'complete':'change'); ?> the plugin's configuration.</p>
            <form id="form" method="post" action="admin-post.php">
                <?php settings_fields('thumbrio-group');?>
                <input type="hidden" name="action" value="get_origin"/>
                <ul class="th-options">
                    <li>
                        <span class="th-h2"><label><input type="radio" name="thumbrio" value="local"
                        <?php echo ($first_time ? 'checked':'');?>
                        onclick="clickLocal();">Local storage</label></span>

                        <p>This is the default option. Select it to store your images in your local hard drive.</p>
                    </li>

                    <li>
                        <span class="th-h2"><label><input type="radio" name="thumbrio" value="amazon" onclick="clickAmazon();"> Amazon S3</label></span >

                        <p>Store your images in Amazon S3. You will never run again out of disk, and you will
                        not risk losing your data due to a hardware problem, but it will be a bit more expensive.
                        We will help you upload all your local images to Amazon.</p>

                        <div id="th-amazon-options" class="th-amazon-options">
                            <div>
                                <label>Amazon Bucket</label>
                                <input type="text" name="amazon-bucket" placeholder="Bucket where you want to store your images">
                            </div>
                            <div>
                                <label>Amazon Content Path</label>
                                <input type="text" name="amazon-path" placeholder="Prefix path common to all images">
                            </div>
                            <div>
                                <label>Amazon Access Key</label>
                                <input type="text" name="<?=htmlentities(OPTION_AMAZON_PUKEY)?>" placeholder="Your access key provided by Amazon">
                            </div>
                            <div>
                                <label>Amazon Secret Key</label>
                                <input type="text" name="<?=htmlentities(OPTION_AMAZON_PRKEY)?>" placeholder="Your secret key provided by Amazon">
                            </div>
                        </div>
                    </li>

                    <li>
                        <span class="th-h2"><label><input type="radio" name="thumbrio"
                        <?php echo ($first_time ? '':'checked');?>
                        value="custom" onclick="clickCustom();"> Custom origin</label></span >

                        <p>Advanced. Select a custom origin configured manually in your Thumbr.io profile
                        (http://thumbr.io/profile). Only use this option if you know what you're doing.</p>

                        <div id="th-custom-origin">
                    <?php
                        $used = ($first_time)? TRUE : FALSE;
                        $webdir = $this->get_real_webdir();
                        foreach ($list_domains as $subdomain) {
                            if ($subdomain['active'] == '1') {
                                $radio_value = $subdomain['domain'].':::'.$subdomain['webdir'];
                                ?>
                                <div class="th-custom-origin-row">
                                    <label>
                                        <input type='radio' name='thumbrio-custom-origin' value='<?=htmlentities($radio_value)?>'
                                        <?php
                                            if (!$used) {
                                                $temp_webdir = $subdomain['webdir'];
                                                $used = ($temp_webdir == $webdir);
                                                echo ($used)? 'checked' : '';
                                            }
                                        ?>
                                        >
                                        <div style="display: inline-block">
                                            <?=htmlentities($subdomain['webdir'])?>
                                            <div class="th-sub-comment">Served from <strong><?=htmlentities($subdomain['domain'])?></strong></div>
                                        </div>
                                    </label>
                                </div>
                                <?php
                            }
                        }
                    ?>
                        </div>
                    </li>

                </ul>
                <button type="submit" form="form" value="Submit" class="button-primary">Save your settings</button>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php
                    if (!$first_time) {
                        echo '<a href='. htmlentities($this->main_url). '>Cancel</a>';
                    }
                ?>
            </form>

        </div>
        <?php
    }

    static function make_a_post($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = curl_exec($ch);
        $error = curl_error($ch);

        if ($error) {
            return array('OK' => false, 'message' => null, 'error' => "$error");
        }
        $response = curl_getinfo($ch);
        curl_close ($ch);

        if ($response['http_code'] == 200) {
            return array('OK' => true,  'message' => $content, 'error' => $error);
        } else {
            return array('OK' => false, 'message' => $response['message'], 'error' => $content);
        }
    }

    private function error() {
        $query_array = $this->query_array();
        $error = (isset($query_array['error']) ? $query_array['error'] : null);
        if ($error) {
            switch ($error) {
                case 'amazon-match':
                    $message = 'The Amazon S3 credentials are not correct.';
                    break;
                case 'The user does exist.':         // decrepated: compatibility
                    $message = 'The user does exist.';
                    break;
                case 'user-exists':
                    $message = "The email belongs to a thumbr.io's user. Please change the email or cancel to log in.";
                    break;
                default:
                    $message = "$error";
                    break;
            }
            echo '<div class="error"><p>' .  htmlentities($message) . '</p></div>';
        }
    }

    private function define_domain_to_local_folder () {
        $response_data  = $this->get_thumbrio_subdomains();
        $list_domains = $response_data['list_domains'];
        $next_url = $this->main_url;
        foreach ($list_domains as $subdomain) {
            if ($subdomain['webdir'] == $this->local_webdir) {
                $this->pick_subdomain($subdomain['domain'], $subdomain['webdir']);
                wp_redirect($next_url, 302);
                exit;
            }
        }
        $data = array(
                api_key    => get_option(OPTION_PUBLIC_KEY),
                secret_key => get_option(OPTION_PRIVATE_KEY),
                webdir     => $this->local_webdir,
            );
        $response = $this->make_a_post(Thumbrio::URL_WP_CREATE_DOMAIN, $data);
        if ($response['OK']) {
            $this->save_values(array(OPTION_SUBDOMAIN => $response['message']));
        } else {
            $error = ($response['error'] ? $response['error'] : 'There is an unknown error');
            $next_url .= "$query_arg&error=" . urlencode($error);
        }
        wp_redirect($next_url, 302);
        exit;
    }

    private function define_domain_to_amazon_bucket ($amazon_info) {
        $next_url = $this->main_url;
        $data = array(
                'api_key'            => get_option(OPTION_PUBLIC_KEY),
                'secret_key'         => get_option(OPTION_PRIVATE_KEY),
                'webdir'             => $amazon_info['webdir'],
                'amazon_private_key' => $amazon_info['prkey'],
                'amazon_public_key'  => $amazon_info['pukey'],
                'amazon_region'      => $amazon_info['region']
            );
        $response = $this->make_a_post(Thumbrio::URL_WP_CREATE_DOMAIN, $data);
        if ($response['OK']) {
            $this->save_values(array(
                OPTION_SUBDOMAIN     => $response['message'],
                OPTION_AMAZON_PUKEY  => $amazon_info['pukey'],
                OPTION_AMAZON_PRKEY  => $amazon_info['prkey'],
                OPTION_WEBDIR        => $amazon_info['webdir'],
                OPTION_AMAZON_REGION => $amazon_info['region']
                ));
        } else {
            $error = ($response['error'] ? $response['error'] : 'There is an unknown error');
            error_log('Amazon error :     ' . $response['error']);
            $next_url .= "$query_arg&error=" . urlencode($error);
        }
        wp_redirect($next_url, 302);
        exit;
    }

    private function check_amazon_configuration_and_get_region ($amazon) {
        try {
            $bucket = Bucket::populate_construct(
                $amazon['bucket'],
                'us-east-1',
                $amazon['pukey'],
                $amazon['prkey'],
                $amazon['path']);
            //Devuelve array( OK, region)
            $response = $bucket->check_amazon_credentials_and_get_region();
            error_log('Response from check amazon ' . json_encode($response));
        } catch (BucketException $e) {
            error_log('Response from check amazon exception ' . urlencode($e->getMessage()));
            return array('OK' => false, 'region'=> '', 'error' => urlencode($e->getMessage()));
        }
        return array('OK' => true, 'region' => $response['region'], 'error' => '');
    }

    //TODO: Verify the redirect in case of error
    private function get_thumbrio_subdomains () {
        $next_url = $this->main_url;
        $data = array(
                'api_key'    => get_option(OPTION_PUBLIC_KEY),
                'secret_key' => get_option(OPTION_PRIVATE_KEY),
                'webdir'     => $this->local_webdir,
            );
        $response = $this->make_a_post(Thumbrio::URL_WP_SHOW_DOMAIN, $data);
        if ($response['OK']) {
            return json_decode($response['message'], true);
        } else {
            $error = ($response['error'] ? $response['error'] : 'There is an unknown error');
            $next_url .= "$query_arg&error=" . urlencode($error);
            wp_redirect($next_url, 302);
            exit;
        }
    }

    /*
     * ***********************
     * Manage form from signup
     * ***********************
     */
    function thumbrio_post_form_sign() {
        $next_url = $this->main_url;

        $signup = $_REQUEST['type'] == 'signup';
        $query_arg = ($signup ? '&signup=1' : '&signin=1');

        $fields = array (
            QA_ADMIN_EMAIL    => 'email',
            QA_ADMIN_PASSWORD => 'password',
        );

        $error = null;
        foreach ($fields as $key => $name) {
            $error_field = (isset($_REQUEST[$key]) ? null : $name );
        }
        $error = ($error_field ? 'The '. $error_field . ' field is required' : null);

        if (!$error) {
            if ($signup and
               ($_REQUEST[QA_ADMIN_PASSWORD . '-repeated'] != $_REQUEST[QA_ADMIN_PASSWORD])) {
                $error = 'The passwords do not match';
            }
        }

        if ($error) {
            $next_url .= "$query_arg&error=" . urlencode ($error);
        } else {
            $data = array(
                'username' => $_REQUEST[QA_ADMIN_EMAIL],
                'password' => $_REQUEST[QA_ADMIN_PASSWORD],
            );
            $url_post = ($signup ? Thumbrio::URL_WP_SIGNUP : Thumbrio::URL_WP_LOGIN );
            $response = $this->make_a_post($url_post, $data);
            if ($response['OK']) {
                $responseData = $response['message'];
                $responseData = json_decode($responseData, true);
                $this->save_values(
                    array (
                        OPTION_EMAIL       => $data['username'],
                        OPTION_PRIVATE_KEY => $responseData['secret_key'],
                        OPTION_PUBLIC_KEY  => $responseData['api_key']
                    ));
            } else {
                $error = ($response['error'] ? $response['error'] : 'There is an unknown error');
                $next_url .= "$query_arg&error=" . urlencode($error);
            }
        }
        wp_redirect($next_url, 302);
        exit;
    }

    function thumbrio_post_set_origin () {
        $option = $_REQUEST['thumbrio'];
        switch ($option) {
            case 'amazon':
                $this->thumbrio_post_form_amazon();
                break;
            case 'custom':
                $this->thumbrio_set_custom();
                break;
            case 'local':
            default:
                $this->define_domain_to_local_folder();
                break;
        }
    }

    function thumbrio_post_form_amazon() {
        require_once (dirname(__FILE__) . '/../admin/thumbrio-plus.php');

        $amazon_info = array(
            'path'   => $_REQUEST['amazon-path'],
            'pukey'  => $_REQUEST[OPTION_AMAZON_PUKEY],
            'prkey'  => $_REQUEST[OPTION_AMAZON_PRKEY],
            'bucket' => $_REQUEST['amazon-bucket'],
        );
        $config = $this->check_amazon_configuration_and_get_region($amazon_info);

        if ($config['OK']) {
            $webdir = 'https://' . $amazon_info['bucket'] .'.s3.amazonaws.com/' . $amazon_info['path'];
            $amazon_info['webdir'] = $webdir;
            $amazon_info['region'] = $config['region'];
            $this->define_domain_to_amazon_bucket($amazon_info);
        } else {
            wp_redirect($this->main_url . '&error=' . $config['error'], 302);
            exit;
        }
    }

    private function pick_subdomain($domain, $webdir) {
        $data = array(
            'api_key'    => get_option(OPTION_PUBLIC_KEY),
            'secret_key' => get_option(OPTION_PRIVATE_KEY),
            'domain'     => $domain,
        );
        $response = $this->make_a_post(Thumbrio::URL_WP_SELECT_DOMAIN, $data);
        if ($response['OK']) {
            $message = $response['message'];
            $amazon_info = json_decode($response['message'], true);

            $this->save_values(array(
                OPTION_WEBDIR        => $webdir,
                OPTION_SUBDOMAIN     => $domain,
                ));

            if (isset($amazon_info['public_key'])) {
                $this->save_values(array(
                    OPTION_AMAZON_PUKEY  => $amazon_info['public_key'],
                    OPTION_AMAZON_PRKEY  => $amazon_info['private_key'],
                    OPTION_AMAZON_REGION => (isset($amazon_info['region'])? $amazon_info['region'] : 'us-east-1'),
                    ));
            } else {
                delete_option(OPTION_AMAZON_PRKEY);
                delete_option(OPTION_AMAZON_PUKEY);
                delete_option(OPTION_AMAZON_REGION);
            }
        } else {
            $error = ($response['error'] ? $response['error'] : 'There is an unknown error');
            $next_url = $this->main_url . "$query_arg&error=" . urlencode($error);
            wp_redirect($next_url, 302);
            exit;
        }
    }

    function thumbrio_set_custom() {
        $dom_web = explode(':::', $_REQUEST['thumbrio-custom-origin']);
        $domain = $dom_web[0];
        $webdir = $dom_web[1];
        $this->pick_subdomain($domain, $webdir);
        if (!thumbrio_is_webdir_local())
            require_once (dirname(__FILE__) . '/../admin/thumbrio-plus.php');
        wp_redirect($this->main_url, 302);
        exit;
    }

    //FIXME//////////////// TEMPORAL PARA PRUEBAS BORRAR /////////////////
    function thumbrio_clean_config() {
        delete_option(OPTION_SUBDOMAIN);
        delete_option(OPTION_WEBDIR);
        delete_option(OPTION_AMAZON_PRKEY);
        delete_option(OPTION_AMAZON_PUKEY);
        delete_option(OPTION_AMAZON_REGION);
        wp_redirect($this->main_url, 302);
        exit;
    }
}
?>
