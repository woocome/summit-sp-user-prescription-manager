<?php

class Sp_Upm_Email_Sender {
    private $from;
    private $replyTo;
    private $subject;
    private $headers;
    private $content;

    public function __construct($treatment_id) {

        if ($treatment_id) {
            $this->from = get_term_meta($treatment_id, 'email_from', true);
            $this->replyTo = get_term_meta($treatment_id, 'email_reply_to', true);
            $this->subject = get_term_meta($treatment_id, 'email_subject', true);
        }

        $this->headers = array();
        $this->setDefaultHeaders();
    }

    public function setFrom($from) {
        $this->from = $from;
    }

    public function setReplyTo($replyTo) {
        $this->replyTo = $replyTo;
    }

    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function setContent($message) {
        ob_start();

        sp_upm_get_template_part('/emails/content', 'email', ['message' => $message]);

        $message = ob_get_clean();

        $this->content = $message;
    }

    private function setDefaultHeaders() {
        $this->headers = array();
        $this->headers[] = sprintf('From: %1$s <%2$s>', "SummitPharma", !empty($this->from) ? $this->from : 'menshealth@summitpharma.com.au');
        $this->headers[] = sprintf('Reply-To: %1$s <%2$s>', "SummitPharma", !empty($this->replyTo) ? $this->replyTo : 'menshealth@summitpharma.com.au');
        $this->headers[] = 'Content-Type: text/html; charset=UTF-8';
    }

    public function send($user) {
        $this->setDefaultHeaders();

        return wp_mail($user->user_email, $this->subject, $this->content, $this->headers);
    }
}