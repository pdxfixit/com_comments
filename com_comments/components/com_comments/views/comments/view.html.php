<?php

defined('_JEXEC') or die;

class CommentsViewComments extends FOFViewHtml {

    // used to store the article object, from the plg_content_comments plugin
    public $article = null;

    public function getAvatar($id) {
        $peopleModel = FOFModel::getAnInstance('People', 'CommentsModel');

        return $peopleModel->getImage($id);
    }

    public function pluginDisplay($article = null) {
        $this->article = $article;
        $this->onDisplay();

        return $this->loadTemplate();
    }

    public function onDisplay($data = array()) {
        $commentsModel = FOFModel::getAnInstance('Comments', 'CommentsModel');
        $this->setModel($commentsModel, true);
        $subscriptionsModel = FOFModel::getAnInstance('Subscriptions', 'CommentsModel');
        $this->table        = array_key_exists('table', $data) ? $data['table'] : $this->input->get('table', 'foobar', 'string');
        $this->row          = array_key_exists('row', $data) ? $data['row'] : $this->input->get('row', 0, 'int');
        $this->user         = JFactory::getUser();

        if (!is_object($this->article) && strtolower($this->table) == 'content') {
            $contentModel = FOFModel::getAnInstance('Article', 'ContentModel');
            if ($this->row) {
                $this->article = $contentModel->getItem($this->row);
            }
        } else {
            $this->row   = $this->article->id;
            $this->table = 'content';
        }

        if ($this->article) {
            $commentsModel->setState('row', $this->row);
            $commentsModel->setState('table', $this->table);
            $commentsModel->clearInput();
            $this->comments = $commentsModel->getItemList();
        }

        $session = JFactory::getSession();

        $this->feedslug   = $this->article ? 'article/' . $this->article->alias : $this->input->get('feedslug', '', 'string');
        $this->params     = JComponentHelper::getParams('com_comments');
        $this->secret     = 'YXSoqWli4qzmAHnraDi2CM69j39YYtJWDyy6ai3WxXnjBJrfOGXDwLDPWG6XEEI';
        $this->session    = array(
            'referrer'         => JUri::current(),
            'username'         => $this->input->get('username', '', 'string'),
            'email'            => $this->input->get('email', '', 'string'),
            'comment'          => $this->input->get('comment', '', 'string'),
            'subscribe'        => $this->input->get('subscribe', '', 'string'),
            'invalid_username' => $session->get('invalid_username', false, 'comments'),
            'invalid_email'    => $session->get('invalid_email', false, 'comments'),
            'invalid_comment'  => $session->get('invalid_comment', false, 'comments'),
            'require_captcha'  => $this->input->get('require_captcha', false),
            'captcha_failed'   => $this->input->get('captcha_failed', false),
            'mollom_id'        => $this->input->get('mollom_id', null, 'string'),
            'timelimit'        => $this->input->get('timelimit', 0, 'int'),
        );
        $this->subscriber = false;

        // returnurl
        if ($_SERVER['REQUEST_URI'] !== '/') {
            if ($session->get('refreshed')) {
                // because FOF does a base64_decode on this
                $this->returnurl = base64_encode($_SERVER['REQUEST_URI']);
            } else {
                $this->returnurl = $_SERVER['REQUEST_URI'];
            }
        }

        if (!$this->user->guest && $this->article) {
            if ($subscriptionsModel->amIaSubscriber($this->user->id, $this->article, $this->table)) {
                $this->subscriber = true;
            }
        }
    }

    public function hasReports($id) {
        $column       = $this->user->guest ? 'ip' : 'created_by';
        $value        = $this->user->guest ? FOFTable::getAnInstance('Comment', 'CommentsTable')->getIp() : $this->user->id;
        $reportsModel = FOFModel::getAnInstance('Reports', 'CommentsModel');
        $reportsModel->setState('comment_id', $id);
        $reportsModel->setState('state', '0');
        $reportsModel->setState($column, $value);

        return $reportsModel->hasReports();
    }

}
