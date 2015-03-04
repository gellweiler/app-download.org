<?php
class AppCrawler
{
    /**
     * The apps rank.
     */
    protected $rank = 1;

    public function __construct()
    {
        // Get top 500 apps from the list.
        $base = 'https://play.google.com/store/apps/collection/topselling_free';
        for ($i = 1; $i <= 401; $i += 100) {
            $this->parse($base . '?num=100&start=' . $i);
        }
   }

    /**
     * Get App info from the tiles.
     */
    protected function parse($url)
    {
        $html = mb_convert_encoding(file_get_contents($url), 'HTML-ENTITIES', "UTF-8");

        // Get Xpath for HTML of App List.
        libxml_use_internal_errors(true);
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $xpath = new DomXPath($dom);


        // Get all tiles.
        $tiles = $xpath->query(
            '//*[contains(@class, "apps")][contains(@class, "card")]');

        // Walk through all tiles.
        foreach ($tiles as $tile) {
            // Information to collect about each app.
            $package = ''; // The package name of the app.
            $title = ''; // The title of the app.
            $author = ''; // The author of the app.
            $description = ''; // A description of the app including html formating.

            // Extrackt package name from link to download.
            $link = $xpath->query(
                './/a[contains(@class, "card-click-target")][1]',
                $tile
            )->item(0);
            foreach ($link->attributes as $attr => $item) {
                if (strtolower($attr) == 'href') {
                    if (preg_match(
                        '/(\\&|\\?)id=([^\\&]+)/',
                        $item->value,
                        $matches
                    ) === 1) {

                        $package = trim(urldecode($matches[2]));
                    }
                }
            }

            // Extract title of app.
            $title_node = $xpath->query(
                './/*[contains(@class, "title")][@title]',
                $tile
            )->item(0);
            foreach ($title_node->attributes as $attr => $item) {
                if (strtolower($attr) == 'title') {
                    $title = trim($item->value);
                }
            }

            // Extract author of app.
            $author_node = $xpath->query(
                './/a[contains(@class, "subtitle")]',
                $tile                
            )->item(0);
            foreach ($author_node->attributes as $attr => $item) {
                if (strtolower($attr) == 'title') {
                    $author = trim($item->value);
                }
            }
            
            // Extract description about app.
            $description_node = $xpath->query(
                './/*[contains(@class, "description")]',
                $tile
            )->item(0);
            foreach ($description_node->childNodes as $node) {
                // Continue till end of paragraph mark.
                foreach ($node->attributes as $attr => $item) {
                    if (strtolower($attr) == 'class') {
                        if (strpos($item->value, 'paragraph-end') !== FALSE) {
                            break 2;
                        }
                    }
                }

                $description .= trim($dom->saveXML($node));
            }

            // Insert entry for topApps table.
            $db = DBApps::getInstance()->db;
            $sth = $db->prepare('INSERT INTO topApps(rank, package) VALUES(:rank, :package);');
            $sth->bindValue(':rank', $this->rank, SQLITE3_INTEGER);
            $sth->bindValue(':package', $package, SQLITE3_TEXT);
            $sth->execute();

            // Update app metadata in Apps table.
            $sth2 = $db->prepare('INSERT OR REPLACE INTO Apps(package, title, author, description) VALUES(:package, :title, :author, :desc);');
            $sth2->bindValue(':package', $package, SQLITE3_TEXT);
            $sth2->bindValue(':title', $title, SQLITE3_TEXT);
            $sth2->bindValue(':author', $author, SQLITE3_TEXT);
            $sth2->bindValue(':desc', $description, SQLITE3_TEXT);
            $sth2->execute();

            $this->rank++;
        }
    }
}
