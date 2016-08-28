<?php

///////////////////////////////////////////////////////////////////////////////
// GENERAL SETTINGS ***TO BE TAILORED***
///////////////////////////////////////////////////////////////////////////////
$servername = "localhost";	// Server name for both SMF and Flarum DB.
$flarum_website_url = "http://www.yourflarumwebsite.xyz";	// Change this fake URL with your flarum website...

// SMF DB credentials
$table_prefix = "smf_";	// Table prefix is at the moment only valid for SMF DB.
$usrSMF = "";
$pwdSMF = "";
$dbSMF  = "";

// Flarum DB credentials
$usrFlarum = "";
$pwdFlarum = "";
$dbFlarum  = "";

// We introduce a sleeping period every 100 posts to limit the load on the server
$server_interval = 10;
// For testing only. Here is possible to set a LIMIT SQL statement for a partial export 
$limit_topics = "";

// Export settings
$do_users = true;	// Export users?
$do_tags = true;	// Export boards and sub-boards?
$do_posts = true;	// Export posts?

// Import settings
$do_import = true;	// Try a direct import in flarum db?
$do_dump = true;	// Dump the SQL code to a file?

// Exports requiring non-bundled Extensions
$do_attachments_avatars = true;	// Export users' avatars?
$do_attachments_images = false;	// Requires flarum-image-upload ext.
$do_youtube_links = false; 		// Requires Mediaembed ext.


// Attachment folder(s)
$attachments_dir = array(
	"0" => "/your_full_path_to_smf2/attachments"
);


?>
