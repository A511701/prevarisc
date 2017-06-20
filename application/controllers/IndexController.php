<?php

class IndexController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $service_user = new Service_User;
        $service_dashboard = new Service_Dashboard;
        $logger = Zend_Registry::get('logger');
        $logger->info("IndexController::indexAction: Get blocs config from Service_Dashboard");
        $blocsConfig = $service_dashboard->getBlocConfig();

        $identity = Zend_Auth::getInstance()->getIdentity();
        $user = $service_user->find($identity['ID_UTILISATEUR']);
        $logger->info(sprintf("IndexController::indexAction: User logged in: '%s'",
            $user['USERNAME_UTILISATEUR']
        ));
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        $acl = unserialize($cache->load('acl'));
        $profil = $user['group']['LIBELLE_GROUPE'];
        $blocs = array();
        $logger->info('IndexController::indexAction: Load all blocks.');
        foreach($blocsConfig as $blocId => $blocConfig) {
            if (!$blocConfig['acl'] || ($acl->isAllowed($profil, $blocConfig['acl'][0], $blocConfig['acl'][1]))) {
                $method = $blocConfig['method'];
                $logger->info(
                    sprintf("IndexController::indexAction: Load '%s' module [Id: '%s', Type: '%s'].",
                        $blocConfig['title'],
                        $blocId,
                        $blocConfig['type']
                    )
                );
                $blocs[$blocId] = array(
                    'type' => $blocConfig['type'],
                    'title' => $blocConfig['title'],
                    'height' => $blocConfig['height'],
                    'width' => $blocConfig['width'],
                );
            }
        }

        // determine the bloc order
        // user preferences
        $logger->info('IndexController::indexAction: Determine block order.');
        if (isset($user['preferences']['DASHBOARD_BLOCS'])
        && $user['preferences']['DASHBOARD_BLOCS']
        && $blocsOrder = json_decode($user['preferences']['DASHBOARD_BLOCS'])
        ) {
            // treat the case where there will be new bloc added
            foreach(array_keys($blocsConfig) as $defaultBloc) {
                if (!in_array($defaultBloc, $blocsOrder)) {
                    $blocsOrder[] = $defaultBloc;
                }
            }
        } else {
            $blocsOrder = array_keys($blocsConfig);
        }

        $this->view->user = $user;
        $this->view->blocs = $blocs;
        $this->view->blocsOrder = $blocsOrder;
        $this->view->inlineScript()->appendFile("/js/jquery.packery.pkgd.min.js");
        $this->_helper->layout->setLayout('index');
        $this->render('index');
    }

    public function blocAction()
    {
        $this->_helper->layout->disableLayout();

        $id = $this->getParam('id');
        $logger = Zend_Registry::get('logger');
        $logger->info('IndexController::BlocAction: Bloc building...');

        $bloc = array();
        $service_user = new Service_User;
        $service_dashboard = new Service_Dashboard;
        $logger->info('IndexController::BlocAction: Get bloc configuration.');
        $blocsConfig = $service_dashboard->getBlocConfig();

        if (isset($blocsConfig[$id])) {
            $blocConfig = $blocsConfig[$id];
            $identity = Zend_Auth::getInstance()->getIdentity();
            $user = $service_user->find($identity['ID_UTILISATEUR']);
            $logger->info(
                sprintf("IndexController::BlocAction: User logged in: '%s'",
                    $user['USERNAME_UTILISATEUR']
                )
            );
            $service = new $blocConfig['service'];
            $method = $blocConfig['method'];
            $bloc = array(
                'id' => $id,
                'data' => $service->$method($user),
                'type' => $blocConfig['type'],
                'title' => $blocConfig['title'],
                'height' => $blocConfig['height'],
                'width' => $blocConfig['width'],
            );
        }

        $logger->info("IndexController::BlocAction: Adding bloc to view.");
        $this->view->bloc = $bloc;
    }

    public function addMessageAction()
    {
        $service_feed = new Service_Feed;
        $service_user = new Service_User;

        $this->view->groupes = $service_user->getAllGroupes();
        if ($this->_request->isPost()) {
            try {
                $service_feed->addMessage($this->_request->getParam('type'), $this->_request->getParam('text'), Zend_Auth::getInstance()->getIdentity()['ID_UTILISATEUR'], $this->_request->getParam('conf') );
                $this->_helper->flashMessenger(array('context' => 'success','title' => 'Message ajouté !','message' => 'Le message a bien été ajouté.'));
            } catch (Exception $e) {
                $this->_helper->flashMessenger(array('context' => 'danger','title' => 'Erreur !','message' => 'Erreur lors de l\'ajout du message : ' . $e->getMessage()));
            }
            $this->_helper->redirector('index', 'index');
        }
    }

    public function deleteMessageAction()
    {
        $service_feed = new Service_Feed;

        try {
            $service_feed->deleteMessage($this->_request->getParam('id'));
            $this->_helper->flashMessenger(array('context' => 'success','title' => 'Message supprimé !','message' => 'Le message a bien été supprimé.'));
        } catch (Exception $e) {
            $this->_helper->flashMessenger(array('context' => 'danger','title' => 'Erreur !','message' => 'Erreur lors de la suppression du message : ' . $e->getMessage()));
        }
        $this->_helper->redirector('index', 'index');
    }
}
