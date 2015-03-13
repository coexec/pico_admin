<?php
/**
 * translation.en.php   English (default) translations for Pico Admin Plugin
 *
 * To add a label:
 * 1. add it here
 * 2. define it in the inline javaScript inside admin.html
 *
 * @note    Labels are parsed in the order as listed, therefor ensure having labels which add something to another,
 *          otherwise identical, label to be listed before that other label.
 *          Ex: 'label.something.more' must be listed before 'label.something' for both labels to be correctly translated.
 * @note    There's no fallback handling for missing labels, if a translation file is found for a language, it is used
 *          for translating ALL labels, without caring about its completeness.
 */
    $translations = array(
        'assets.delete'             => 'Delete',
        'assets.download'           => 'Download',
        'assets.mkdir'              => 'Create new directory',
        'assets.meta'               => 'Edit title and description',
        'assets.rename'             => 'Rename',
        'assets.root'               => 'Go back to root directory',
        'current-file.rename'       => 'Click to rename the file',
        'description'               => 'Description',
        'directory.create.prompt'   => 'Create directory:',
        'directory.delete.confirm'  => 'Are you sure you want to delete the directory: "<fileFolderName>" and all contained files?',
        'directory.rename.prompt'   => 'Rename directory to:',
        'done'                      => 'Done',
        'file.delete.confirm'       => 'Are you sure you want to delete the file: "<fileFolderName>"',
        'file.rename.prompt'        => 'Rename file to:',
        'guided-tour'               => 'Displays a guided tour of the user interface of this administration backend',
        'intro.assets'              => 'From here, image files can be uploaded and organized:<br />previewed, renamed, deleted. Additionally image directories can be created and removed.<br />Inserting an image into the currently loaded post is done by clicking on an image\'s filename.',
        'intro.controls'            => 'These options create, load and save posts (pages), here in the editor and in the actual website.<br />The section also includes options for this info and to log-out from the administration backend.<br /><br />Hint: All Buttons in this interface display an information bubble when the mousepointer stays above them without motion.',
        'intro.editor'              => 'Over here, posts are edited. Using the above buttons, styles can be applied to the text and elements like images, tables, hyperlinks, etc. can be inserted into the text.<br />Posts begin with a comment which contains meta-attributes, like the post title and a sorting number ("Placing"). When any of these is changed the tree should be reloaded (by pressing CTRL+R or the refresh button in the top-left control options)',
        'intro.navigation'          => 'From this tree a post can be loaded (by clicking the title) for editing, posts can also be previewed in the website or be deleted (using the options which are displayed when the mousepointer hovers a post item). The tree also shows the sorting and hierarchy of pages in the navigation.',
        'login.password'            => 'Password',
        'login.welcome'             => 'Welcome to Pico CMS administration!',
        'logout'                    => 'Logout from the Pico administration backend',
        'meta.description.prompt'   => 'Description:',
        'meta.title.prompt'         => 'Title:',
        'next'                      => 'Next',
        'post.delete.confirm'       => 'Are you sure you want to delete the post: "<postName>" (filename: "<filename>")?',
        'post.delete'               => 'Delete this post',
        'post.new.prompt'           => 'Enter a post title.\\nYou can create sub folders by entering a path before the title.',
        'post.new'                  => 'Create new post',
        'post.refresh-tree'         => 'Refresh the posts tree',
        'post.rename'               => 'Rename the file of this post',
        'post.save'                 => 'Save current post',
        'post.view.current'         => 'View current post in the actual website',
        'post.view'                 => 'View this post in the actual website',
        'prev'                      => 'Back',
        'preview.inline'            => 'Preview in the administration backend',
        'rte.bold'                  => 'Bold',
        'rte.comment'               => 'Comment (not shown in website)',
        'rte.h1'                    => 'First Level Headline',
        'rte.h2'                    => 'Second Level Headline',
        'rte.h3'                    => 'Third Level Headline',
        'rte.h4'                    => 'Fourth Level Headline',
        'rte.h5'                    => 'Fifth Level Headline',
        'rte.h6'                    => 'Sixth Level Headline',
        'rte.italic'                => 'Italic',
        'rte.link'                  => 'Hyperlink',
        'rte.list.bullets'          => 'List with bullet points',
        'rte.list.numbered'         => 'Numbered list',
        'rte.picture'               => 'Picture',
        'rte.quotation'             => 'Quotation',
        'rte.strikethrough'         => 'Strikethrough',
        'rte.table'                 => 'Table',
        'saved'                     => 'Saved',
        'saving'                    => 'Saving',
        'skip'                      => 'Skip',
        'title'                     => 'Title',
        'warning.post.none-loaded'  => 'No post loaded'
    );