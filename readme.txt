=== Thumbrio ===
Contributors: cuenca@thumbr.io
Tags: thumbnails, thumbr.io, images, effects, instagram
Donate link: http://thumbr.io
Requires at least: 3.5
Tested up to: 4.1
Stable tag: 2.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Serve the images in your pages through Thumbr.io.

== Description ==
This plugin makes the images in your blog responsive, adapting them to the size and resolution of your users' devices. It depends on a service (thumbr.io) to resize dinamically your images.

== Installation ==
1. Upload the folder **wp-thumbrio-plugin** to the **/wp-content/plugins/** directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Once the plugin is active, you have to setup it following the Settings link
    * If you a thumbr.io's account you could use it.
    * If you are not a Thumbr.io's user a new account will be built.

== Screenshots ==

1. Link the plugin to a Thumbr.io's account.

2. Set up the plugin.

3. Select a custom subdomain.

4. Synchronize the external storage.

== Other Notes ==

= Set-up guide =

* Para configurar el uso del servicio de Thumbr.io puede crear una cuenta nueva o usar una que ya estuviera creada. Crear una cuenta en thumbr.io solo requiere de una direccion de email válida y un password (ver [screenshot](http://wordpress.org/plugins/thumbrio/screenshots)).

* Luego de esto debe configurar el servicio esto es, seleccionar el origen de sus imágenes. Si desea usar almacenamiento local (configuración por defecto) basta con hacer check en la opción.
En el que desee usar un bucket de Amazon S3 como storage de sus imágenes. Debe seguir los siguientes pasos:
    1. En la página de settings las credentiales para acceder a la misma ( ver [screenshot](http://wordpress.org/plugins/thumbrio/screenshots)).
    2. El bucket de Amazon S3 debe poseer para la cuenta de acreditada los permisos de listado, upload, delete (Estos últimos si desea hacer uso del plugin para subir y borrar imágenes en el bucket).
    3. En el bucket de Amazon S3 la CORS configuration debe ser algo como

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


* Luego de aceptar la configuración aparece una página donde podrá cambiar en lo sucesivo la configuración del plugin o en el caso de hacer uso de un origen externo (Amazon o thumbr.io subdomain) puede actualizar la información en la base datos de modos que las imágenes en almacenadas externamentes sean accesibles. Luego de producirse la synchronization sus imágenes seran mostradas, en la librería multimedia.

* A la configuración de Thumbr.io podrá acceder en cualquier momento por el menú Settings->Thumbr.io.

= Full reference =

Visit [Thumbr.io](https://thumbr.io) to get information about our service and plans.

== Changelog ==

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
* Improve integration with [thumbr.io](thumbr.io)

= 1.0 =
* First release
