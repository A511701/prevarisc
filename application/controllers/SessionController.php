<?php

class SessionController extends Zend_Controller_Action
{
    public function loginAction()
    {
        $this->_helper->layout->setLayout('login');
        $logger = Zend_Registry::get('logger');

        $form = new Form_Login();
        $service_user = new Service_User();
        $username = null;
        $password = "";
        $user = null;
        $this->view->form = $form;

        try {
            // Adaptateur CAS
            if (getenv('PREVARISC_CAS_ENABLED') == 1) {
                $username = phpCAS::getUser();
                $logger->info(sprintf(
                    "SessionController::loginAction: Get CAS user '%s'",
                    $username
                ));

            // Adapter NTLM
            } else if (getenv('PREVARISC_NTLM_ENABLED') == 1) {

                if (!isset($_SERVER['REMOTE_USER'])) {
                    $logger->err("SessionController::loginAction: ntlm auth with no REMOTE_USER set in server variables");
                    error_log('ntlm auth with no REMOTE_USER set in server variables');
                } else {
                    $cred = explode('\\', $_SERVER['REMOTE_USER']);
                    if (count($cred) == 1) array_unshift($cred, null);
                    list($domain, $username) = $cred;
                }
                $logger->info(sprintf(
                    "SessionController::loginAction: Get NTLM user '%s'",
                    $username
                ));

            // Cas par défaut
            } else if ($this->_request->isPost()) {

                if (!$form->isValid($this->_request->getPost())) {
                    error_log("Auth: formulaire classique invalide");
                    throw new Zend_Auth_Exception('Authentification invalide.');
                }

                // Identifiants
                $username = $this->_request->prevarisc_login_username;
                $logger->info(sprintf(
                    "SessionController::loginAction: Get user '%s' by request",
                    $username
                ));
                $password = $this->_request->prevarisc_login_passwd;
            }

            if ($username) {
                $logger->info(sprintf(
                    "SessionController::loginAction: Try to connect '%s'",
                    $username
                ));

                // Récupération de l'utilisateur
                $user = $service_user->findByUsername($username);

                // Si l'utilisateur n'est pas actif, on renvoie false
                if ($user === null || ($user !== null && !$user['ACTIF_UTILISATEUR'])) {
                    error_log("Auth: utilisateur inexistant ou inactif '$username'");
                    throw new Zend_Auth_Exception('Authentification invalide.');
                }

                // Authentification adapters
                $adapters = array();

                // Adaptateur SSO noauth
                if (getenv('PREVARISC_CAS_ENABLED') == 1 || getenv('PREVARISC_NTLM_ENABLED') == 1 ) {
                    $adapters['sso'] = new Service_PassAuthAdapater($username);

                // Cas classique s'il y a déjà eu des login infructueux
                // Système anti-dos, anti-bruteforce : si pas l'ip habituelle, on drop la requête
                } else if (getenv('PREVARISC_ENFORCE_SECURITY') == 1
                        && isset($user['FAILED_LOGIN_ATTEMPTS_UTILISATEUR'])
                        && $user['FAILED_LOGIN_ATTEMPTS_UTILISATEUR'] >= 2
                        && isset($user['IP_UTILISATEUR'])
                        && $user['IP_UTILISATEUR']
                    ) {
                        if ($user['IP_UTILISATEUR'] != $_SERVER['REMOTE_ADDR']) {
                            error_log("Auth: trop d'essais infructeurs, denying IP ".$_SERVER['REMOTE_ADDR']." which does not match last login IP ".$user['IP_UTILISATEUR']);
                            throw new Zend_Auth_Exception('Authentification invalide.');
                        }
                }

                // Adaptateur principal (dbtable)
                $adapters['dbtable'] = new Zend_Auth_Adapter_DbTable(null, 'utilisateur', 'USERNAME_UTILISATEUR', 'PASSWD_UTILISATEUR');
                $adapters['dbtable']->setIdentity($username)->setCredential(md5($username . getenv('PREVARISC_SECURITY_SALT') . $password));

                // Adaptateur LDAP
                if (getenv('PREVARISC_LDAP_ENABLED') == 1) {
                    $logger->info("SessionController::loginAction: LDAP Adaptor building...");
                    $ldap = new Zend_Ldap(array('host' => getenv('PREVARISC_LDAP_HOST'), 'port' => getenv('PREVARISC_LDAP_PORT') ? : 389, 'username' => getenv('PREVARISC_LDAP_USERNAME'), 'password' => getenv('PREVARISC_LDAP_PASSWORD'), 'baseDn' => getenv('PREVARISC_LDAP_BASEDN')));
                    try {
                        $accountForm = getenv('PREVARISC_LDAP_ACCOUNT_FORM') ? getenv('PREVARISC_LDAP_ACCOUNT_FORM') : Zend_Ldap::ACCTNAME_FORM_DN;
                        $adapters['ldap'] = new Zend_Auth_Adapter_Ldap();
                        $adapters['ldap']->setLdap($ldap);
                        $adapters['ldap']->setUsername($ldap->getCanonicalAccountName($username, $accountForm));
                        $adapters['ldap']->setPassword($password);
                    } catch (Exception $e) {
                        error_log("Auth: ldap exception: ".$e->getMessage());
                    }
                }

                // On lance le process d'identification avec les différents adaptateurs
                $logger->info("SessionController::loginAction: Check Adaptor validity");
                foreach ($adapters as $key => $adapter) {
                    if ($adapter->authenticate()->isValid()) {
                        $logger->info(sprintf(
                            "SessionController::loginAction: '%s' is now connected",
                            $username
                        ));
                        $service_user->resetFailedLogin($user);
                        Zend_Auth::getInstance()->getStorage()->write($user);
                        $this->_helper->redirector->gotoUrl(empty($this->_request->getParams()["redirect"]) ? '/' : urldecode($this->_request->getParams()["redirect"]));
                    }
                }

                error_log("Auth: password incorrect pour '$username'");
                throw new Zend_Auth_Exception('Authentification invalide.');
            }

        } catch (Exception $e) {
            $service_user->logFailedLogin($user);
            $this->_helper->flashMessenger(array('context' => 'danger', 'title' => 'Erreur d\'authentification', 'message' => $e->getMessage()));
        }
    }

    public function logoutAction()
    {
        $logger = Zend_Registry::get('logger');
        $auth = Zend_Auth::getInstance();

        $user = $auth->getIdentity();
        if($auth->hasIdentity()) {
            $service_user = new Service_User;

            $logger->info(sprintf(
            "SessionController::logoutAction: User '%s'Logout",
            $user['USERNAME_UTILISATEUR']));

            $service_user->updateLastActionDate($auth->getIdentity()['ID_UTILISATEUR'], null);

            $auth->clearIdentity();
        }

        if (getenv('PREVARISC_CAS_ENABLED') == 1) {
            phpCAS::logout();
        // On test si l'utilisateur est connecté en NTLM
        } else if (getenv('PREVARISC_NTLM_ENABLED') == 1 && $user && $user['PASSWD_UTILISATEUR'] == null) {
            $this->_helper->layout->setLayout('error');
        } else {
            $this->_helper->redirector->gotoUrl($this->view->url(array("controller" => null, "action" => null)));
        }
    }
}

