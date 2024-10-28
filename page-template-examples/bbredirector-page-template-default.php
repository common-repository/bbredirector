<?php
/*
 * Template Name: Page Redirect Default
 *
 * This template uses the bbRedirector plugin and makes it very easy to redirect a page to another location.
 *
 * How it works: 
 * - Copy this file to your theme directory.
 * - Select this template (Page Redirect) as your page template in the interface
 * 
 * This is the most simple use of the bbRedirector. You can set the url and http status code in the bbRedirector's settings.
 * By default it will automatically redirect to your blog's home url with a http status code 302.
 */
if( function_exists('bbrd_redirect') ) { bbrd_redirect(); } ?>
