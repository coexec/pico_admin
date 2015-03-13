Pico Admin Plugin
=================

Adds an administration backend to Pico CMS, containing:
 * A file manager for .md content files inside /content/
 * An online Markdown editor with preview option and some Markdown insertion options
 * An inline image manager with upload, download, preview, and storing of additional meta (title, description per image) options


Install
-------
0. Ensure having installed the pico_placing plugin (https://github.com/ollierik/Pico-Placing)
   (or do not use the "Placing" attribute)

1. Copy the "pico_admin" folder into your Pico installation's "plugins" folder
2. Open the admin_config.php file and insert your SHA1 hashed password
3. Visit http://www.yoursite.com/admin and login
4. Thats it :)


Changelog
---------
See CHANGELOG.md


Author & License
----------------
Pico Admin was written by Kay Stenschke, www.coexec.com

License: see http://opensource.org/licenses/MIT


Third Party Credits
-------------------
* Pico Admin Plugin was initially started by refactoring and extending the Pico Editor plugin by Gilbert Pellegrom.
* The inline Markdown RTE is markItUp! (http://markitup.jaysalvat.com/)
* The plugin uses jQuery (http://jquery.com/), jQuery UI (www.jqueryui.com) and the jQuery plugin introjs (http://usablica.github.io/intro.js/)
* Pico Admin uses icons from the Font Awesome project (http://fortawesome.github.io/Font-Awesome/).