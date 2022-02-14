<?php
$twenty_four = time() - 86400;
$thirty = time() - (86400 * 30);

$conn = new PDO('sqlite:/var/www/securitx/data/securitx.db');

$query = "delete from members where verified = 0 and moddate <= $twenty_four";
$conn->exec($query);


$query = "delete from members where verified = 1 and moddate <= $thirty";
$conn->exec($query);
