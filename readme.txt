=== Thumbrio ===
Contributors: cuenca@thumbr.io
Tags: thumbnails, thumbr.io, responsive, images, CDN, amazon, S3, bucket
Donate link: http://thumbr.io
Requires at least: 3.5
Tested up to: 4.2.2

Stable tag: 2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Serve the images in your pages through Thumbr.io.

== Description ==
This plugin makes the images in your blog responsive, adapting them to the size and resolution of your users' devices and serves them quickly through a first-class CDN.

You can serve images stored locally in your harddrive (usually in wp-content/uploads) or serve images stored in an Amazon S3 bucket.

To use this plugin you have to sign up in [Thumbr.io](https://thumbr.io). This plugin will sign you up automatically during the setup process if you still don't have an account in [Thumbr.io](https://thumbr.io). We only require an email address and a password.

You can serve up to 1 GB/month with our **Free** plan.

You will be able to edit your images even when they are stored in Amazon S3. Our plugin will only store the original image you uploaded in Amazon S3 and will edit the image on the fly, and adapt it automatically to your user's devices. Saving fewer images on Amazon S3 will help lower your Amazon costs.

Do you need any special features? Please let us know, we are constantly improving listening to our users feedback.

Requires WordPress 3.5 and PHP 5. Tested up to WordPress 4.2.2.


**Plugin Features**

* Streamlined Thumbr.io setup
* Store images locally or remotely in Amazon S3
* Edition of images in external storages
* Access to a dashboard in Thumbr.io with full statistics of your usage

**Coming soon**

* Integrate Thumbr.io's usage charts

== Installation ==
1. Upload the folder **wp-thumbrio-plugin** to the **/wp-content/plugins/** directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Once the plugin is active, you have to set it up in Settings. This will create a new Thumbr.io user account if needed.
4. Select the storage of your image. It could be local, Amazon S3 or any other storage supported by Thumbr.io.

Go to Other Notes to see these steps in further detail [Set-up guide](http://wordpress.org/plugins/thumbrio/other_notes)

== Frequently Asked Questions ==

= How long do I have to wait to use a new domain in Thumbr.io? =

Once you setup a new domain in Thumbr.io we have to wait a few minutes / hours to propagate this change accross the world wide CDN network that Thumbr.io is using. You will receive an email when your new domain is ready.

= What happens to my blog if I disable this plugin? =

We made an effort to allow our users to have responsive images and keep the blog
working if you decide to disable this plugin.

Thumbr.io allows you to store your original images locally in your harddrive,
and in this case if you disable our plugin you will lose the responsiveness
of your images, but they will still be visible to your users.

But we also allow you to store your original images remotely in Amazon S3. In this
case all thumbnails are generated dynamically by Thumbr.io service. If you disable
this plugin the images will still be visible but they will not be responsive. If
you want to unsubscribe from Amazon S3 or from Thumbr.io you have to backup your
images from Amazon S3 and change your blog posts to use your local version of these
images.

= What happens if I go over my free limit in my Thumbr.io account? =

All paid plans in Thumbr.io are unlimited. If you're on a free plan and you store
your images locally and go over your limit images will stop being responsive
until the next month starts.

If you store your images in Amazon S3 and you go over the free limit in Thumbr.io
your images will break until next month. It is strongly recommended that you use
a paid plan in Thumbr.io if you store your images remotely in Amazon S3.

= Which other features does Thumbr.io have? =

We specialize in serving images with friendly URLs through a first world class
CDN. You can also use Thumbr.io outside of WordPress and access all its features
directly. Please visit [Thumbr.io/doc](https://thumbr.io/doc) to see all our features and API to integrate
Thumbr.io in other projects.

== Screenshots ==

1. Link the plugin to a Thumbr.io's account. Sign up (A) to create a Thumbr.io's account or log in (B) to use a previously created account. Follow Settings -> Thumbr.io (C) to customize Thumbr.io settings.

2. Set up the plugin. You have to select the location of your original (high-resolution)
images. You can store them locally (A) or in Amazon S3 (B). If it's the first time you setup the Amazon S3 account that you want to use, you will have to add your credentials in (C).
If you already have an Amazon S3 account in your Thumbr.io preferences, you can use it here automatically selecting Custom origin (D). It will list automatically all the Amazon S3 accounts
linked to your Thumbr.io account.

3. After saving your settings. If you want to have access in WordPress to images
already stored in Amazon S3 click the "synchronization" button (A). In your Thumbr.io
profile you have detailed statistics of your usage, and there you can change / upgrade
your plan.

== Other Notes ==

= Set-up guide =

1. Sign up in Thumbr.io ([Screenshot 1](http://wordpress.org/plugins/thumbrio/screenshots)).

2. Setup the Thumbr.io WordPress plugin: Select where do you want to store your
original (high-resolution) images ([Screenshot 2](http://wordpress.org/plugins/thumbrio/screenshots)). You can store them:
    * _Local storage_. Default and only option in standard WordPress.
    * _Amazon S3_. Recommended, use Amazon S3 to store your images in a virtually
    unlimited harddrive:
        1. Signup in [Amazon S3](https://aws.amazon.com/s3) and create a new bucket.
        2. In the Thumbr.io WordPress settings add your Amazon S3 bucket credentials (see [Screenshot 2](http://wordpress.org/plugins/thumbrio/screenshots)).
        3. The credentials that you use must have permission to: list, upload and delete.
        4. Add a CORS configuration to your bucket (see the CORS Configuration section).
    * _Custom Origin_. Other S3 buckets already configured in Thumbr.io.

3. Synchronization: Give WordPress access to your images that are already stored in
your Amazon S3 bucket (only available when you store your original images in Amazon S3).
It can take several seconds / minutes to synchronize your Amazon S3 collection with
WordPress ([Screenshot 3](http://wordpress.org/plugins/thumbrio/screenshots)). NOTE:
We currently don't provide a way to automatically upload your WordPress existing
collection to Amazon S3.

= CORS Configuration =

If you want to use an existing Amazon S3 bucket with Thumbr.io you should add a CORS
configuration to this bucket.

`
    <?xml version="1.0" encoding="UTF-8"?>
    <CORSConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
        <CORSRule>
            <AllowedOrigin>*</AllowedOrigin>
            <AllowedMethod>GET</AllowedMethod>
            <AllowedMethod>POST</AllowedMethod>
            <AllowedMethod>PUT</AllowedMethod>
            <AllowedMethod>DELETE</AllowedMethod>
            <MaxAgeSeconds>3000</MaxAgeSeconds>
            <AllowedHeader>*</AllowedHeader>
        </CORSRule>
    </CORSConfiguration>
`

= Full reference =

Visit [thumbr.io](https://thumbr.io) to get information about our service and price plans.

== Changelog ==

= 2.2 =
* Integrate Amazon S3 as storage of the images
* Integrate previous Thumbr.io subdomains
* Enable edition of images externally storaged

= 2.1.3 =
* Fix a bug where the computation of image width was wrong.

= 2.1.2 =
* Enable Thumbr.io on blogs served with https when the WordPress Address
  and Site Address contain an http URL.

= 2.1.1 =
* Enable the protocol https

= 2.0.1 =
* Improve Setting Up process

= 2.0 =
* Serve image from your local wordpress static repository
* Easier Setting Up process
* Improve integration with [thumbr.io](https://thumbr.io)

= 1.0 =
* First release
