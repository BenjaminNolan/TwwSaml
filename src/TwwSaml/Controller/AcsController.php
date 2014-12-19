<?php

namespace TwwSaml\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class AcsController extends AbstractActionController
{

    public function indexAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $config = $serviceLocator->get('Config');
        $auth = new \OneLogin_Saml2_Auth($config['tww-saml']['settings']);
        $auth->processResponse();

        $errors = $auth->getErrors();
        if (!empty($errors)) {
            throw new \Exception('Errors returned from SAML library: '.implode(', ', $errors));
        }

        if (!$auth->isAuthenticated()) {
            throw new \Exception('Authentication failed.');
        }

        if (isset($config['tww-saml']['storage']) && $config['tww-saml']['storage']['type'] == 'zend_session') {
            $className = $config['tww-saml']['storage']['container_class'];
            $container = new $className($config['tww-saml']['storage']['container_name']);
            $container->samlUserdata = $auth->getAttributes();
        } else {
            $_SESSION['samlUserdata'] = $auth->getAttributes();
        }
        if (isset($_POST['RelayState']) && (strlen($_POST['RelayState']) > 0) && (\OneLogin_Saml2_Utils::getSelfURL() != $_POST['RelayState'])) {
            return $this->redirect()->toUrl($_POST['RelayState']);
        } else {
            return $this->redirect()->toRoute('home');
        }
    }

}
