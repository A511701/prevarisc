<?php

class Api_DossierController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $logger = Zend_Registry::get('logger');
        $logger->info(
            sprintf(
                "[API] Api_DossierController::indexAction : Calling '%s' method from '%s' controller in API.",
                $this->_request->getActionName(),
                $this->_request->getControllerName()
            )
        );
        header('Content-type: application/json');

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $server = new SDIS62_Rest_Server;
        $server->setClass("Api_Service_Dossier");
        $server->handle($this->_request->getParams());
    }
}