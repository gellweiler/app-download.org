<?php
class afterdownload extends Controller
{
    /**
     * Prepare afterdownload dialog to be loaded into an iframe.
     */
    public function frame() {
        $this->view->display('afterdownload_frame');
    }
}