<?php

class Api_LoginController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $logger = Zend_Registry::get('logger');
        $logger->info(
            sprintf(
                "[API] Api_LoginController::indexAction : Calling '%s' method from '%s' controller in API.",
                $this->_request->getActionName(),
                $this->_request->getControllerName()
            )
        );
        header('Content-type: application/json');

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $server = new SDIS62_Rest_Server;
        $server->setClass("Api_Service_Login");
        $server->handle($this->_request->getParams());
    }
}