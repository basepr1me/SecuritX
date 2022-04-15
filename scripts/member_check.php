<?php
$twenty_four = time() - 86400;
$thirty = time() - (86400 * 30);
$path = "/var/www/securitx/data/downloads/";

$conn = new PDO('sqlite:/var/www/securitx/data/securitx.db');

/* 24 hour */
$query = "delete from members where verified = 0 and is_admin = 0 and is_editor = 0 moddate <= $twenty_four";
$conn->exec($query);

/* 30 days */
$query = $conn->prepare("select u_key from members where verified = 1 and is_admin = 0 and is_editor = 0 and moddate <= ?");
$query->execute([$thirty]);
$results = $query->fetchAll();
foreach ($results as $result)
	rm_dir($path . $result['u_key']);

$query = "delete from members where verified = 1 and is_admin = 0 and is_editor = 0 and moddate <= $thirty";
$conn->exec($query);

function rm_dir($dir) {
	foreach(scandir($dir) as $file) {
		if ('.' === $file || '..' === $file)
			continue;
		if (is_dir("$dir/$file"))
		  	rm_dir("$dir/$file");
		else
			unlink("$dir/$file");
	}
	rmdir($dir);
}
