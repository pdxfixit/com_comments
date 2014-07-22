<?php

defined('_JEXEC') or die();

class CommentsViewUnsubscribe extends FOFViewHtml {

    public function display($tpl = null) {
        $input                    = new FOFInput();
        $comments_subscription_id = $input->get('id', '');

        if (!empty($comments_subscription_id)) {
            $subscriptionModel = FOFModel::getAnInstance('Subscriptions', 'CommentsModel');
            $subscription      = $subscriptionModel->getItem($comments_subscription_id);

            if ($subscription->comments_subscription_id) {
                $subscription->delete();
                $this->text = JText::_('COM_COMMENTS_SUBSCRIPTION_REMOVED');
            }
        } else {
            $this->text = JText::_('COM_COMMENTS_UNABLE_TO_FIND_SUBSCRIPTION');
        }

        return parent::display($tpl);
    }
}
