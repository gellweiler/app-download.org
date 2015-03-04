<?php
class sitemap extends Controller {

    /**
     * List all main urls.
     */
    public function render()
    {
        header('Content-Type: application/xml');
        $this->_render();
    }

}
