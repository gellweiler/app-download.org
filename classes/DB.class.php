<?php
class DBException extends Exception {}

/**
 * A singleton holding the DB object. SQLite is used because it comes with php
 * and there is only minimal DB functionality needed for this project.
 */
class DB extends Singleton
{
    /**
     * @var SQLite3
     *  The SQLite3 instance of the main db.
     */
    public $db;

    /**
     * Open DB connection.
     */
    protected  function __construct()
    {
        $this->db = new SQLite3(__DIR__ . '/../db/main.db');

        // Since we use so few tables just check that they exists every time
        // and create them if not.
        $this->createTables();
    }

    /**
     * Create Tables needed for this application.
     *
     * @throws DBException
     *  If a create table query fails.
     */
    protected function createTables()
    {
        $sqls = array(
<<<'EOF'
-- Table defining a user.
-- All we store is a hash of the email, so that
-- we can identify the user when he is logging in.
-- Password validation is taken care by google.
CREATE TABLE IF NOT EXISTS users
(
    id INTEGER PRIMARY KEY ASC,
    emailhash TEXT
)
EOF
        ,
<<<'EOF'
-- Table listing all virtual devices that users registrate.
-- Each device belongs to a user and has a human readable name
-- The device id is given by google and is needed
-- to authentificate a device with the playstore.
CREATE TABLE IF NOT EXISTS devices
(
    owner INTEGER,
    name TEXT,
    deviceid TEXT, -- Hex string.

    FOREIGN KEY (owner) REFERENCES user(id)
);
EOF
        ,
<<<'EOF'
-- Table holding records about downloads that get requested.
-- When a user has successfully requested a download the download information
-- will be written into this table and attached to an unique and secure token.
-- The Browser then can start the actual download using this token.
CREATE TABLE IF NOT EXISTS downloads
(
    token TEXT PRIMARY KEY,
    url TEXT, -- The url from where to download.
    marketda TEXT, -- MarketDA Cookie needet for download.
    package TEXT, -- The package the user wants to download.
    expires INTEGER  -- Unix Timestamp when the download information will expire.
);
EOF
        );
        foreach($sqls as $sql) {
            if ($this->db->query($sql) === FALSE) {
                throw new DBException(_('Failed to create tables.'));
            }
        }
    }
}
