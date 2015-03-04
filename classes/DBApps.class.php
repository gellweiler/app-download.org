<?php
class DBAppsException extends Exception {}

/**
 * A singleton holding the DB object for the list of apps.
 * SQLite is used because it comes with php
 * and there is only minimal DB functionality needed for this project.
 */
class DBApps extends Singleton
{
    /**
     * @var SQLite3
     *  The SQLite3 instance of the apps db.
     */
    public $db;

    /**
     * Open DB connection.
     */
    protected  function __construct()
    {
        // Create an own DB for every language.
        $this->db = new SQLite3(__DIR__ . '/../db/apps_' . LANGUAGE . '.db');

        // Since we use so few tables just check that they exists every time
        // and create them if not.
        $this->createTables();
    }

    /**
     * Create Tables needed for storing information about apps.
     *
     * @throws DBAppsException
     *  If a create table query fails.
     */
    protected function createTables()
    {
        $sqls = array(
<<<'EOF'
-- Table for listing top free apps from google.
CREATE TABLE IF NOT EXISTS topApps
(
    package TEXT, -- The package name of the app.
    rank INT, -- The rank of the app in the top apps list.
    PRIMARY KEY(package)
);
EOF
,
<<<'EOF'
-- Table for listing all apps from google.
CREATE TABLE IF NOT EXISTS Apps
(
    package TEXT, -- The package name of the app.
    title TEXT, -- The title of the app (human readable).
    author TEXT, -- The author of the app.
    description TEXT, -- The description text of the app.
    PRIMARY KEY(package)
);
EOF
        );
        foreach($sqls as $sql) {
            if ($this->db->query($sql) === FALSE) {
                throw new DBAppsException(_('Failed to create tables.'));
            }
        }
    }
}
