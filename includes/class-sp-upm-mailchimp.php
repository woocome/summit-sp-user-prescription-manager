<?php
use WPFormsMailchimp\Provider\Api;

/**
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */

class Sp_Upm_Mailchimp
{

    private static $mailchimp_api;

    public $list_id;

    public function __construct(string $list_id)
    {
        $this->list_id = $list_id;
        $this->init_mailchimp_api();
    }

    private function init_mailchimp_api() {
        $providers = wpforms_get_providers_options();

        if ($mailchimp = $providers['mailchimpv3']) {
            $mailchimpv3 = reset($mailchimp);
            $mailChimpApi = new Api($mailchimpv3['api']);

            self::$mailchimp_api = $mailChimpApi;
        }
    }

    public function generate_hash_email($email) {
        if (! $email || ! is_email($email)) return;
        return self::$mailchimp_api::subscriberHash($email);
    }

    public function update_mailchimp_tags(array $tags, $email) {
        self::$mailchimp_api->update_member_tags($this->list_id, $email, ['tags' => $tags]);
    }

    public function get_existing_mailchimp_tags($email) {
        $hash = $this->generate_hash_email($email);

        $response = self::$mailchimp_api->get( "lists/{$this->list_id}/members/{$hash}/tags");

        return $response['tags'] ?? false;
    }
}

function sp_upm_mailchimp($list_id) {
    return new Sp_Upm_Mailchimp($list_id);
}