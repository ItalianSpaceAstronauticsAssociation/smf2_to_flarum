# smf2_to_flarum
Migration script from SMF version 2 to Flarum, partially based on the phpbb_to_flarum script by robrotheram, VIRUXE, Reflic https://github.com/robrotheram/phpbb_to_flarum

##Description
The Script allows you to migrate your SMF2 forum to Flarum.
It supports, together or as alternatives:
- a DB to DB migration, using php mysqli functions;
- a DB to ASCII SQL file export

At the moment the script supports the migration of:
- Users (no passwords, no alias)
- Boards and sub boards (categories ignored, and boards deeper than 2nd level are added as extra Tags)
- Topics and messages (with bbcode to flarum markup translation)

##Known issues / Future features
- Users' avatars are not exported at the moment.
- SMF/SMF2 Attachments are, at the moment, not exported. While SMF supports all kind of attachments, Flarum only allows image attachments after having installed flagrow-image-upload extension (https://discuss.flarum.org/d/1836-flagrow-image-uploader-for-flarum-forum-messages)
- Private Messages are not exported yet, since this feature is not yet supported by Flarum. 

##Installation
* Install a vanilla Flarum, with empty database
* Copy smf2_to_flarum.php script in your SMF forum root
* Customise smf2_to_flarum.php script settings with the correct DB username/password and enabling/disabling the export settings if needed.
* Run the script as http://www.yoursmf2website.xyz/smf2_to_flarum.php
* The script provides plenty of reporting about the on-going activities

##Help us
Feel free to contribute to this project!
