<?php

defined('_JEXEC') or die;

class PlgContentComments extends JPlugin {

    public function __construct(&$subject, $config = array()) {
        include_once JPATH_LIBRARIES . '/fof/include.php';
        if (!defined('FOF_INCLUDED')) {
            throw new Exception ('FOF is not installed', 500);
        }

        return parent::__construct($subject, $config);
    }

    /**
     * Display the comments view on the article page
     */
    function onContentAfterDisplay($context, &$article, &$params, $page = 0) {
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            return;
        }
        $this->loadLanguage();

        // If this is the article view display the comments
        if ($context == 'com_content.article' && $this->params->get('enable_content', 0) && FOFInput::getCmd('tmpl') != 'component') {
            // initialize
            $commentsController = FOFController::getTmpInstance('com_comments', 'comments');
            $commentsView       = $commentsController->getView('Comments', 'Html', 'CommentsView');

            return $commentsView->pluginDisplay($article);
        }
    }

}
