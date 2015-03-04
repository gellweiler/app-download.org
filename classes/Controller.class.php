<?php
class Controller extends Singleton
{
    protected $view;

    protected function __construct()
    {
        $this->view = new View();

        // Store controller name in view.
        $this->view->controller = get_class($this);
    }

    /**
     * Background function for render.
     * You should not override this function.
     * This should only be called from $this->render().
     */
    protected function _render()
    {
        $name = strtolower(get_class($this));
        $this->view->display("$name");
    }

    /**
     * Display the main view of the controller.
     * The main view has to be views/ControllerName.phtml.
     *
     * If you want to add variables to $this->view before rendering
     * override this method and call $this->render in the end.
     */
    public function render()
    {
        $this->_render();
    }
}
