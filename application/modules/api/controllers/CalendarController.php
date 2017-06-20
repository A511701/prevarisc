<?php

class Api_CalendarController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $logger = Zend_Registry::get('logger');
        $logger->info(
            sprintf(
                "[API] Api_CalendarController::indexAction : Calling '%s' method from '%s' controller in API.",
                $this->_request->getActionName(),
                $this->_request->getControllerName()
            )
        );
        $idCommission = 0;
        if ($this->_getParam('commission')) {
            $idCommission = $this->_getParam('commission');
        }

        $headers = array("Content-Type: text/Calendar; charset=utf-8");

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $server = new SDIS62_Rest_Server();
        $server->setClass("Api_Service_Calendar");
        $server->handle($this->_request->getParams(), $headers, false);
    }
}