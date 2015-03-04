<?php
class ContactException extends Exception {}

class contact extends Controller
{
    /**
     * Send mail with data from contact form received with $_POST.
     */
    public function submit()
    {
        try {
            // Check if an email address is given and is valid.
            if (empty($_POST['emailaddress'])
              || !filter_var($_POST['emailaddress'], FILTER_VALIDATE_EMAIL)) {
                throw new ContactException('Please enter a valid e-mail address.');
            }

            // Check if a message is given.
            if (empty($_POST['message'])) {
                throw new ContactException('You need to enter a message.');
            }

            // Check message is not to long.
            if (strlen($_POST['message']) > 10000) {
                throw new ContactException('Your message is too long (longer than 10 000 characters).');
            }

            // If honeypod is triggered silently fail.
            if (!empty($_POST['email'])) {
                throw new ContactException('');
            }

            // Build message include name in message if given.
            $message = !empty($_POST['name']) ? ('Name:' . $_POST['name'] . "\r\n\r\n") : '';
            $message .= $_POST['message'];

            // Send mail.
            $email = preg_replace('/[\\r\\n]/', '', $_POST['emailaddress']);
            $headers = 'FROM: ' . $email . "\r\n";
            $headers .= 'CC: ' . $email;
            $status = mail(CONTACT_MAIL, 'contact form', $message, $headers);
            if (!$status) {
                throw new ContactException('Sending mail failed. I\'m sorry.'
                . ' You can send the mail manual to ' . CONTACT_MAIL . '.');
            }
            $this->view->success = 'Thank you. Your message has been send.';
        } catch (ContactException $e) {
            $this->view->error = $e->getMessage();
            $this->view->email = !empty($_POST['emailaddress']) ? $_POST['emailaddress'] : '';
            $this->view->message = !empty($_POST['message']) ? $_POST['message'] : '';
            $this->view->name = !empty($_POST['name']) ? $_POST['name'] : '';
        }

        $this->render();
    }
}
