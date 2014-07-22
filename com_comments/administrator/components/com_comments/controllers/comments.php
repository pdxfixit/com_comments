<?php

defined('_JEXEC') or die;

class CommentsControllerComments extends FOFController {

    public function display($cachable = false, $urlparams = false) {
        $this->getView('Comments', 'Html', 'CommentsView');

        return parent::display($cachable, $urlparams);
    }

}
