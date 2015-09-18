<?php

require_once 'DrSlump/Protobuf.php';
\DrSlump\Protobuf::autoload();

require_once(__DIR__ . '/../inc/functions.php');
require_once(__DIR__ . '/../classes/GoogleAccount.class.php');
require_once(__DIR__ . '/../classes/Checkin.class.php');

$google_account = new GoogleAccount('USER_EMAIL', 'USER_PASSWORD');

$checkin = new Checkin($google_account);
$checkin->changeDevice('nexus-5');
echo 'AID: ' . $checkin->checkin() . "\n";
