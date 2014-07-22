<?php

defined('_JEXEC') or die;

// include Mollom
include_once(JPATH_ADMINISTRATOR . '/components/com_comments/helpers/mollom.php');
$mollom = new Mollom();

// prepare Timestamp + Secret
$timestamp = time();
$timestamp_secret = sha1($timestamp . $this->secret);

// prepare Document
JHtml::_('behavior.framework', true);
JHtml::_('bootstrap.framework');
// we need to call the frameworks first, so they're available to us...
FOFTemplateUtils::addCSS('media://com_comments/css/comments.css');
FOFTemplateUtils::addJS('media://com_comments/js/comments.js');

if ($this->row && $this->table) {
    ?>
    <script type="text/javascript">
        window.addEvent('domready', function () {
            $('rpoohcheck').set('defaultValue', '');
            $$('.subscribed').addEvent('mouseover',function (event) {
                event.target.set('html', '<?php echo JText::_("COM_COMMENTS_UNSUBSCRIBE"); ?>');
            }).addEvent('mouseleave', function (event) {
                event.target.set('html', '<?php echo JText::_("COM_COMMENTS_SUBSCRIBED"); ?>');
            });
        });
    </script>

    <div id="comments" class="comments">
        <h2 class="heading h2"><?php echo JText::_('COM_COMMENTS_COMMENTS'); ?></h2>

        <?php if (!$this->user->guest) { ?>
            <a data-noasync class="<?php echo $this->subscriber ? strtolower(JText::_('COM_COMMENTS_SUBSCRIBED')) : strtolower(JText::_('COM_COMMENTS_SUBSCRIBE')); ?> button" title="<?php echo JText::_('COM_COMMENTS_SUBSCRIBE'); ?>" data-action="<?php echo $this->subscriber ? 'unsubscribe' : 'subscribe'; ?>" href="#"><?php echo $this->subscriber ? JText::_('COM_COMMENTS_SUBSCRIBED') : JText::_('COM_COMMENTS_SUBSCRIBE'); ?></a>
        <?php } ?>

        <?php if ($this->feedslug) { ?>
            <a data-noasync class="rss" href="/feeds/comments/<?php echo $this->feedslug; ?>" title="<?php echo JText::_('COM_COMMENTS_SUBSCRIBE_TO_COMMENTS_FEED'); ?>"><span><?php echo JText::_('COM_COMMENTS_SUBSCRIBE_TO_COMMENTS_FEED'); ?></span></a>
        <?php } ?>

        <?php if (count($this->comments)) { ?>
            <ul class="comments-list">
                <?php foreach (@$this->comments as $comment) { ?>
                    <li>
                        <article id="c<?php echo $comment->comments_comment_id; ?>" class="comment clearfix <?php echo $comment->enabled ? '' : 'disabled'; ?>">
                            <a name="comment-<?php echo $comment->comments_comment_id; ?>" id="comment-<?php echo $comment->comments_comment_id; ?>"></a>
                            <header>
								<span class="author">
									<?php echo $comment->created_by ? JFactory::getUser($comment->created_by)->name : $comment->username; ?>
                                    <span><?php echo JText::_('COM_COMMENTS_SAID'); ?></span>:
								</span>
                                <time datetime="<?php echo $comment->created_on; ?>" title="<?php echo JFactory::getDate($comment->created_on)->format('l, d F Y H:i:s'); ?>"><?php echo JFactory::getDate($comment->created_on)->format('d M'); ?></time>
                                <?php if ($this->user->authorise(null)) { ?>
                                    <span class="comment-ip"><?php echo JText::_('COM_COMMENTS_IP') . ': ' . $comment->ip; ?></span>
                                <?php } ?>
                            </header>
                            <?php if ($this->params->get('gravatar') /*&& $comment->created_by*/) { ?>
                                <img src="<?php echo $this->getAvatar($comment->created_by); ?>" width="48px" height="48px" class="avatar" />
                            <?php } ?>

                            <p><?php echo $comment->comment ?></p>

                            <div class="comment-controls">
                                <?php if (!$this->hasReports($comment->comments_comment_id)) { ?>
                                    <a data-noasync href="#" class="button" data-action="report" data-id="<?php echo $comment->comments_comment_id; ?>"><?php echo JText::_('COM_COMMENTS_REPORT'); ?></a>
                                <?php } ?>

                                <a data-noasync href="#" class="reply button" data-action="reply" data-id="<?php echo $comment->comments_comment_id; ?>"><?php echo JText::_('COM_COMMENTS_REPLY'); ?></a>

                                <?php if ($this->user->authorise(null)) { ?>
                                    <a data-noasync class="unpublish button" title="<?php echo $comment->enabled ? JText::_('COM_COMMENTS_DISABLE_COMMENT') : JText::_('COM_COMMENTS_ENABLE_COMMENT'); ?>" data-action="<?php echo $comment->enabled ? 'unpublish' : 'publish'; ?>" data-id="<?php echo $comment->comments_comment_id; ?>" href="#"><?php echo $comment->enabled ? JText::_('COM_COMMENTS_DISABLE') : JText::_('COM_COMMENTS_ENABLE'); ?></a>
                                    <a data-noasync class="spam button" title="<?php echo JText::_('COM_COMMENTS_MARK_AS_SPAM'); ?>" data-action="spam" data-id="<?php echo $comment->comments_comment_id; ?>" href="#"><?php echo JText::_('COM_COMMENTS_SPAM'); ?></a>
                                <?php } ?>
                            </div>
                        </article>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>

        <?php if ($this->session['moderated']) { ?>
            <p><?php echo JText::_('COM_COMMENTS_COMMENT_MODERATED'); ?></p>
        <?php } ?>

        <?php if ($this->session['awaiting_approval']) { ?>
            <p><?php echo JText::_('COM_COMMENTS_COMMENT_AWAITING_APPROVAL'); ?></p>
        <?php } ?>

        <form action="<?php echo JRoute::_('/index.php?option=com_comments&view=comments&row=' . $this->row . '&table=' . $this->table); ?>" method="post" id="com-comments-comments" class="comments-form clearfix">
            <?php if ($flood = $this->session['timelimit']) { ?>
                <p class="invalid"><?php echo JText::sprintf('COM_COMMENTS_FLOOD_WARNING', $flood); ?></p>
            <?php } ?>

            <?php if ($this->user->guest) { ?>
                <label for="username"><?php echo JText::_('COM_COMMENTS_NAME'); ?>:</label>
                <input type="text" size="40" name="username" id="username" value="<?php echo $this->session['username']; ?>" required class="<?php echo $this->session['invalid_username'] ? 'invalid' : ''; ?>" />
                <label for="email"><?php echo JText::_('COM_COMMENTS_EMAIL'); ?>:</label>
                <input type="text" size="40" name="email" id="email" value="<?php echo $this->session['email']; ?>" required class="<?php echo $this->session['invalid_email'] ? 'invalid' : ''; ?>" />
            <?php } else { ?>
                <input type="hidden" name="username" id="username" value="<?php echo $this->user->username; ?>" />
                <input type="hidden" name="email" id="email" value="<?php echo $this->user->email; ?>" />
            <?php } ?>
            <input type="hidden" name="<?php echo JSession::getFormToken(); ?>" value="1" id="_token" />
            <label for="poohcheck" class="winnie"><?php echo JText::_('COM_COMMENTS_DO_NOT_ENTER_DATA'); ?>:</label>
            <input type="text" size="40" class="winnie" name="poohcheck" value="" />
            <input type="text" size="40" class="winnie" id="rpoohcheck" name="rpoohcheck" value="comment" />
            <label for="comment"><?php echo JText::_('COM_COMMENTS_COMMENT'); ?>:</label>
            <textarea name="comment" id="comment" required class="<?php echo $this->session['invalid_comment'] ? 'invalid' : ''; ?>"><?php echo $this->session['comment']; ?></textarea>
            <input type="checkbox" name="subscribe" id="subscribe" value="1" <?php echo $this->subscriber ? 'checked="checked"' : ''; ?> />
            <label for="subscribe" class="inline-label"><?php echo JText::_('COM_COMMENTS_SUBSCRIBE_TO_COMMENTS'); ?></label>
            <input type="hidden" name="format" value="raw" />
            <input type="hidden" name="row" value="<?php echo $this->row; ?>" />
            <input type="hidden" name="table" value="<?php echo $this->table; ?>" />
            <?php if ($this->session['require_captcha']) {
                if (!$this->session['captcha_failed']) {
                    ?>
                    <p><?php echo JText::_('COM_COMMENTS_SUSPECTED_SPAM'); ?></p>
                <?php } else { ?>
                    <p class="invalid"><?php echo JText::_('COM_COMMENTS_CAPTCHA_INCORRECT'); ?></p>
                <?php
                }
                $imageCaptcha = $mollom->getImageCaptcha($this->session['mollom_id']);
                echo $imageCaptcha['html'];
                ?>
                <input type="text" size="15" name="captcha_value" class="<?php echo $this->session['captcha_failed'] ? 'invalid' : ''; ?>" value="" required="required" />
            <?php } ?>
            <input type="hidden" name="mollom_id" value="<?php echo $this->session['mollom_id']; ?>" />
            <input type="hidden" name="timestamp" value="<?php echo $timestamp; ?>" />
            <input type="hidden" name="timestamp_secret" value="<?php echo $timestamp_secret; ?>" />
            <?php echo isset($this->returnurl) ? '<input type="hidden" name="returnurl" value="' . $this->returnurl . '" />' : ''; ?>
            <input type="hidden" name="task" value="save" />
            <input class="button" type="submit" value="<?php echo JText::_('Post Comment'); ?>" />
            <?php if ($this->feedslug) { ?>
                <input type="hidden" name="feedslug" value="<?php echo $this->feedslug; ?>" />
            <?php } ?>
        </form>
    </div>
<?php
//} else {
// todo: proper error output
}
