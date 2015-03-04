<?php
/**
 * @author Sebastian Gellweiler
 * @file
 *  Minimal user management for this project.
 *  Authentication is done via google.
 *  Email addresses will only be stored in hashed form
 *  to make life for spammers harder.
 */

class User
{
    /**
     * @var Integer
     *  The id of the user.
     */
    public $uid;

    /**
     * @var GoogleAccount
     *  The Google account used to identify and authenticate the user.
     */
    protected $login;

    /**
     * @var String
     *  The hash of the user email used to identify the user in DB querys.
     */
    protected $email_hash;

    /**
     * @var GoogleAccount $login
     *  A Google account will be used to identify and authenticate the user.
     */
    public function __construct(GoogleAccount $login)
    {
        $this->login = $login;
        $this->email_hash = self::hashEmail($this->login->email);
        $this->uid = $this->queryUid();
    }

    /**
     * Gets the user id from the DB.
     * If no user entry for the user exists create one and return the new uid.
     *
     * @return Integer
     *  The user id.
     */
    protected function queryUid()
    {
        $sql =
<<<'EOF'
SELECT id FROM users
WHERE emailhash = :hash
EOF;

        $db = DB::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':hash', $this->email_hash, SQLITE3_TEXT);
        $result = $sth->execute()->fetchArray(SQLITE3_ASSOC);

        // If no user with this email exists in the DB.
        // Create it.
        if (empty($result)) {
            $this->newUserEntry();
            return $this->queryUid();
        }

        return $result['id'];
    }

    /**
     * Creates a new user entry in the DB.
     */
    protected function newUserEntry()
    {
        $sql =
<<<'EOF'
INSERT INTO users(emailhash)
VALUES(:hash)
EOF;
        $db = DB::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':hash', $this->email_hash, SQLITE3_TEXT);
        $sth->execute();
    }

    /**
     * Hash given email for storing it in the DB.
     * Unfortunately we can't use a dynamic hash as that would prevent
     * looking up the hash in the DB.
     *
     * @param String $email
     *  The email to hash.
     *
     * @return String
     *  Hash of given email address.
     */
    protected static function hashEmail($email)
    {
        return crypt($email . STATIC_EMAIL_SALT, '$6$rounds=10000$$');
    }
}
