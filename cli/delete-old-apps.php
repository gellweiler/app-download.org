<?php
require_once(__DIR__ . '/../inc/functions.php');
require_once(__DIR__ . '/../inc/autoload.php');

set_time_limit(0);

define('LANGUAGE', 'en');


$db = DBApps::getInstance()->db;
$res = $db->query('SELECT package FROM Apps;');

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    echo '.';

    // Try to recover from errors.
    for ($i = 0; $i < 3; $i++) {
        try {
            if (!package_exists($row['package'])) {
                $sth = $db->prepare('DELETE FROM Apps WHERE package = :package;');
                $sth->bindValue(':package', $row['package']);
                $sth->execute();
            }

            break;
        } catch (Exception $e) {
            echo 'f';
            sleep(30);
        }
    }
}
