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

1. Acceder al servicio de Thumbr.io: Debe crear una cuenta o usar una que ya estuviera creada. Crear una cuenta en Thumbr.io solo requiere de una direccion de email válida y un password ([Screenshot 1](http://wordpress.org/plugins/thumbrio/screenshots)).

2. Configurar el servicio:  Seleccionar el origen de sus imágenes. El plugin le brinda tres posibilidades
    * _Almacenamiento Local_. Esta es la configuración por defecto basta con hacer check en la opción.
    * _Amazon S3 bucket_. En el caso que desee usar un bucket de Amazon S3 como storage de sus imágenes. Debe seguir los siguientes pasos:
        1. En la página de settings introduzca las credentiales de acceso (ver [Screenshot 2](http://wordpress.org/plugins/thumbrio/screenshots)).
        2. El bucket de Amazon S3 debe poseer para la cuenta de acreditada los permisos de listado, upload, delete (Estos últimos si desea hacer uso del plugin para subir y borrar imágenes en el bucket).
        3. Debe establecer una CORS configuration del bucket de Amazon S3 conveniente.
    * _Custom Origin_. Si ya tiene configurados subdominios en Thumbr.io que de sean compatibles con el plugin, estos serán listados. Puedes seleccinar entonces el que desees usar ([Screenshot 3](http://wordpress.org/plugins/thumbrio/screenshots)).
3. Sychronization: En el caso de hacer uso de un origen externo (Amazon o Thumbr.io's subdomain) puedes actualizar la información en la base datos de modos que las imágenes en almacenadas externamentes sean accesibles. Luego de producirse la synchronization las imágenes seran mostradas en la librería multimedia. Esta operación puede tardar varios segundos ([Screenshot 4](http://wordpress.org/plugins/thumbrio/screenshots)).

4. A la configuración de Thumbr.io podrá acceder en cualquier momento por el menú **Settings->Thumbr.io**.

= CORS Configuration =

En la consola de Amazon S3 bucket debe configurar una politíca CORS ([Screenshot 5](http://wordpress.org/plugins/thumbrio/screenshots)). Sírvase del siguiente ejemplo.

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
