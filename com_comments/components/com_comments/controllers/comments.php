<?php

defined('_JEXEC') or die;

class CommentsControllerComments extends FOFController {

    /**
     * @var array An array of spam checks to be executed.
     */
    protected $_checks = array(
        'data',
        'flooding',
        'honeypot',
        'reverseHoneypot',
        'blacklist',
        'blackHost',
        'blackEmail',
        'timestamp',
        'spamhaus',
        'honeypotProject',
        'botscout',
        'mollomAnalysis',
        'captcha'
    );

    /**
     * @var string The client IP.
     */
    protected $_client_ip;

    /**
     * @var array An array containing the checks that failed.
     */
    protected $_failed_checks = array();

    /**
     * @var string the flooding time limit.
     */
    protected $_flood_limit;

    /**
     * @var array array of honeypot fields
     */
    protected $_honeypots = array('honeypot' => 'poohcheck', 'reverseHoneypot' => 'rpoohcheck');

    /**
     * @var array An array of invalid data
     */
    protected $_invalid_data;

    /**
     * @var object an instance of the mollom helper class
     */
    private $_mollom;

    /**
     * @var string the mollom session id
     */
    protected $_mollom_id;

    /**
     * @var string A secret string for generating hashes.
     */
    protected $_secret = 'YXSoqWli4qzmAHnraDi2CM69j39YYtJWDyy6ai3WxXnjBJrfOGXDwLDPWG6XEEI';

    /**
     * @var boolean True is the form/data is spammed, false otherwise.
     */
    protected $_spammed;

    /**
     * @var boolean True if the client IP is whitelisted, false otherwise.
     */
    protected $_white_ip;

    public function browse() {
        $session = JFactory::getSession();
        $session->set('refreshed', true, 'comments');
        $data = array('row' => $session->get('row', '', 'comments'), 'table' => $session->get('table', '', 'comments'));
        $view = $this->getView('Comments', 'Html', 'CommentsView');
        $view->onDisplay($data);
        $html = $view->loadTemplate();
        // todo: revisit this (below) and clean up.  perhaps just echo and return to FOFDispatcher, provided POST:format=raw?
        die($html);
        // returning $html sends us into a redirect loop, and most other conditions will wrap $html in the template
    }

    /**
     * Client IP getter.
     * @return The IP addresses from the client.
     */
    public function getClientIp() {
        if (!$this->_client_ip) {
            $ip = '';
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                $matches = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
                if (array_key_exists(0, $matches)) {
                    $ip = (filter_var(end($matches), FILTER_VALIDATE_IP));
                }
            } else if (array_key_exists("REMOTE_ADDR", $_SERVER)) {
                $ip = $_SERVER["REMOTE_ADDR"];
            }

            $this->_client_ip = $ip;
        }

        return $this->_client_ip;
    }

    /**
     * Email domain getter.
     *
     * @param string The email address.
     *
     * @return string The domain from the email address.
     */
    public function getEmailDomain($email) {
        $domain = strstr((string)$email, '@');
        if ($domain === false) {
            return '';
        }

        return substr($domain, 1);
    }

    /**
     * DNSBL Test.
     *
     * @param
     *               string The lookup string.
     *
     * @return mixed False if test succeeds (no block), lookup result otherwise.
     */
    private function _isDnsblBlocked($lookup) {
        if ($lookup[strlen($lookup) - 1] != '.') {
            $lookup .= '.';
        }
        $result = gethostbyname($lookup);
        if ($result == $lookup) {
            return false;
        } else {
            return $result;
        }
    }

    /**
     * Notify users on comment submission
     */
    public function notify($comment) {
        // Local scope!
        $commentEmail    = $comment->email;
        $commentId       = $comment->id;
        $commentRow      = $comment->row;
        $commentTable    = $comment->table;
        $commentUsername = $comment->username;

        // Get a list of the subscribers
        $subscribersModel = FOFModel::getAnInstance('Subscription', 'CommentsModel');
        $subscribersModel->setState('email', '');
        $subscribersModel->setState('row', $commentRow);
        $subscribersModel->setState('table', $commentTable);
        $subscribers = $subscribersModel->getList();

        if (count($subscribers)) {
            $user      = JFactory::getUser();
            $app       = JFactory::getApplication();
            $submitter = $user->guest ? $commentUsername : $user->username;

            // get blacklisted emails
            $blackemailsModel = FOFModel::getAnInstance('blackemails', 'CommentsModel');
            $blackemailsModel->setState('email', '');
            $blackemailsList = $blackemailsModel->getList();
            $blackemails     = array();
            foreach ($blackemailsList as $item) {
                if (isset($item->email)) {
                    $blackemails[] = $item->email;
                }
            }
            unset($blackemailsModel, $blackemailsList);

            // get blacklisted hosts
            $blackhostsModel = FOFModel::getAnInstance('blackhosts', 'CommentsModel');
            $blackhostsModel->setState('name', '');
            $blackhostsList = $blackhostsModel->getList();
            $blackhosts     = array();
            foreach ($blackhostsList as $item) {
                if (isset($item->name)) {
                    $blackhosts[] = $item->name;
                }
            }
            unset($blackhostsModel, $blackhostsList);

            jimport('joomla.mail.helper');

            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select($db->qn('title'));

            // this is horrible, special case scenarios
            if ($commentTable == 'content') {
                $link = 'index.php?option=com_content&view=article&id=' . $commentRow;
                $id   = 'id';
            } else {
                $identify     = explode('_', $commentTable);
                $link         = 'index.php?option=com_' . $identify[0] . '&view=' . FOFInflector::singularize($identify[1]) . '&id=' . $commentRow;
                $commentTable = FOFInflector::pluralize($commentTable);
                $id           = FOFInflector::singularize($commentTable) . '_id';
            }

            $query->from($db->qn('#__' . $commentTable))
                  ->where($db->qn($id) . ' = ' . $db->q($commentRow));

            $db->setQuery($query);
            $tuple = $db->loadResult();

            foreach ($subscribers as $subscriber) {
                if ($user->guest) {
                    if ($subscriber->email == $commentEmail) {
                        continue;
                    }
                } else {
                    if (!$user->guest && $subscriber->user_id == $user->id) {
                        continue;
                    }
                }

                // check we are not a spammer
                $host = end(explode('@', $subscriber->email));

                if (in_array($subscriber->email, $blackemails)) {
                    continue;
                }
                if (in_array($host, $blackhosts)) {
                    continue;
                }

                $subject  = sprintf(JText::_('COM_COMMENTS_COMMENT_ADDED_TO'), $tuple);
                $bodytext = sprintf(JText::_('COM_COMMENTS_NOTIFICATION_NEW_COMMENT_ADDED'), $subscriber->username, $submitter, $tuple, $app->getCfg('sitename')) . "\n";
                $bodytext .= JText::_('COM_COMMENTS_YOU_CAN_SEE_THE_COMMENT_HERE') . ' ' . rtrim(JURI::base(), '/') . JRoute::_($link) . '#c' . $commentId;
                $bodytext .= "\n" . "\n" . JText::_('COM_COMMENTS_TO_UNSUBSCRIBE_VISIT') . ' ' . rtrim(JURI::base(), '/') . JRoute::_('index.php?option=com_comments&view=unsubscribe&uuid=') . $subscriber->uuid;

                $mail = new JMail();
                $mail->sendMail($app->getCfg('mailfrom'), $app->getCfg('fromname'), $subscriber->email, $subject, $bodytext);
            }
        }
    }

    /**
     * Execute something after Save has run.
     * @return  boolean  True to allow normal return, false to cause a 403 error
     */
    protected function onAfterSave() {
        // Notify the subscribers if the comment is not spam
        if (!$this->_spammed) {
            $comment = FOFTable::getAnInstance('Comment', 'CommentsTable');
            $comment->load($this->input->get('id', '', 'int'));
            $this->notify($comment);
        }

        // Unsubscribe or Subscribe a user where needed
        $row  = FOFTable::getAnInstance('Subscription', 'CommentsTable');
        $user = JFactory::getUser();

        if ($user->guest) {
            $row->email = $this->input->get('email', '', 'string');
        } else {
            $row->user_id = $user->id;
        }

        $row->table   = $this->input->get('table', '');
        $row->row     = $this->input->get('row', '');
        $subscription = $this->input->get('subscribe', false);

        if ($row->load() && isset($subscription) && !$subscription) { // $subscription could be 0 or false
            $row->delete();
        } else {
            if ($subscription && !$this->_spammed) {
                $row->store();
            }
        }

        //only run this if we have failed spam or a mollom id
        $mollom_id = $this->input->get('mollom_id', null, 'string');
        if ($mollom_id || $this->_failed_checks) {
            $spam_report = FOFTable::getAnInstance('spam_reports', 'CommentsTable');

            if ($mollom_id) {
                if (!is_object($this->_mollom)) {
                    require_once JPATH_ADMINISTRATOR . '/components/com_comments/helpers/mollom.php';
                    $this->_mollom = new Mollom();
                }
                $mol                    = $this->_mollom->checkContent($mollom_id);
                $spam_report->mollom_id = $mollom_id;
                $spam_report->quality   = $mol['spam'];

                if ($mol['spam'] == 'spam') {
                    $comment->enabled = 0;
                    $comment->store();
                }
            } elseif ($this->_failed_checks) {
                $spam_report->quality = JText::_('Spam on ' . $this->_failed_checks[0]);
            }

            $spam_report->comment_id = $comment->comments_comment_id;
            $spam_report->store();
        }

        return true;
    }

    protected function onBeforeSave() {
        $session = JFactory::getSession();
        $user    = JFactory::getUser();

        // set the redirect
        $referrer = $session->get('referrer', '', 'comments');
        $this->setRedirect($referrer . '#com-comments-comments');

        // Admins can do what they please
        if ($user->authorise(null)) {
            return true;
        }

        // store our comment in the session
        $session->set('comment', $this->input->get('comment', ''), 'comments');
        $session->set('username', $this->input->get('username', ''), 'comments');
        $session->set('email', $this->input->get('email', ''), 'comments');
        $session->set('subscribe', $this->input->get('subscribe', ''), 'comments');

        // run our spam checks
        $this->spammed();

        $failed = $this->_failed_checks;

        // if data validation failed then set the failed elements the session
        if (in_array('data', $failed)) {
            foreach ($this->_invalid_data as $invalid) {
                $session->set('invalid_' . $invalid, true, 'comments');
            }

            $this->browse();
        }

        // if we failed the flooding validation let them know how long they have to wait
        if (in_array('flooding', $failed)) {
            $this->input->set('timelimit', $this->_flood_limit);

            $this->browse();
        }

        // if we have failed any of our internal spam checks or we are blacklisted
        // then moderate the comment and tell the user it has to be approved
        if (in_array('honeypot', $failed) ||
            in_array('reverseHoneypot', $failed) ||
            in_array('timestamp', $failed) ||
            in_array('blacklist', $failed) ||
            in_array('blackHost', $failed) ||
            in_array('blackEmail', $failed) ||
            in_array('spamhaus', $failed) ||
            in_array('honeypotProject', $failed) ||
            in_array('botscout', $failed)
        ) {
            $session->set('referrer', $referrer, 'comments');
            $this->input->set('enabled', 0);

            $this->browse();
        }

        // if we failed the mollom analysis require a captcha
        if (in_array('mollomAnalysis', $failed)) {
            $this->input->set('require_captcha', true);
            $this->input->set('mollom_id', $this->_mollom_id);

            $this->browse();
        }

        // if we failed to fill in the captcha correctly
        if (in_array('captcha', $failed)) {
            $this->input->set('captcha_failed', true);
            $this->input->set('require_captcha', true);
            $this->input->set('mollom_id', $this->_mollom_id);

            $this->browse();
        }

        if ($this->_mollom_id) {
            $this->input->set('mollom_id', $this->_mollom_id);
        }

        $session->set('referrer', $referrer, 'comments');

        return true; // override FOF's ACL check, by not calling the parent's method
    }

    public function publish() {
        $this->storeData();

        return parent::publish();
    }

    public function report() {
        $reportModel = FOFModel::getAnInstance('Reports', 'CommentsModel');
        $reportModel->save(array('comment_id' => $this->input->get('comments_comment_id', 0, 'int')));
    }

    /**
     * Returns a reversed IP address
     *
     * @param string IP address.
     *
     * @return string Reversed IP address.
     */
    public function reverseIp($ip) {
        return implode('.', array_reverse(explode('.', $ip)));
    }

    public function save() {
        $this->storeData();

        return parent::save();
    }

    public function spam() {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        return $this->setstate(2);
    }

    /**
     * Performs a spam check.
     *
     * @param array An optional configuration array.
     *
     * @throws Exception If a requested spam check is not implemented.
     * @return boolean True if spam is suspected, false otherwise.
     */
    public function spammed() {
        if (!isset($this->_spammed)) {
            // check for a whitelist entry
            if ($this->input->get('whitelist', true) && $this->whiteIp()) {
                // Client is whitelisted.
                $this->_spammed = false;

                return $this->_spammed;
            }

            // Initialize the spammed status as false.
            $this->_spammed = false;

            // loop through our checks and see if we have passed them
            foreach ($this->_checks as $check) {
                $method = '_' . $check . 'Check';
                if (!method_exists($this, $method)) {
                    throw new Exception('Unknown spam check.', 500);
                }
                if (!$this->$method()) {
                    $this->_failed_checks[] = $check;
                }
            }

            //if we failed a check then we are spammers
            if (count($this->_failed_checks)) {
                $this->_spammed = true;
            }
        }

        return (bool)$this->_spammed;
    }

    private function storeData() {
        //put this data into the session, cuz the input gets erased by the time browse() is called
        $session = JFactory::getSession();
        $session->set('table', $this->input->get('table', 'foobar', 'string'), 'comments');
        $session->set('row', $this->input->get('row', 0, 'int'), 'comments');
    }

    public function subscribe() {
        $input = new FOFInput();
        $row   = $input->get('row', 0, 'int');
        $table = $input->get('table', 'foobar', 'string');
        $user  = JFactory::getUser();

        $subscriptionData  = array('user_id' => $user->id, 'row' => $row, 'table' => $table, 'email' => $user->email);
        $subscriptionModel = FOFModel::getAnInstance('Subscriptions', 'CommentsModel');

        $subscriptionModel->save($subscriptionData);
    }

    public function unpublish() {
        $this->storeData();

        return parent::unpublish();
    }

    public function unsubscribe() {
        $input             = new FOFInput();
        $subscriptionModel = FOFModel::getAnInstance('Subscriptions', 'CommentsModel');
        $user              = JFactory::getUser();

        $subscriptionModel->setState('user_id', $user->id);
        $subscriptionModel->setState('row', $input->get('row', 0, 'int'));
        $subscriptionModel->setState('table', $input->get('table', 'foobar', 'string'));
        $subscriptionModel->setState('email', $user->email);

        $subscriptionModel->getFirstItem()->delete();
    }

    /**
     * Tells if the current client IP address is whitelisted (internal).
     * @return boolean True if whitelisted, false otherwise.
     */
    public function whiteIp() {
        if (!isset($this->_white_ip)) {
            $this->_white_ip = false;
            $ip              = $this->getClientIp();

            // Get a list of the whitelisted IPs
            $whitelistModel = FOFModel::getAnInstance('Whiteips', 'CommentsModel');
            $whitelistModel->setState('ip', $ip);
            $whitelist = $whitelistModel->getItem();
            if ($whitelist->ip == $ip) {
                $this->_white_ip = true;
            }
        }

        return (bool)$this->_white_ip;
    }

    /**
     * flooding check.
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _floodingCheck() {
        $floodwall = JComponentHelper::getParams('com_comments')->get('flood_prevention');
        $user      = JFactory::getUser();
        $column    = $user->guest ? 'ip' : 'author';
        $value     = $user->guest ? $this->getClientIp() : $user->id;

        // Get the last comment for this item from the database
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
              ->from($db->qn('#__comments_comments'))
              ->where($db->qn('row') . ' = ' . $db->q($this->input->get('row')))
              ->where($db->qn('table') . ' = ' . $db->q($this->input->get('table')))
              ->where($db->qn($column) . ' LIKE ' . $db->q($value))
              ->order($db->qn('created_on') . ' DESC');
        $db->setQuery($query, 0, 1);
        $item = $db->loadObject();

        if (isset($item)) {
            // Work out the difference between their last comment and the current unix time
            $now  = time();
            $then = JFactory::getDate($item->created_on)->toUnix();
            $diff = $now - $then;

            // If the difference is less than the one we have set in the settings then don't allow them to post
            if ($diff < $floodwall) {
                $this->_flood_limit = $floodwall - $diff;

                return false;
            }
        }

        return true;
    }

    /**
     * Honey pot check.
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _honeypotCheck() {
        // Check if the predefined honeypot field is not empty, i.e. a bot filled it.
        $honeypot = $this->input->get($this->_honeypots['honeypot'], null);
        if (!empty($honeypot)) {
            return false;
        }

        return true;
    }

    /**
     * Validate Data check
     */
    protected function _dataCheck() {
        $guest_checks  = array('comment', 'email', 'username');
        $normal_checks = array('comment');
        $passed        = true;

        $checks = (JFactory::getUser()->guest) ? $guest_checks : $normal_checks;

        foreach ($checks as $field) {
            $data = $this->input->get($field, null);
            if ($field == 'email') {
                if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
                    $this->_invalid_data[] = $field;
                }
            } else {
                if (!$data) {
                    $this->_invalid_data[] = $field;
                }
            }
        }

        return (empty($this->_invalid_data)) ? true : false;
    }

    /**
     * Reverse honey pot check.
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _reverseHoneypotCheck() {
        // Check if the predefined reverse honeypot field is not empty, i.e. a bot not running JS left it filled.
        $reverseHoneypot = $this->input->get($this->_honeypots['reverseHoneypot'], null);
        if (!empty($reverseHoneypot)) {
            return false;
        }

        return true;
    }

    /**
     * Timestamp check.
     * The current time is compared with the time at which the form was
     * rendered. If only a few seconds have passed, the form is considered as
     * filled by a spam bot.
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _timestampCheck() {
        $timestamp = $this->input->get('timestamp');

        // Verify the provided timestamp
        $sha1 = sha1($timestamp . $this->_secret);
        if ($this->input->get('timestamp_secret') != $sha1) {
            // Wrong hash, spammed.
            return false;
        }

        // Compare timestamps. Anything less than 5 seconds is considered as spam.
        if (time() - $timestamp < 5) {
            return false;
        }

        return true;
    }

    /**
     * Performs a check against the black hosts table.
     * If the provided email's domain is found on it, the check fails.
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _blackHostCheck() {
        $email  = $this->input->get('email', null) ? $this->input->get('email', null, 'string') : JFactory::getUser()->email;
        $domain = $this->getEmailDomain($email);

        // Get a list of the blacklisted hosts
        $blackhostModel = FOFModel::getAnInstance('Blackhosts', 'CommentsModel');
        $blackhostModel->setState('name', $domain);
        $blackhost = $blackhostModel->getItem();
        if ($blackhost->name == $domain) {
            return false;
        }

        return true;
    }

    /**
     * Performs a check against the black emails table
     * If the provided email is found on it, the check fails.
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _blackEmailCheck() {
        $email = $this->input->get('email', null) ? $this->input->get('email') : JFactory::getUser()->email;

        // Get a list of the blacklisted emails
        $blackemailModel = FOFModel::getAnInstance('Blackemails', 'CommentsModel');
        $blackemailModel->setState('email', $email);
        $blackemail = $blackemailModel->getItem();
        if ($blackemail->email == $email) {
            return false;
        }

        return true;
    }

    /**
     * Performs a check against the the internal blacklist table.
     * If the current client IP matches a record in the table, the check fails.
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _blacklistCheck() {
        $ip = $this->getClientIp();

        // Get a list of the blacklisted IPs
        $blacklistModel = FOFModel::getAnInstance('Blackips', 'CommentsModel');
        $blacklistModel->setState('ip', $ip);
        $blacklist = $blacklistModel->getItem();
        if ($blacklist->ip == $ip) {
            return false;
        }

        return true;
    }

    /**
     * Performs an IP check against The Spamhaus Project
     * (http://www.spamhaus.org/).
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _spamhausCheck() {
        $reverse_ip = $this->reverseIp($this->getClientIp());

        // SBL check.
        $result = $this->_isDnsblBlocked($reverse_ip . '.sbl.spamhaus.org');
        if ($result !== false) {
            // Positive result.
            $result = explode('.', $result);
            if ($result[0] == '127') {
                return false;
            }
        }
        // XBL check.
        $result = $this->_isDnsblBlocked($reverse_ip . '.xbl.spamhaus.org');
        if ($result !== false) {
            // Positive result.
            $result = explode('.', $result);
            if ($result[0] == '127') {
                return false;
            }
        }

        return true;
    }

    /**
     * Performs an IP check against Project Honey Pot
     * (http://www.projecthoneypot.org/).
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _honeypotProjectCheck() {
        $api_key = JComponentHelper::getParams('com_comments')->get('honeypot_key');

        //if we dont have a api key, then we passed
        if (!$api_key) {
            return true;
        }

        // SBL check.
        $lookup = $api_key . '.' . $this->reverseIp($this->getClientIp()) . '.dnsbl.httpbl.org.';
        $result = gethostbyname($lookup);

        if ($result != $lookup) {
            $result = explode('.', $result);
            if ($result[0] == '127') {
                if ($result[3] >= 2) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Performs an IP check against Bot Scout (http://www.botscout.com/).
     *
     * @param
     *               array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _botScoutCheck() {
        $api_key = JComponentHelper::getParams('com_comments')->get('botscout_key');

        //if we dont have a api key, then we passed
        if (!$api_key) {
            return true;
        }

        $email = $this->input->get('email', null) ? $this->input->get('email', null, 'string') : JFactory::getUser()->email;
        $ip    = $this->getClientIp();

        $url = 'http://botscout.com/test/?multi&mail=' . urlencode($email) . '&ip=' . urlencode($ip) . '&key=' . $api_key;

        $data = $this->_getUrl($url);

        if ($data === false) {
            // Couldn't perform check, assume passed.
            return true;
        }

        // Cleanup string.
        $data = str_replace(array("\n", "\r", "\t", ' '), '', $data);

        $data = explode('|', $data);

        // We are only looking for a Y result
        if ($data[0] == 'Y') {
            // Positive result.
            return false;
        }

        return true;
    }

    /**
     * Ask mollom to analyse the content and return a ham/spam score
     *
     * @param array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _mollomAnalysisCheck() {
        // only run mollom if we haven't failed any other checks
        if (empty($this->_failed_checks)) {
            $user     = JFactory::getUser();
            $username = $user->guest ? $this->input->get('username', null, 'string') : $user->name;
            $email    = $user->guest ? $this->input->get('email', null, 'string') : $user->email;

            if (!$mollom_id = $this->input->get('mollom_id', null, 'string')) {
                require_once JPATH_ADMINISTRATOR . '/components/com_comments/helpers/mollom.php';
                $this->_mollom    = new Mollom();
                $info             = $this->_mollom->checkContent($mollom_id, null, $this->input->get('comment'), $username, null, $email);
                $this->_mollom_id = $info['session_id'];

                // If we are unsure or spam then require a captcha
                if ($info['spam'] == 'unsure' || $info['spam'] == 'spam') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check with mollom that the provided captcha is correct
     *
     * @param array An optional configuration array.
     *
     * @return boolean True if check is passed, false otherwise.
     */
    protected function _captchaCheck() {
        // only run this, if we have a mollom_id
        if ($mollom_id = $this->input->get('mollom_id', null, 'string')) {
            if (!is_object($this->_mollom)) {
                require_once JPATH_ADMINISTRATOR . '/components/com_comments/helpers/mollom.php';
                $this->_mollom = new Mollom();
            }

            if (!$this->_mollom->checkCaptcha($mollom_id, $this->input->get('captcha_value', null, 'string'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Issues a GET request using curl.
     *
     * @param
     *               string The request url.
     *
     * @throws Exception If curl isn't found.
     * @return mixed The response on success, false on failure.
     */
    private function _getUrl($url) {
        if (!function_exists('curl_init')) {
            throw new Exception('Curl not available.', 500);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }

}
