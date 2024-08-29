<?php

class Sp_Upm_Email_Sender {
    private $from;
    private $replyTo;
    private $subject;
    private $headers;
    private $content;

    public function __construct($treatment_id) {
        $this->from = get_term_meta($treatment_id, 'email_from', true);
        $this->replyTo = get_term_meta($treatment_id, 'email_reply_to', true);
        $this->subject = get_term_meta($treatment_id, 'email_subject', true);

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

    public function setContent($message, $style = null) {
        ob_start();

        sp_upm_get_template_part('/emails/content', 'email', ['message' => $message, 'style' => $style]);

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

    private function getEmailApprovedPrescriptionHtml($data) {
        // Generate the email content based on the data provided
        // This is a placeholder. Replace it with your actual implementation.
        return "
            <html>
            <head></head>
            <body>
                <h1>Approved Prescription</h1>
                <p>Dear {$data['customer_name']},</p>
                <p>Your prescription for {$data['medication_name']} has been approved.</p>
                <p>Concern: {$data['concern']}</p>
                <p>Prescriber: {$data['prescriber']}</p>
                <p>Is NRT: " . ($data['is_nrt'] ? 'Yes' : 'No') . "</p>
                <p>Best regards,</p>
                <p>SummitPharma Team</p>
            </body>
            </html>
        ";
    }

    /**
     * Get email headers.
     *
     * @return string
     */
    public function get_headers() {
        $header = 'Content-Type: Content-Type: text/html; charset=UTF-8' . "\r\n";
        $header .= 'Reply-to: SummitPharma' . ' <' . 'info@summitpharma.com.ay' . ">\r\n";

        return $header;
    }
}