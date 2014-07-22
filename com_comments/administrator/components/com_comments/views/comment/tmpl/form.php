<?php

defined('_JEXEC') or die();

JHtml::_('jquery.ui');
$document = JFactory::getDocument();
$document->addStyleSheet('/media/com_comments/css/accordion.min.css');
$document->addStyleSheet('/media/com_comments/css/admin.css');
$document->addScript('/media/com_comments/js/accordion.min.js');
$document->addScript('/media/com_comments/js/admin.js');

// load the XML form
$viewTemplate = $this->getRenderedForm();
echo $viewTemplate;

/* Timeline */
if (count($this->timeline)) {
    ?>
    <div id="comments-timeline" class="col validation-advice-align-left">
        <ul class="comments-list">
            <?php foreach ($this->timeline as $row) { ?>
                <li class="comment<?php echo ($row->comments_comment_id == $this->item->comments_comment_id) ? ' active-comment' : ''; ?><?php echo $row->enabled ? '' : ' disabled'; ?>">
                    <h1 class="author">
                        <?php echo $row->username ? $row->username : $this->getUsername($row->created_by); ?>
                        <span><?php echo JText::_('COM_COMMENTS_SAID'); ?></span>:
                        <?php if ($row->comments_comment_id != $this->item->comments_comment_id) : ?>
                            <a href="index.php?option=com_comments&view=comment&id=<?php echo $row->comments_comment_id; ?>"><?php echo JText::_('COM_COMMENTS_EDIT'); ?></a>
                        <?php endif ?>
                    </h1>

                    <div class="comment-info">
                        <time datetime="<?php echo $row->created_on; ?>" title="<?php echo JFactory::getDate($row->created_on)->format('l, d F Y H:i:s'); ?>"><?php echo JFactory::getDate($row->created_on)->format('d M'); ?></time>
                        <p><?php echo JText::_('COM_COMMENTS_IP') . ': ' . $row->ip; ?></p>
                        <?php if (isset($row->quality)) : ?>
                            <p><?php echo JText::_('COM_COMMENTS_RATING') . ': ' . $row->quality; ?></p>
                        <?php endif ?>
                    </div>
                    <p><?php echo strip_tags($row->comment); ?></p>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php
}

/* Reports */
if (count($this->reports)) {
    ?>
    <div id="comments-reports">
        <div class="reporting-options">
            <a href="javascript:void(0);" id="comments-mark-reports-valid" title="<?php echo JText::_('COM_COMMENTS_MARK_ALL_VALID'); ?>"><?php echo JText::_('COM_COMMENTS_MARK_ALL_VALID'); ?></a><br />
            <a href="javascript:void(0);" id="comments-mark-reports-invalid" title="<?php echo JText::_('COM_COMMENTS_MARK_ALL_INVALID'); ?>"><?php echo JText::_('COM_COMMENTS_MARK_ALL_INVALID'); ?></a>
        </div>
        <h3><?php echo JText::_('COM_COMMENTS_REPORTS'); ?></h3>

        <div id="comments-report-accordion">
            <?php foreach ($this->reports as $report) { ?>
                <h4><?php echo JText::sprintf('Reporter: %s', $this->getUsername($report->created_by)) . ' <span>' . JText::_('COM_COMMENTS_REPORT_STATUS' . $report->state) . '</span>'; ?></h4>
                <div class="comments-report-panel">
                    <div class="element">
                        <label class="key hasTip" title="<?php echo JText::_('COM_COMMENTS_REPORT_REPORTED_DESC'); ?>"><?php echo JText::_('COM_COMMENTS_REPORTED'); ?></label>
                        <time datetime="<?php echo $report->created_on; ?>" title="<?php echo JFactory::getDate($report->created_on)->format('l, d F Y H:i:s'); ?>"><?php echo JFactory::getDate($report->created_on)->format('d M'); ?></time>
                    </div>
                    <div class="element">
                        <label class="key hasTip" title="<?php echo JText::_('COM_COMMENTS_REPORT_STATE_DESC'); ?>"><?php echo JText::_('COM_COMMENTS_STATE'); ?></label>
                        <select name="report[<?php echo $report->comments_report_id ?>]" class="comments-report-state">
                            <?php $states = array(0 => 'New Report', 1 => 'Valid Report', 2 => 'Invalid Report');
                            foreach ($states as $k => $state) {
                                echo '<option value="' . $k;
                                if ($report->state == $k) {
                                    echo '" selected="selected';
                                }
                                echo '">' . $state . '</option>';
                            } ?>
                        </select>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php
}
