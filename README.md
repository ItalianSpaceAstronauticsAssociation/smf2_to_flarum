# smf2_to_flarum
Migration Script from SMF version 2 to Flarum, partially based on the phpbb_to_flarum script by robrotheram, VIRUXE, Reflic
// https://github.com/robrotheram/phpbb_to_flarum

The Script performs, according to your settings:
- a DB to DB migration
- a DB to SQL ascii file export

At the moment the script copies:
- Users (no passwords, no alias)
- Boards and sub boards (categories ignored, and boards deeper than 2nd level are added as extra Tags)
- Topics and messages (with bbcode to flarum markup translation)

TBD
- Aliases
- Attachments (only images supported by flarum, via extension)
- Private Messages (not yet supported by flarum)

##Install
* Install a vanilla flarum
* Copy the script in your SMF forum root
* Customise the script settings (the section is clearly marked in the source code) for your SMF and (optional) flarum DB
* Run the script as http://www.yoursmfwebsite.xyz/smf2_to_flarum.php

##Help us
Feel free to contribute to this project!
