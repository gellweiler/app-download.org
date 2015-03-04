<?php
class sitemapapps extends Controller {
    const LIMIT = 45000;

    /**
     * Generate sitemap with links to all apps for language:.
     */
    public function render()
    {
        header('Content-Type: application/xml');

        // Calculate offset.
        if (!empty($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
            $offset = self::LIMIT * ($_GET['page'] -1);
        } else {
            $offset = 0;
        }

        // Get List of urls from DB.
        $sql =
<<<'EOF'
SELECT :base_detail_url || package as url
FROM Apps
ORDER BY package
LIMIT :limit OFFSET :offset;
EOF;
        $db = DBApps::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':base_detail_url', url('apps/app/'), SQLITE3_TEXT);
        $sth->bindValue(':limit', self::LIMIT, SQLITE3_INTEGER);
        $sth->bindValue(':offset', $offset, SQLITE3_INTEGER);

        $this->view->result = $sth->execute();

        $this->_render();
    }

    /**
     * List all sitemaps.
     */
    public function index()
    {
        header('Content-Type: application/xml');

        $this->view->sitemaps = $this->_get_sitemaps();

        $this->view->display('sitemapapps_index');
    }

    /**
     * Get a list of urls to all app sitemaps for language.
     */
    private  function _get_sitemaps()
    {
        $sitemaps = array();

        // Get total number of available apps.
        $db = DBApps::getInstance()->db;
        $count = $db->querySingle('SELECT count(*) As cnt FROM Apps;');

        for (
            $i = $c = 0;
            $c < $count;
            $c += self::LIMIT, $i++
        ) {
            $sitemaps[] = url('sitemapapps', array('page' => $i));
        }

        return $sitemaps;
    }
}
