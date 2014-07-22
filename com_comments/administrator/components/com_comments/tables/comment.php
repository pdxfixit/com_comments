<?php

defined('_JEXEC') or die;

class CommentsTableComment extends FOFTable {

    function __construct($table, $key, &$db) {

        // this is only needed for the (single) Form data; under browse conditions it'll just slow us down
        $input = new FOFInput();
        $view  = $input->get('view', 'comments');
        if ($view !== 'comments') {
            $query = $db->getQuery(true);
            $query->select('quality')->leftJoin('#__comments_spam_reports AS s ON ( s.comment_id = ' . $table . '.' . $key . ' ) ');
            $this->setQueryJoin($query);
        }

        return parent::__construct($table, $key, $db);
    }

    protected function onAfterStore() {
        $this->getGravatar();

        return parent::onAfterStore();
    }

    protected function onBeforeStore($updateNulls) {
        // html tidy the comment data.
        $config = array(
            'clean'                       => true,
            'drop-proprietary-attributes' => true,
            'output-html'                 => true,
            'show-body-only'              => true,
            'bare'                        => true,
            'wrap'                        => 0,
            'word-2000'                   => true,
        );

        $this->comment = $this->getTidy($this->comment, $config);
        $this->ip      = $this->getIp();

        return parent::onBeforeStore($updateNulls);
    }

    /**
     * Create the user in the comments people table and check with gravatar for a avatar
     */
    private function getGravatar() {
        $user = JFactory::getUser();

        // If avatars are turned on, check with gravatar to see if we have an avatar
        if (!$user->guest && JComponentHelper::getParams('com_comments')->get('gravatar')) {
            // Get a empty person row, assign the user id and attempt to load it
            $person                     = FOFTable::getAnInstance('Person', 'CommentsTable');
            $person->comments_person_id = $user->id;
            $person->load();

            require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gravatar.php';
            $gravatar = new CommentsHelperGravatar($user->email, 404);
            $avatar   = $gravatar->getSrc();

            // Prepare curl
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/curl.php';
            $curl = new CommentsHelperCurl();
            $opt  = array(
                CURLOPT_RETURNTRANSFER => true
            );
            $curl->addSession($avatar, $opt);

            $image = $curl->exec();

            // If gravatar didnt return a 404, then lets copy it to the filesystem and set it on the person row
            if ($image != '404 Not Found') {
                $dest = '/media/com_comments/images/avatars/' . $user->id . '/gravatar.png';
                JFile::write(JPATH_ROOT . $dest, $image);
                $person->avatar = $dest;
            }

            // Finally save the row
            $this->_db->insertObject('#__comments_people', $person, 'comments_person_id', false);
        }
    }

    /**
     * Set the ip address of the user before saving the comment
     */
    public function getIp() {
        $ip = '';
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $matches = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            if (array_key_exists(0, $matches)) {
                $ip = (filter_var(end($matches), FILTER_VALIDATE_IP));
            }
        } else if (array_key_exists("REMOTE_ADDR", $_SERVER)) {
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        return $ip;
    }

    /**
     * Gets a Tidy object
     *
     * @param string    The data to be parsed.
     */
    public function getTidy($string, $config = array(), $encoding = 'utf8') {
        if (class_exists('Tidy')) {
            $this->_tidy = new Tidy();
            $this->_tidy->parseString($string, $config, $encoding);
        } else {
            $this->_tidy = $string;
        }

        return $this->_tidy;
    }

}
