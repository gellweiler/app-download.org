<?php
class index extends Controller
{
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Render content of main page.
     */
    public function render()
    {
        // Attach widgets to the view to render them in the view.
        $this->view->account = account::getInstance();
        $this->view->device = device::getInstance();
        $this->view->download = download::getInstance();

        $this->_render();
        exit();
    }
}
