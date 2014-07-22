<?php

defined('_JEXEC') or die();

class CommentsToolbar extends FOFToolbar {

    /**
     * Disable rendering a toolbar.
     * @return array
     */
    protected function getMyViews() {
        return array('comments', 'blackips', 'blackhosts', 'blackemails', 'whiteips');
    }

    public function onCommentsBrowse() {
        JToolbarHelper::editList();
        $this->deleteOnly('COM_COMMENTS_COMMENTS');
    }

    public function onCommentsEdit() {
        JToolbarHelper::apply();
        JToolbarHelper::save();
        JToolbarHelper::cancel();
        JToolbarHelper::title(JText::_('COM_COMMENTS') . ' &ndash; ' . JText::_('COM_COMMENTS_TITLE_COMMENTS_EDIT'));
    }

    public function onBlackemailsBrowse() {
        $this->deleteOnly('COM_COMMENTS_BLACKEMAILS');
    }

    public function onBlackhostsBrowse() {
        $this->deleteOnly('COM_COMMENTS_BLACKHOSTS');
    }

    public function onBlackipsBrowse() {
        $this->deleteOnly('COM_COMMENTS_BLACKIPS');
    }

    public function onWhiteipsBrowse() {
        $this->deleteOnly('COM_COMMENTS_WHITEIPS');
    }

    private function deleteOnly($title) {
        JToolbarHelper::deleteList();
        JToolbarHelper::divider();
        JToolBarHelper::preferences('com_comments');
        $this->renderSubmenu();
        JToolbarHelper::title(JText::_('COM_COMMENTS') . ' &ndash; ' . JText::_($title), 'comments');
    }

}
