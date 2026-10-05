<?php
/**
 * Settings page save handling: CSRF / capability checks and credential defaults.
 *
 * @package WP_PayPal_Donations
 */

namespace WPPayPalDonations\Tests\Unit;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

class Settings_Page_Test extends TestCase {

    /** @var \WP_PayPal_Donations */
    private $plugin;

    protected function setUp(): void {
        parent::setUp();
        $this->plugin = new \WP_PayPal_Donations();
        Monkey\Functions\when('sanitize_textarea_field')->returnArg();
    }

    private function maliciousPost() {
        return array(
            'submit' => 'Save Changes',
            'wpd_paypal_email' => 'attacker@evil.com',
            'wpd_currency' => 'USD',
            'wpd_default_amount' => '25',
            'wpd_success_message' => 'Thanks',
            'wpd_paypal_client_id' => 'id',
            'wpd_paypal_client_secret' => 'secret',
            'wpd_payment_method' => 'standard',
        );
    }

    private function expectDie() {
        Monkey\Functions\expect('wp_die')->once()->andReturnUsing(function () {
            throw new \RuntimeException('wp_die');
        });
        $this->expectException(\RuntimeException::class);
    }

    public function test_settings_are_not_saved_without_a_valid_nonce() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(false);
        Monkey\Functions\expect('update_option')->never();
        $this->expectDie();

        $this->plugin->save_settings($this->maliciousPost());
    }

    public function test_settings_are_not_saved_when_nonce_is_missing() {
        $checked = array();
        Monkey\Functions\when('wp_verify_nonce')->alias(function ($nonce, $action) use (&$checked) {
            $checked[] = array($nonce, $action);
            return false;
        });
        Monkey\Functions\expect('update_option')->never();
        Monkey\Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die');
        });

        try {
            $this->plugin->save_settings($this->maliciousPost());
            $this->fail('save_settings() must die without a nonce');
        } catch (\RuntimeException $e) {
            $this->assertSame(array(array('', 'wpd_settings_nonce')), $checked);
        }
    }

    public function test_settings_are_not_saved_without_manage_options_capability() {
        Monkey\Functions\when('current_user_can')->justReturn(false);
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\expect('update_option')->never();
        $this->expectDie();

        $this->plugin->save_settings($this->maliciousPost() + array('_wpnonce' => 'n'));
    }

    public function test_settings_are_saved_with_valid_nonce_and_capability() {
        $checked = array();
        Monkey\Functions\when('wp_verify_nonce')->alias(function ($nonce, $action) use (&$checked) {
            $checked[] = array($nonce, $action);
            return $nonce === 'good' && $action === 'wpd_settings_nonce';
        });
        $saved = array();
        Monkey\Functions\when('update_option')->alias(function ($key, $value) use (&$saved) {
            $saved[$key] = $value;
            return true;
        });

        $post = $this->maliciousPost() + array('_wpnonce' => 'good');
        $post['wpd_paypal_email'] = 'finance@charity.org';
        $post['wpd_payment_method'] = 'not-a-real-method';
        $post['wpd_currency'] = 'XXX';

        $this->assertTrue($this->plugin->save_settings($post));
        $this->assertSame(array(array('good', 'wpd_settings_nonce')), $checked);
        $this->assertSame('finance@charity.org', $saved['wpd_paypal_email']);
        $this->assertSame('standard', $saved['wpd_payment_method'], 'unknown payment method must fall back to standard');
        $this->assertSame('USD', $saved['wpd_currency'], 'unsupported currency must fall back to USD');
        $this->assertSame(0, $saved['wpd_sandbox_mode']);
    }

    public function test_settings_page_ships_no_hardcoded_paypal_credentials() {
        foreach (array('_e', 'esc_html_e', 'esc_attr_e') as $fn) {
            Monkey\Functions\when($fn)->echoArg();
        }
        foreach (array('selected', 'checked', 'wp_nonce_field', 'submit_button') as $fn) {
            Monkey\Functions\when($fn)->justReturn('');
        }
        Monkey\Functions\when('esc_textarea')->returnArg();
        // Fresh install: every option falls back to its default.
        Monkey\Functions\when('get_option')->alias(function ($option, $default = false) {
            return $default;
        });

        ob_start();
        $this->plugin->settings_page();
        $html = ob_get_clean();

        $this->assertStringNotContainsString('Af_8jpwQ0WWKhd4yAdu6QlLl1UO', $html);
        $this->assertStringNotContainsString('ECQ61mmOEG7bOdnPnjFOtxuD3q1OhJIcx7yyu', $html);
        $this->assertStringContainsString('name="wpd_paypal_client_secret" value=""', $html);
    }
}
