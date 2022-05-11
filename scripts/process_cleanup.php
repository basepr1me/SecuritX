<?php
$twenty_four = time() - 86400;
$five = time() - (86400 * 5);
$thirty = time() - (86400 * 30);
$downloads_path = "/var/www/securitx/data/downloads/";
$uploads_path = "/var/www/securitx/data/uploads/";

$conn = new PDO('sqlite:/var/www/securitx/data/securitx.db');

/* member cleanup */
/* 24 hour */
$query = "delete from members where verified = 0 and is_admin = 0 and " .
    "is_editor = 0 moddate <= $twenty_four";
$conn->exec($query);

/* 30 days */
$query = $conn->prepare("select u_key from members where verified = 1 and " .
    "is_admin = 0 and is_editor = 0 and moddate <= ?");
$query->execute([$thirty]);
$results = $query->fetchAll();
foreach ($results as $result)
	rm_dir($downloads_path . $result['u_key']);

$query = "delete from members where verified = 1 and is_admin = 0 and " .
    "is_editor = 0 and moddate <= $thirty";
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

/* downloads cleanup */
$query = $conn->prepare("select * from downloads where moddate <= ?");
$query->execute([$five]);
$results = $query->fetchAll();
foreach ($results as $result) {
	if ($result['company_id'] != 0) {
		$query_2 = $conn->prepare("select short from companies where " .
		    "company_id = ?");
		$query_2->execute([$result['company_id']]);
		$fd = $query_2->fetch();
		unlink($uploads_path . $fd['short'] . "/" . $result['id_key'] .
		    ".pdf");
	} else {
		unlink($downloads_path . $result['u_key'] . "/" .
		    $result['id_key'] . ".pdf");
	}
}

$query = "delete from downloads where moddate <= $five";
$conn->exec($query);
