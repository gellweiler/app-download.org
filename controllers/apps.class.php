<?php
class apps extends Controller
{
    /**
     * Modifies the result row, before processing. Used for generating a short teaser text for each app.
     *
     * @param Array $row
     *  The row to modify.
     */
    protected  function _alterRow(&$row) {
        if (!empty($row)) {
            // Generate a short teaser from the play store description.
            $t = mb_substr(strip_tags($row['description']), 0, 200);
            $row['teaser'] = mb_substr($t, 0, mb_strrpos($t, ' '));
        }
    }

    /**
     * Show detail information about an app
     * and offer download of it.
     *
     * @param string $package
     *  The package name of the app to show information for.
     *
     * @throws Exception
     *  If package does not exists.
     */
    public function app($package = NULL)
    {
        $sql =
<<<EOF
SELECT package, title, author, description
FROM Apps
WHERE package LIKE :package
;
EOF;

        // Get info from DB and show app details.
        $db = DBApps::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':package', trim($package), SQLITE3_TEXT);
        $res = $sth->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $this->_alterRow($row);


        if (!empty($row)) {
            $this->view->package = $package = $row['package'];
            $this->view->title = $row['title'];
            $this->view->author = $row['author'];

            $play_url = 'https://play.google.com/store/apps/details?id=' . urlencode($package);

            $this->view->teaser = $row['teaser']
            . _f(
                ' %s[more]%s<br><br><i>This is a short extract from %s<br>%sCopyright notice%s</i>',
                '<a target="_blank" href="' . q($play_url) . '">',
                '</a>',
                '<a target="_blank" href="' . q($play_url) . '">' . q($play_url) . '</a>',
                '<a href="' . q(url('impressum')) . '#app-teaser">[',
                ']</a>'
            );
        }
        else if (package_exists($package)) {
            $this->view->package = $package;
            $this->view->title = $package;
            $this->view->author = _('Unknown');
            $this->view->teaser = '<i>' . _f(
                'App-Download.org has no description for this app, however you can still download the app using the %sdownload link%s.'
                . ' Please visit the %sPlay Store%s for information about this app.',
                '<a href="' . q(url('download/direct/' . urlencode($package))) . '">',
                '</a>',
                '<a target="_blank" href="https://play.google.com/store/apps/details?id=' . q(urlencode($package)) . '">',
                '</a>') . '</i>';
        }
        else {
            throw new Exception(_f('Could not find app with package name "%s".', $package));
        }

        // Build links.
        $this->view->downloadUrl =
            url('download/direct/') . urlencode($package);
        $this->view->playstoreUrl =
            'https://play.google.com/store/apps/details?id='
            . urlencode($package);

        $this->view->display('app');
    }

    /**
     * Show a list of all top apps.
     */
    public function render()
    {
        // Get Result set for apps.
        $sql =
<<<'EOF'
SELECT
    package,
    title,
    author,
    description,
    :base_detail_url || package as detailsUrl,
    :base_download_url || package as downloadUrl,
    :base_play_url || package as playstoreUrl
    FROM topApps
    JOIN Apps USING(package)
    ORDER BY rank;
EOF;
        $db = DBApps::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':base_detail_url', url('apps/app/'), SQLITE3_TEXT);
        $sth->bindValue(':base_download_url', url('download/direct/'), SQLITE3_TEXT);
        $sth->bindValue(
            ':base_play_url',
            'https://play.google.com/store/apps/details?id=',
            SQLITE3_TEXT
        );
        $rs = $sth->execute();

        // Read in entire result into an array and call alter row on each row.
        $this->view->result = array();
        while ($row = $rs->fetchArray(SQLITE3_ASSOC)) {
            $this->_alterRow($row);
            $this->view->result[] = $row;
        }

        $this->_render();
    }
}
