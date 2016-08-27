<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
</head>
<body>
<tt>

<?php
///////////////////////////////////////////////////////////////////////////////
// SMF2 to FLARUM migration script
//
// s9e\TextFormatter version
//
// This version performes a migration from SMF2 to Flarum using the 
// TextFormatter parser from s9e\TextFormatter version by PHP
//
// (C) Marco Zambianchi - ISAA Technical Team (http://www.isaa.it)
// This file is shared under a Creative Commons "BY" license.
//
// Partially based on script phpbb_to_flarum by robrotheram, VIRUXE, Reflic
// https://github.com/robrotheram/phpbb_to_flarum
//
// License: MIT
///////////////////////////////////////////////////////////////////////////////

// Get the autoloader (unless you have already loaded Composer's)
include __DIR__ . '/vendor/autoload.php';

use s9e\TextFormatter\Bundles\Forum as TextFormatter;

set_time_limit(60*60*24); // 1 day
error_reporting(E_ALL);
ini_set('display_errors',1);
$timestamp_start = time();

///////////////////////////////////////////////////////////////////////////////
// GENERAL SETTINGS ***TO BE TAILORED***
///////////////////////////////////////////////////////////////////////////////
// General settings are in smf2_to_flarum_settings.php
///////////////////////////////////////////////////////////////////////////////
include_once("smf2_to_flarum_settings.php");

///////////////////////////////////////////////////////////////////////////////
// AUXILIARY FUNCTIONS
///////////////////////////////////////////////////////////////////////////////
function slugify($text)
{
	$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
	$text = trim($text, '-');
	$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	$text = strtolower($text);
	$text = preg_replace('~[^-\w]+~', '', $text);

	if (empty($text))
		return 'n-a';

	return $text;
}

// Generates a random color for Tags
function rand_color()
{
	return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

// Used to convert Categories to Tags
function stripBBCode($text_to_search) {
	$pattern = '|[[\/\!]*?[^\[\]]*?]|si';
	$replace = '';
	return preg_replace($pattern, $replace, $text_to_search);
}

// We convert messages bodies into Flarum-compatible XML format
function newFormatText($connection,$text)
{
	
	// We get rid of [html] bbcode
	$text = preg_replace('/(\[html]|\[\/html])/si', '', $text);
	
	// HTML line breaks to \n
	$text = preg_replace('/(<br\s?\/?>)+/is', "\n", $text);
	
	// Replace multiple (one ore more) line breaks with a single one.
	$text = preg_replace('/[\r\n]{2,}/s', "\n", $text);
	
	// Handle some special case here...
	$text = preg_replace('/(&amp;#039;|&#039;)+/',"'", $text);
	
	// Force encoding conversion to UTF-8
	$text = html_entity_decode($text,ENT_COMPAT|ENT_HTML401,'UTF-8');
	
	// 
	$text = TextFormatter::parse($text);
	
	// Delete all non converted bbcode
	$text = preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $text);
	
	return $connection->real_escape_string($text);
}

// Older attachments may still use this function.
function getLegacyAttachmentFilename($filename, $attachment_id)
{
	$clean_name = $filename;

	// Remove international characters (windows-1252)
	// These lines should never be needed again. Still, behave.
	$clean_name = strtr($filename,"\x8a\x8e\x9a\x9e\x9f\xc0\xc1\xc2\xc3\xc4\xc5\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd1\xd2\xd3\xd4\xd5\xd6\xd8\xd9\xda\xdb\xdc\xdd\xe0\xe1\xe2\xe3\xe4\xe5\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xff",'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy');
	$clean_name = strtr($clean_name, array("\xde" => 'TH', "\xfe" =>'th', "\xd0" => 'DH', "\xf0" => 'dh', "\xdf" => 'ss', "\x8c" => 'OE',"\x9c" => 'oe', "\xc6" => 'AE', "\xe6" => 'ae', "\xb5" => 'u'));

	// Sorry, no spaces, dots, or anything else but letters allowed.
	$clean_name = preg_replace(array('/\s/', '/[^\w_\.\-]/'), array('_', ''), $clean_name);

	$enc_name = $attachment_id . '_' . strtr($clean_name, '.', '_') . md5($clean_name);
	$clean_name = preg_replace('~\.[\.]+~', '.', $clean_name);

	return $enc_name;
}

function generateRandomString($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

///////////////////////////////////////////////////////////////////////////////
// DATABASE CONNECTIONS
///////////////////////////////////////////////////////////////////////////////
echo "<h2>DATABASE CONNECTIONS</h2>";

// Establish a connection to the server where the PHPBB database exists
$exportDbConnection = new mysqli($servername, $usrSMF, $pwdSMF, $dbSMF);
printf("Initial character set in EXPORT server: %s<br>", $exportDbConnection->character_set_name());
if (!$exportDbConnection->set_charset("utf8")) {
	printf("Error loading character set utf8: %s<br>", $exportDbConnection->error);
} else {
	printf("Current character set in EXPORT server: %s<br>", $exportDbConnection->character_set_name());
}

if ($do_import)
{
	$importDbConnection = new mysqli($servername, $usrFlarum, $pwdFlarum, $dbFlarum);
	printf("Initial character set in IMPORT server: %s<br>", $exportDbConnection->character_set_name());
	if (!$importDbConnection->set_charset("utf8")) {
		printf("Error loading character set utf8: %s<br>", $importDbConnection->error);
	} else {
		printf("Current character set in IMPORT server: %s<br>", $importDbConnection->character_set_name());
	}
}

// SQL Dump file
if ($do_dump)
{
	$fileDump = "SMF_dump.sql";
	$fileHandler = fopen($fileDump,"w");
}

///////////////////////////////////////////////////////////////////////////////
// 0) SHOW SETTINGS
///////////////////////////////////////////////////////////////////////////////
// Export settings
echo "<h2>0) EXPORT/IMPORT SETTINGS</h2>";
if ($do_users) { echo "USERS export ENABLED<br>"; } else { echo "USERS export DISABLED<br>"; }
if ($do_tags) { echo "BOARDS export ENABLED<br>"; } else { echo "BOARDS export DISABLED<br>"; }
if ($do_posts) { echo "MESSAGES export ENABLED<br>"; } else { echo "MESSAGES export DISABLED<br>"; }
if ($do_import) { echo "DIRECT IMPORT in flarum ENABLED<br>"; } else { echo "DIRECT IMPORT in flarum DISABLED<br>"; }
if ($do_dump) { echo "SQL FILE DUMP ENABLED<br>"; } else { echo "SQL FILE DUMP DISABLED<br>"; }
if ($do_youtube_links) { echo "YOUTUBE EMBED ENABLED<br>"; } else { echo "YOUTUBE EMBED ENABLED<br>"; }
if ($do_dump) { echo "IMAGE ATTACHMENTS EXPORT ENABLED<br>"; } else { echo "IMAGE ATTACHMENTS EXPORT DISABLED<br>"; }

///////////////////////////////////////////////////////////////////////////////
// 1) USERS
///////////////////////////////////////////////////////////////////////////////
if ($do_users)
{
	echo "<H2>1) SMF USERS TO FLARUM USERS</H2>";

	$result = $exportDbConnection->query("
		SELECT 
			".$table_prefix."members.*, 
			".$table_prefix."attachments.id_attach,
			".$table_prefix."attachments.filename, 
			".$table_prefix."attachments.fileext, 
			".$table_prefix."attachments.file_hash, 
			".$table_prefix."attachments.id_folder
		FROM ".$table_prefix."members 
		LEFT JOIN ".$table_prefix."attachments 
		ON ".$table_prefix."members.id_member=".$table_prefix."attachments.id_member 
		WHERE 
			".$table_prefix."attachments.attachment_type = 0"
		);
	if (!$result) echo $exportDbConnection->error;
	$totalUsers = $result->num_rows;

	if ($result)
	{
		if ($do_import)
		{
			$auxQuery = $importDbConnection->query("TRUNCATE users;");
			$auxQuery = $importDbConnection->query("TRUNCATE users_groups;");
		}
		
		if ($do_dump)
		{
			$testW = fwrite($fileHandler,"TRUNCATE users;".PHP_EOL);
			$testW = fwrite($fileHandler,"TRUNCATE users_groups;".PHP_EOL);
		}
		
				
		echo "Found $totalUsers users to export<br>";
		$i = 0;
		$usersIgnored = 0;
		while($row = $result->fetch_assoc())
		{
			$i++;
			
			// no email address, we skip
			if(trim($row["email_address"]))
			{
				$username = $row['member_name'];
				$id = $row['id_member'];
				$email = $row['email_address'];
				$password = sha1(md5(time())); //old password is deleted and changed with a random one
				$jointime = date("Y-m-d H:i:s",$row['date_registered']);
				
				echo sprintf("User %06d - %s\n<br>",$id,$username);
				
				// Avatars are collected from SMF attachments table - Save location in flarum ./assets/avatars/7hgxrunbithyo20i.jpg
				if (trim($row['file_hash']) == "")
				{
					$filename = getLegacyAttachmentFilename($row['filename'],$row['id_attach']);
				} else {
					$filename = $row['id_attach'] . "_" . $row['file_hash'];
				}
				$idx_src_dir = $row['id_folder'];
				$src_dir = $attachments_dir[$idx_src_dir];
				$src = $src_dir . '/' . $filename;
				$dst = __DIR__.'/avatars/' . generateRandomString() . '.' . $row['fileext'];
				
				// We make sure we have a unique filename
				while (file_exists($dst)) $dst = __DIR__.'/avatars/' . generateRandomString() . '.' . $row['fileext'];
				
				// If avatar not found or copy fails, we set avatar to NULL
				$val = @copy($src,$dst);
				//echo "Copy of $src to $dst ";
				//if ($val) echo '<span style="color:green;">OK</span>';
				$avatar = "'".basename($dst)."'";
				//if (!$val) echo '<span style="color:red;">KO</span><br>';
				if (!$val) $avatar = "NULL";
				
				// We create the users table entries
				$query = "INSERT INTO `users` (`id`, `username`, `email`, `password`, `join_time`, `avatar_path`, `is_activated`) VALUES ('$id', '$username', '$email', '$password', '$jointime', " . $avatar . ", '1');";
				if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
				if ($do_import)
				{
					$auxQuery = $importDbConnection->query($query);
					if (!$auxQuery) echo $importDbConnection->error . "<br>";
				}
				
				// We create the users_groups table entries, adding all new users to "members" group
				$query = "INSERT INTO `users_groups` (`user_id`, `group_id`) VALUES ('$id', '3');";
				if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
				if ($do_import)
				{
					$auxQuery = $importDbConnection->query($query);
					if (!$auxQuery) echo $importDbConnection->error . "<br>";
				}
			}
			else {
				$usersIgnored++;
			}
		}
		echo ($i-$usersIgnored) . ' of '. $totalUsers .' users converted';
	}
	else
		echo "Users export error.";
	$result->free_result();
	echo "<hr>";
}


///////////////////////////////////////////////////////////////////////////////
// 2) SMF BOARDS AND SUB-BOARDS TO FLARUM TAGS
///////////////////////////////////////////////////////////////////////////////
if ($do_tags)
{
	echo "<h2>2) SMF BOARDS AND SUB-BOARDS TO FLARUM TAGS...</h2>";
	$result = $exportDbConnection->query("SELECT * FROM ".$table_prefix."boards ORDER BY id_parent, child_level;");
	if (!$result) echo $exportDbConnection->error;
	$totalBoards = $result->num_rows;
	if ($totalBoards)
	{
		if ($do_import)
		{
			$auxQuery = $importDbConnection->query("TRUNCATE tags");
		}
		
		if ($do_dump)
		{
			$testW = fwrite($fileHandler,"TRUNCATE tags;".PHP_EOL);
		}

		$i = 1;
		while($row = $result->fetch_assoc())
		{
			$id = $row["id_board"];
			$name = html_entity_decode(addslashes($row["name"]));
			$description = html_entity_decode(addslashes($row["description"]));
			$color = rand_color();
			$slug = slugify($row["name"]);
			
			// We port only the first 2 layers of sub-boards, any deeper level is imported as secondary Tag
			if ($row["child_level"] <= 1) 
			{
				$parent_id = ($row["id_parent"] < 1) ? "NULL" : "'".$row["id_parent"]."'";
				$position = "'$i'";
			} else {
				$parent_id = "NULL";
				$position = "NULL";
			}
			
			echo sprintf("Tag %04d - %s (%s)\n<br>",$i,$name,$slug);
			
			// Let's put some records in this Tags table!
			$query = "INSERT IGNORE INTO tags (id, name, description, parent_id, slug, color, position) VALUES ( '$id', '$name', '$description', $parent_id, '$slug', '$color', $position);";
			if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
			if ($do_import)
			{
				$auxQuery = $importDbConnection->query($query);
				if (!$auxQuery) echo $importDbConnection->error . "<br>";
			}

			$i++;
		}
		echo $totalBoards . ' boards and sub-boards converted.';
	}
	else
		echo "Boards/Sub-boards export error.";
	$result->close();
	echo "<hr>";
}

///////////////////////////////////////////////////////////////////////////////
// 3) SMF TOPICS AND MESSAGES TO FLARUM DISCUSSIONS AND POSTS
///////////////////////////////////////////////////////////////////////////////

/*
*** Topic/Messages to Discussions/Posts mapping ***
FLARUM							SMF
discussion.id					topics.id_topic
discussion.title				messages.subject
discussion.comments_count		topics.num_replies
discussion.participants_count	TBC
discussion.number_index			BLANK
discussion.start_time			messages.poster_time (from Timestamp to YYYY-MM-DD HH:II:SS)
discussion.start_user_id		messages.id_member
discussion.start_post_id		messages.id_msg (first topic only, of course)
discussion.last_time			TBC
discussion.last_user_id			topics.id_member_updated
discussion.last_post_id			topics.id_last_msg
discussion.last_post_number		messages.num_replies
discussion.hide_time			NULL
discussion.hide_user_id			NULL
discussion.is_locked			topics.locked
discussion.is_sticky			topics.is_sticky
*/
if ($do_posts)
{
	echo "<h2>3) SMF TOPICS AND MESSAGES TO FLARUM DISCUSSIONS AND POSTS</h2>";
	$result = $exportDbConnection->query("SELECT 
		topics.id_topic,
		topics.id_board,
		messages.id_msg,
		messages.subject,
		topics.num_replies,
		from_unixtime(messages.poster_time) as poster_time,
		messages.id_member,
		messages.id_msg,
		topics.id_member_updated,
		messages.body,
		topics.id_last_msg,
		topics.locked,
		topics.is_sticky,
		messages.poster_ip
	FROM ".$table_prefix."topics as topics INNER JOIN ".$table_prefix."messages as messages
	ON topics.id_topic = messages.id_topic
	ORDER BY topics.id_topic DESC, messages.poster_time $limit_topics;");
	if (!$result) echo $exportDbConnection->error;
	$totalPosts = $result->num_rows;

	if ($result)
	{
		echo "Found $totalPosts messages to convert<br>";
		
		// We empty the tables, for a clean import
		if ($do_import)
		{
			$auxQuery = $importDbConnection->query("TRUNCATE discussions;");
			$auxQuery = $importDbConnection->query("TRUNCATE posts;");
			$auxQuery = $importDbConnection->query("TRUNCATE users_discussions;");	
		}
		
		if ($do_dump)
		{
			$testW = fwrite($fileHandler,"TRUNCATE discussions;".PHP_EOL);
			$testW = fwrite($fileHandler,"TRUNCATE posts;".PHP_EOL);
			$testW = fwrite($fileHandler,"TRUNCATE users_discussions;".PHP_EOL);
		}
		
		$converted = 0;
		$prev_id = -1;

		while($message = $result->fetch_assoc())
		{
			// DISCUSSIONS TABLE
			// We have a new topic, we create an entry in discussions, then we continue with entries in posts
			if ($prev_id != $message["id_topic"])
			{
				// We try to detect and correct any subject which is not utf-8 encoded
				$encoding = mb_detect_encoding($message["subject"],'UTF-8, ASCII, ISO-8859-1', true);
				$subject = $exportDbConnection->real_escape_string(html_entity_decode($message["subject"]));
				if ($encoding != 'UTF-8')  $subject = $exportDbConnection->real_escape_string(html_entity_decode(iconv($encoding,'UTF-8',$message["subject"])));	
				
				//echo "New discussion, ID ".$message["id_topic"]." (".$message["subject"]." ($encoding) => $subject)"."<br>";
				echo sprintf("Discussion ID %07d %s [Posts: %05d]<br>",$message["id_topic"],$subject,($message["num_replies"]+1));
				
				$post_counter = 1;
				$prev_id = $message["id_topic"];
				$comments_counter = $message["num_replies"]+1;
				
				// Partecipants Count
				$auxQuery = $exportDbConnection->query("SELECT DISTINCT id_member FROM ".$table_prefix."messages WHERE id_topic='".$message["id_topic"]."';");
				$participants_count = $auxQuery->num_rows;
				$auxQuery->free_result();

				// Last post date and time
				$auxQuery = $exportDbConnection->query("SELECT from_unixtime(MAX(poster_time)) as last_time FROM ".$table_prefix."messages WHERE id_topic='".$message["id_topic"]."';");
				$auxRes = $auxQuery->fetch_assoc();
				$last_time = $auxRes["last_time"];
				$auxQuery->free_result();
				
				// NOTE: SMF was setting user_id to 0 for deleted/banned users. A NEW LOGIC SHALL BE DEFINED HERE (Define a "fake" user with user_id like -1?
				$query = "INSERT INTO `discussions` (`id`,`title`,`comments_count`,`participants_count`,`number_index`,`start_time`,`start_user_id`,`start_post_id`,`last_time`,`last_user_id`,
					`last_post_id`,`last_post_number`,`hide_time`,`hide_user_id`,`slug`,`is_approved`,`is_locked`,`is_sticky`
					) VALUES (
					'".$message["id_topic"]."','".$subject."','".$comments_counter."','".$participants_count."','".$message["num_replies"]."','".$message["poster_time"]."','".$message["id_member"]."','".$message["id_msg"]."','".$last_time."','".$message["id_member_updated"]."','".$message["id_last_msg"]."',DEFAULT,DEFAULT,DEFAULT,'".slugify($message["subject"])."','1','".$message["locked"]."','".$message["is_sticky"]."');";
				if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
				if ($do_import)
				{
					$auxQuery = $importDbConnection->query($query);
					if (!$auxQuery) echo $importDbConnection->error . "<br>";
				}

				// We now connect Tags with Discussions
				$query = "INSERT IGNORE INTO discussions_tags (`discussion_id`,`tag_id`) VALUES ('".$message["id_topic"]."','".$message["id_board"]."');";
				if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
				if ($do_import)
				{
					$auxQuery = $importDbConnection->query($query);
					if (!$auxQuery) echo $importDbConnection->error . "<br>";
				}

				// We update discussion_count in table users
				$query = "UPDATE users SET discussions_count=discussions_count+1 WHERE id='".$message["id_member"]."';";
				if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
				if ($do_import)
				{
					$auxQuery = $importDbConnection->query($query);
					if (!$auxQuery) echo $importDbConnection->error . "<br>";
				}
			}
			
			// posts table population 
			
			// We try to detect and correct any subject which is not utf-8 encoded
			$encoding = mb_detect_encoding($message["body"],'UTF-8, ASCII, ISO-8859-1', true);
			$body = $message["body"];
			if ($encoding != 'UTF-8') $body = iconv($encoding,'UTF-8',$message["body"]);	
				
			// Single entries in post table now...
			$query = "INSERT INTO `posts` (`id`,`discussion_id`,`number`,`time`,`user_id`,`type`,`content`,`edit_time`,`edit_user_id`,`hide_time`,`hide_user_id`,`ip_address`,`is_approved`
				) VALUES (
				'".$message["id_msg"]."','".$message["id_topic"]."','".$post_counter."','".$message["poster_time"]."','".$message["id_member"]."','comment','".newFormatText($exportDbConnection,$body)."',DEFAULT,DEFAULT,DEFAULT,DEFAULT,'".$message["poster_ip"]."',1);";
			if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
			if ($do_import)
			{
				$auxQuery = $importDbConnection->query($query);
				if (!$auxQuery) echo $importDbConnection->error . "<br>";
			}

			// We add a record to users_discussions too
			$query = "INSERT IGNORE INTO `users_discussions` (`user_id`,`discussion_id`) VALUES ('".$message["id_member"]."','".$message["id_topic"]."');";
			if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
			if ($do_import)
			{
				$auxQuery = $importDbConnection->query($query);
				if (!$auxQuery) echo $importDbConnection->error . "<br>";
			}

			// We update discussion_count in table users
			$query = "UPDATE users SET comments_count=comments_count+1 WHERE id='".$message["id_member"]."';";
			if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
			if ($do_import)
			{
				$auxQuery = $importDbConnection->query($query);
				if (!$auxQuery) echo $importDbConnection->error . "<br>";
			}
			
			$post_counter++;
			$converted++;
			
			// Every 100 converted posts, we sleep a few seconds 5 seconds
			if (($converted % 100) == 0)
			{
				echo "Converted $converted of $totalPosts messages<br>";
				echo "Elapsed time: ".(time() - $timestamp_start)." sec.<br>";
				flush(); 
				ob_flush();
				sleep($server_interval);
			}
		}
		$result->free_result();
	}
	else
		echo "Topic/Messages export error.";
}

//Clean-up

$exportDbConnection->close();
if ($do_import) $importDbConnection->close();

fclose($fileHandler);

?>
</tt>
</body>
</html>
