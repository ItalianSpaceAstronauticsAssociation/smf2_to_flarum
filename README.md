# smf2_to_flarum
Migration script for SMF2 forum to Flarum, partially based on the phpbb_to_flarum script by robrotheram, VIRUXE, Reflic https://github.com/robrotheram/phpbb_to_flarum

##Description
The Script exports and migrates your SMF2 forum to Flarum. It supports:
- a DB to DB migration,
- a DB to ASCII SQL file export.

These two functionalities can be performed together or as alternatives.

Flarum is still in beta testing, therefore only some of the typical web forum features are available.
At this moment smf2_to_flarum only supports migration of:
- Users (no passwords - ask for a new one, no avatars)
- Boards and sub boards (categories are intentionally ignored, boards deeper than 2nd level are added in Flarum as extra Tags)
- Topics and messages (with some bbcode to flarum-markdown translation)

##Known issues / Future features
- Users' avatars are not exported.
- SMF/SMF2 Attachments are not exported. While SMF supports all kind of attachments, Flarum only allows image attachments and provided that flagrow-image-upload extension has been installed (see https://discuss.flarum.org/d/1836-flagrow-image-uploader-for-flarum-forum-messages)
- Private Messages are not exported yet, since this feature is not yet supported by Flarum. 
- Smilies are not "translated".

##Installation
* Install a vanilla Flarum, with empty database.
* Copy smf2_to_flarum.php file in your SMF forum root.
* Customise smf2_to_flarum.php script settings with the correct username/password for your SMF2 and (optionally) Flarum DB, then enable/disable the export settings (all enabled by default).
* Run the script as http://www.yoursmf2website.xyz/smf2_to_flarum.php

The script provides plenty of reporting about the on-going activities.

##Help us
Feel free to contribute to this project!
