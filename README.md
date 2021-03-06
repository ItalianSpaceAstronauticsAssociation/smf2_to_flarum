# smf2_to_flarum
Migration script for SMF2 forum to Flarum, partially based on the phpbb_to_flarum script by robrotheram, VIRUXE, Reflic https://github.com/robrotheram/phpbb_to_flarum

## Where to download it
You find the script on GitHub
https://github.com/ItalianSpaceAstronauticsAssociation/smf2_to_flarum

## Description
The Script exports and migrates your SMF2 (possibly SMF1.x) forum to Flarum. It supports:
- a DB to DB migration,
- a DB to ASCII SQL file export.

These two functionalities can be performed together or as alternatives.

Flarum is still in beta testing, therefore only some of the typical web forum features are available, and what is now working can break anytime (including this script, tailored for flarum ver. 0.1.0-beta.5).
At this moment smf2_to_flarum only supports migration of:
- Users and their avatars (no passwords are exported. Migrated users will have to ask for a new one).
- Boards and sub boards (Categories are intentionally ignored, and boards deeper than 2nd level are added in Flarum as extra Tags)
- Topics and messages (with some bbcode to flarum-markdown translation)
- Attachments (partial, only images)

## Changelog
- 2016-08-28: Version 0.3 Alpha
  - Added preliminary image attachments export
  - General bugfix.
- 2016-08-27: Version 0.2 Alpha
  - Added preliminary avatars export
  - Added alternative script version, based on s9e\TextFormatter library (http://s9etextformatter.readthedocs.io).
  - General bugfix.
- 2016-08-26: Version 0.1 Alpha
  - First commit (for flarum ver. 0.1.0-beta.5)

## Known issues / Future features
- Only SMF/SMF2 *image* attachments are exported. While SMF supports all kind of attachments, Flarum only allows image attachments and provided that flagrow-image-upload extension has been installed (see https://discuss.flarum.org/d/1836-flagrow-image-uploader-for-flarum-forum-messages)
- Private Messages are not exported yet, since this feature is not yet supported by Flarum. 
- Smilies are not migrated.

## Installation
* Install a vanilla Flarum, with empty database.
* Copy smf2_to_flarum.php (or smf2_to_flarum_alternative.php) and smf2_to_flarum_settings.php in your SMF forum root.
* Customise smf2_to_flarum_settings.php script settings with the correct username/password for your SMF2 and (optionally) Flarum DB, and enable/disable the export settings (all enabled by default).
* Run the script as http://www.yoursmf2website.xyz/smf2_to_flarum.php

## Alternative version
The alternative version of the script (smf2_to_flarum_alternative.php) needs the following tools to be installed on the same server where SMF2 is installed:
- Composer: https://getcomposer.org/download/
- s9etextFormatter: http://s9etextformatter.readthedocs.io/Getting_started/Installation/ (should be in the script's dir)
Rever to the respective websites for install instructions.

## Avatars
The script saves users' avatars in a directory named "avatars". 
From there the avatars shall be manually copied into flarum "assets/avatars" directory.

## Attachments
Attachment management is not part of flarum core. In order to have attachments functionality in flarum the "Image Upload" extension has to be installed and enabled (see https://discuss.flarum.org/d/1836-flagrow-image-uploader-for-flarum-forum-messages).

The migration script exports only *image* attachments, since no other attachment type is supported at the moment. Files are extracted from single or multiple directory attachment system of SMF/SMF2 then stored in a directory named "images". 

From this directory the attachments shall be manually copied in "assets/images" directory of your flarum installation.

## Help us
Feel free to contribute to this project!
