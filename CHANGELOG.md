Pico Admin Plugin by Kay Stenschke / www.coexec.com
===================================================

Version 2.0.1
-------------
* Bugfix: File paths were not resolved correctly on some servers
* Added meta attributes storage (title, description) and administration in a meta.php file, w/ an associative array, storing meta attributes for every image in folder
* Made assets list scrollable
* Extracted some helper methods into static helper classes

Version 2.0.0
-------------
* Added a guided tour of the administration's user interface
* Added simple translation handling and UI translations in english and german
* Made UI panels resizable
* Added assets manager:
  * browsing of /content/images
  * option to create directories
  * option to upload, download, rename and delete files and directories
  * automatic thumbs generation for images, thumb display on mouseover of files
* Removed unsaved observer / auto-saving
* Replaced EpicEditor by markItUp (faster, easier extensible)
* Added option to rename post files
* Added post info: filename of currently loaded post, with option to rename the file
* Added multi-level ability to tree: display, rename and delete directories, load and save nested posts
* Changed template for new pages: reduced attributes, added "Placing" attribute (requires pico_placing plugin)
* Extended pages listing to include 404 page in admin
* Improved Log-In: added auto-focus password field, added welcome splash w/ reload after successful login, to ensure Pico core (loaded before editor) knowing the log-in status
* Accelerated page load: reduced requests by implementing automatic merging of CSS files and JS files, merged icons into sprite
* Replaced icoMoon icon font by (FontAwesome) icons
* Extracted redundant code from actions into methods
* Integrated jQuery.min.js into assets delivered w/ the plugin (was: loaded via google CDN)
* Moved all global inline JavaScript into an external file, containing only one object in the global scope
* Added this changelog


Pico Editor Version 1.1 by Gilbert Pellegrom / www.dev7studios.com
------------------------------------------------------------------
This is the original plugin which the Pico Admin Plugin was refactored and extended into.