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
// (C) Marco Zambianchi - ISAA Technical Team (http://www.isaa.it)
// This file is shared under a Creative Commons "BY" license.
//
// Partially based on script phpbb_to_flarum by robrotheram, VIRUXE, Reflic
// https://github.com/robrotheram/phpbb_to_flarum
//
// License: MIT
///////////////////////////////////////////////////////////////////////////////

set_time_limit(60*60*24); // 1 day
error_reporting(E_ALL);
ini_set('display_errors',1);
$timestamp_start = time();

///////////////////////////////////////////////////////////////////////////////
// GENERAL SETTINGS ***TO BE TAILORED***
///////////////////////////////////////////////////////////////////////////////
<<<<<<< HEAD
// General settings are in smf2_to_flarum_settings.php
///////////////////////////////////////////////////////////////////////////////
include_once("smf2_to_flarum_settings.php");
=======
$servername = "localhost";
$table_prefix = "smf_";

// SMF DB credentials
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
>>>>>>> 8e3daa66ead37b7261119a5ead994c9f22922471

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

function convertURL($text)
{
<<<<<<< HEAD
	return preg_replace('/(https?://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)/is','<URL url="$1">$1</URL>',$text);
}

// This feature requires extension Mediaembed 
// see (https://discuss.flarum.org/d/647-s9e-mediaembed-embed-videos-and-third-party-content)
function convertYoutubeURL($text)
{
	return preg_replace('/http(?:s?):\/\/(?:www\.)?youtu(?:be\.com\/watch\?v=|\.be\/)([\w\-\_]*)(&(amp;)?‌​[\w\?‌​=]*)?/i','<p><YOUTUBE id="$1" url="$0">$0</YOUTUBE></p>',$text);
=======
	return preg_replace('#(https?://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#is','<URL url="$1">$1</URL>',$text);
>>>>>>> 8e3daa66ead37b7261119a5ead994c9f22922471
}

function convertQuoteBBCode($text)
{
	// Setting up things for catching the opening quote bbcode...
	$r_open = " XXXSTARTQUOTEYYY ";
	// We have to support some different options
	$regexp = '/(\[quote]|(\[quote(=|\w|")+\])|(\[quote author(=|\w|")+\])|(\[quote author(=|\w|")+\slink(=|\w|"|#|\.)+\sdate(=|\d)+\]))/i';

	// Replacing the start quote
	$text = preg_replace($regexp,$r_open,$text);
	// Replacing the end quote
	$text = preg_replace('/\[\/quote]/i',"</p></QUOTE><NEWLINE>",$text);

	// splitting up words with spaces (We want to keep newlines)
	$words = preg_split('/ /',$text);

	// We now navigate the words' array and replace the quote start string
	// applying the proper indentation level (&gt;)
	$level = 0;
	for ($idx=0; $idx<count($words); $idx++)
	{
		$w = $words[$idx];
		
		// Have we met an opening quote? Then we deal with it.
		// We make sure to apply the proper level indentation.
		$test = strpos(trim($w),trim($r_open));
		if ($test !== false)
		{
			$level++;
			$replacement = "<QUOTE><i>" . str_repeat('&gt;',$level) . " </i><p>";
			$words[$idx] = preg_replace('/('.trim($r_open).')+/s',$replacement,$w);
		}
		
		// Have we met a closing quote tag? 
		// Then we decrease the indentation level.
		$test = strpos($w,"</QUOTE>");
		if ($test !== false) $level--;
	}

	// Now that the "translation" is done, we merge everything together again...
	$text = implode(" ",$words);
	//$text = "<p>".preg_replace('/<NEWLINE>/i',"\n",$text)."</p>";
	$text = preg_replace('/<NEWLINE>/i',"\n",$text);
	return $text;
}

// Converts BBCODE to Flarum-compatible Markdown internal format
function convertBBCodeToMarkdown($bbcode)
{
	$bbcode = preg_replace('/\[b](.+)\[\/b]/i', "<STRONG><s>**</s>$1<e>**</e></STRONG>", $bbcode);
	$bbcode = preg_replace('/\[i](.+)\[\/i]/i', "<EM><s>*</s>$1<EM><e>*</e></EM>", $bbcode);
	
	
	if (!preg_match('/\[url=(.+?)]\s?\[img/',$bbcode))
	{
		$bbcode = preg_replace('/(\[img]|\[img width(=|\d|")+\])(.+?)\[\/img]/i', '<IMG alt="" src="$3"><s>![</s><e>]($3)</e></IMG>', $bbcode);	
		$bbcode = preg_replace('/\[url=(.+?)](.+?)\[\/url]/i','<URL url="$1">$2</URL>', $bbcode);
		$bbcode = preg_replace('/\[url](.+?)\[\/url]/i','<URL url="$1">$1</URL>', $bbcode); 
	} else {
		$bbcode = preg_replace('/(\[img]|\[img width(=|\d|")+\])(.+?)\[\/img]/i', '<IMG alt="" src="$3"><s>![</s><e>]($3)</e></IMG>', $bbcode);	
	}
	
	$bbcode = preg_replace('/\[center](.+?)\[\/center]/i', '$1', $bbcode);
	$bbcode = preg_replace('/\[color.+?](.+?)\[\/color]/i', '$1', $bbcode);
	$bbcode = preg_replace('/\[size.+?](.+?)\[\/size]/i', '$1', $bbcode);
	
	$bbcode = convertQuoteBBCode($bbcode);
	
	// Delete all non converted bbcode
	$bbcode = preg_replace('|[[\/\!]*?[^\[\]]*?]|si', '', $bbcode);
	
	return $bbcode;
}

// Formats PHPBB's text to Flarum's text format
function formatText($connection,$text)
{
<<<<<<< HEAD
	global $do_youtube_links;
	
	// Do we need rich test wrapTag ("r")?
	// It is needed for [quote], [url]
	$wrapTag = "t";
	if (preg_match('/\[(url|http|quote|img)/i',$text)) $wrapTag = "r";
=======
	// Do we need rich test wrapTag ("r")?
	// It is needed for [quote], [url]
	$wrapTag = "t";
	if (preg_match('/\[(url|quote|img)/i',$text)) $wrapTag = "r";
>>>>>>> 8e3daa66ead37b7261119a5ead994c9f22922471
	 
	// HTML line breaks to \n
	$text = preg_replace('/(\<br\>|\<br\/\>|\<br\s\/\>)/', "\n", $text);
	
	// Replace multiple (one ore more) line breaks with a single one.
	$text = preg_replace('/[\r\n]{2,}/', "\n", $text);
	
	// Force encoding conversion to UTF-8
	$text = html_entity_decode($text,ENT_COMPAT|ENT_HTML401,'UTF-8');
	
	// Convert SLF bbcode in Flarum internal Markdown equivalent tags
	$text = convertBBCodeToMarkdown($text);
	
<<<<<<< HEAD
	// OPTIONAL - Remove Smilies?
	// $text = preg_replace('#\:\w+#', '', $text);
	
	// OPTIONAL - Convert all youtube links to Mediaembed compatible lingo?
	if ($do_youtube_links) $text = convertYoutubeURL($text);
=======
	// Removing Smilies?
	//$text = preg_replace('#\:\w+#', '', $text);
>>>>>>> 8e3daa66ead37b7261119a5ead994c9f22922471
	
	// Wrap text lines with paragraph tags
	$explodedText = preg_split ('/$\R?^/m', $text);
	foreach ($explodedText as $key => $value)
	{
		if(strlen(trim($value)) >= 1)// Only wrap in a paragraph tag if the line has actual text
			$explodedText[$key] = '<p>' . trim($value) . '</p>';
	}
	$text = implode("\n", $explodedText);
	
	// We wrap the text just before returning
	$text = sprintf('<%s>%s</%s>', $wrapTag, $text, $wrapTag);
	
	return $connection->real_escape_string($text);
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
<<<<<<< HEAD
if ($do_youtube_links) { echo "YOUTUBE EMBED ENABLED<br>"; } else { echo "YOUTUBE EMBED ENABLED<br>"; }
if ($do_dump) { echo "IMAGE ATTACHMENTS EXPORT ENABLED<br>"; } else { echo "IMAGE ATTACHMENTS EXPORT DISABLED<br>"; }
=======
>>>>>>> 8e3daa66ead37b7261119a5ead994c9f22922471

///////////////////////////////////////////////////////////////////////////////
// 1) USERS
///////////////////////////////////////////////////////////////////////////////
if ($do_users)
{
	echo "<H2>1) SMF USERS TO FLARUM USERS</H2>";

	$result = $exportDbConnection->query("
		SELECT 
			".$table_prefix."members.*, 
			".$table_prefix."attachments.filename, 
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
			$auxQuery = $importDbConnection->query("TRUNCATE users");
			$auxQuery = $importDbConnection->query("TRUNCATE users_groups");
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
				
				// TBD - AVATAR File EXPORT
				// $avatar = $row['filename'];
				// Avatars are to be collected from SMF attachments table - Save location in flarum ./assets/avatars/7hgxrunbithyo20i.jpg
				
				// We create the users table entries
				$query = "INSERT INTO `users` (`id`, `username`, `email`, `password`, `join_time`, `avatar_path`, `is_activated`) VALUES ('$id', '$username', '$email', '$password', '$jointime', NULL, '1');";
				if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
				if ($do_import)
				{
					$auxQuery = $importDbConnection->query($query);
					if (!$auxQuery) echo $importDbConnection->error . "<br>";
				}
				//echo "$query<br>";
				//echo "<br>";
				
				// We create the users_groups table entries, adding all new users to "members" group
				$query = "INSERT INTO `users_groups` (`user_id`, `group_id`) VALUES ('$id', '3');";
				if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
				if ($do_import)
				{
					$auxQuery = $importDbConnection->query($query);
					if (!$auxQuery) echo $importDbConnection->error . "<br>";
				}
				//echo "$query<br>";
				//echo "<br>";
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
		$auxQuery = $importDbConnection->query("TRUNCATE discussions");
		$auxQuery = $importDbConnection->query("TRUNCATE posts");
		$auxQuery = $importDbConnection->query("TRUNCATE users_discussions");	
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
			//echo "$query<br>";
			//echo "<br>";
		
			// We now connect Tags with Discussions
			$query = "INSERT IGNORE INTO discussions_tags (`discussion_id`,`tag_id`) VALUES ('".$message["id_topic"]."','".$message["id_board"]."');";
			if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
			if ($do_import)
			{
				$auxQuery = $importDbConnection->query($query);
				if (!$auxQuery) echo $importDbConnection->error . "<br>";
			}
			//echo "$query<br>";
			//echo "<br>";
			
			// We update discussion_count in table users
			$query = "UPDATE users SET discussions_count=discussions_count+1 WHERE id='".$message["id_member"]."';";
			if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
			if ($do_import)
			{
				$auxQuery = $importDbConnection->query($query);
				if (!$auxQuery) echo $importDbConnection->error . "<br>";
			}
			//echo "$query<br>";
			//echo "<br>";
		}
		
		// posts table population 
		
		// We try to detect and correct any subject which is not utf-8 encoded
		$encoding = mb_detect_encoding($message["body"],'UTF-8, ASCII, ISO-8859-1', true);
		$body = $message["body"];
		if ($encoding != 'UTF-8') $body = iconv($encoding,'UTF-8',$message["body"]);	
			
		// Single entries in post table now...
		$query = "INSERT INTO `posts` (`id`,`discussion_id`,`number`,`time`,`user_id`,`type`,`content`,`edit_time`,`edit_user_id`,`hide_time`,`hide_user_id`,`ip_address`,`is_approved`
			) VALUES (
			'".$message["id_msg"]."','".$message["id_topic"]."','".$post_counter."','".$message["poster_time"]."','".$message["id_member"]."','comment','".formatText($exportDbConnection,$body)."',DEFAULT,DEFAULT,DEFAULT,DEFAULT,'".$message["poster_ip"]."',1);";
		if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
		if ($do_import)
		{
			$auxQuery = $importDbConnection->query($query);
			if (!$auxQuery) echo $importDbConnection->error . "<br>";
		}
		//echo "$query<br>";
		//echo "<br>";
		
		// We add a record to users_discussions too
		$query = "INSERT IGNORE INTO `users_discussions` (`user_id`,`discussion_id`) VALUES ('".$message["id_member"]."','".$message["id_topic"]."');";
		if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
		if ($do_import)
		{
			$auxQuery = $importDbConnection->query($query);
			if (!$auxQuery) echo $importDbConnection->error . "<br>";
		}
		//echo "$query<br>";
		//echo "<br>";
		
		// We update discussion_count in table users
		$query = "UPDATE users SET comments_count=comments_count+1 WHERE id='".$message["id_member"]."';";
		if ($do_dump) $testW = fwrite($fileHandler,$query.PHP_EOL);
		if ($do_import)
		{
			$auxQuery = $importDbConnection->query($query);
			if (!$auxQuery) echo $importDbConnection->error . "<br>";
		}
		//echo "$query<br>";
		//echo "<br>";
			
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
}
else
	echo "Topic/Messages export error.";

//Clean-up
$result->free_result();
$exportDbConnection->close();
if ($do_import) $importDbConnection->close();

fclose($fileHandler);

?>
</tt>
</body>
</html>
