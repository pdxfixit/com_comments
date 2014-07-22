<?php

defined('_JEXEC') or die();

class CommentsViewComments extends FOFViewForm {

    public function display($tpl = null) {
        $model = $this->getModel();
        $item  = $model->getItem();

        if ($item->comments_comment_id) {
            // Get the reports
            $reportsModel = FOFModel::getAnInstance('Reports', 'CommentsModel');
            $reportsModel->setState('comment_id', $item->comments_comment_id);
            $this->reports = $reportsModel->getItemList();
            $reportsModel->clearState();

            // Get the timeline
            $commentsModel = FOFModel::getAnInstance('Comments', 'CommentsModel');
            $commentsModel->setState('row', $item->row);
            $commentsModel->setState('table', $item->table);
            $commentsModel->clearInput();
            $this->timeline = $commentsModel->getItemList();
            $commentsModel->clearState();
        }

        return parent::display($tpl);
    }

    public function getUsername($id) {
        $user = JFactory::getUser($id);
        if ($user->name) {
            return $user->name;
        } else {
            return 'Guest User';
        }
    }

}
