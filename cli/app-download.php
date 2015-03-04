<?php
require_once(__DIR__ . '/../inc/functions.php');
require_once(__DIR__ . '/../inc/autoload.php');

ini_set("default_socket_timeout", 1800); // 30min.
set_time_limit(0);

define('LANGUAGE', 'en');

// Connect to mongo server.
$c = new MongoClient(
    'mongodb://GitHubCrawlerUser:g22LrJvULU5B@ec2-54-88-152-45.compute-1.amazonaws.com:21766/PlayStore',
    array(
        "connectTimeoutMS" => -1,
    )
);
$table = $c->PlayStore->ProcessedApps;

// Get data of interest.
$set = $table->find(
    array('IsFree' => true),
    array(
        'Url' => 1,
        'Name' => 1,
        'Developer' => 1,
        'Description' => 1,
    )
);

// Write data to sqlite db.
while($set->hasNext()) {
    $row = $set->getNext();

    // Extract package name from app.
    if (preg_match('/id=(.*)$/i', $row['Url'], $matches) === 1) {
        $package = $matches[1];

        $sql =
<<<'EOF'
INSERT OR REPLACE INTO Apps(package, title, author, description)
VALUES(:package, :title, :author, :desc);
EOF;
        $db = DBApps::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':package', $package, SQLITE3_TEXT);
        $sth->bindValue(':title', $row['Name'], SQLITE3_TEXT);
        $sth->bindValue(':author', $row['Developer'], SQLITE3_TEXT);
        $sth->bindValue(':desc', $row['Description'], SQLITE3_TEXT);
        $sth->execute();
    }
    else {
        echo "Warning could not extract package from URL:" . $row['Url'] . "\n";
    }
}
