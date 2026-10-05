<?php
/**
 * PayPal REST return handling: the donation row must match the captured order.
 *
 * @package WP_PayPal_Donations
 */

namespace WPPayPalDonations\Tests\Unit;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

class REST_Return_Test extends TestCase {

    /** @var \WP_PayPal_Donations */
    private $plugin;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $api;

    protected function setUp(): void {
        parent::setUp();
        $this->plugin = new \WP_PayPal_Donations();
        $this->api = $this->createMock(\WPD_PayPal_REST_API::class);

        $prop = new \ReflectionProperty(\WP_PayPal_Donations::class, 'paypal_api');
        $prop->setAccessible(true);
        $prop->setValue($this->plugin, $this->api);
    }

    private function captureResult($value, $currency) {
        return array(
            'success' => true,
            'status' => 'completed',
            'transaction_id' => '5O190127TN364715T',
            'data' => array(
                'id' => '5O190127TN364715T',
                'status' => 'COMPLETED',
                'purchase_units' => array(array(
                    'payments' => array('captures' => array(array(
                        'id' => '3C679366HH908993F',
                        'amount' => array('currency_code' => $currency, 'value' => $value),
                    ))),
                )),
            ),
        );
    }

    private function pendingDonation($overrides = array()) {
        return $this->createMockDonation(array_merge(array(
            'id' => 4812,
            'payment_status' => 'pending',
            'transaction_id' => '5O190127TN364715T',
            'amount' => 25.00,
            'currency' => 'USD',
        ), $overrides));
    }

    public function test_rejects_when_token_does_not_belong_to_the_donation() {
        $wpdb = $this->mockWpdb();
        $wpdb->method('get_row')->willReturn($this->pendingDonation(array('transaction_id' => 'SOME-OTHER-ORDER', 'amount' => 10000.00)));
        $wpdb->expects($this->never())->method('update');
        $this->api->expects($this->never())->method('capture_payment');

        $this->assertFalse($this->plugin->complete_rest_donation('5O190127TN364715T', 'PAYER1', 4812));
    }

    public function test_rejects_when_donation_is_already_completed() {
        $wpdb = $this->mockWpdb();
        $wpdb->method('get_row')->willReturn($this->pendingDonation(array('payment_status' => 'completed')));
        $wpdb->expects($this->never())->method('update');
        $this->api->expects($this->never())->method('capture_payment');

        $this->assertFalse($this->plugin->complete_rest_donation('5O190127TN364715T', 'PAYER1', 4812));
    }

    public function test_rejects_when_donation_does_not_exist() {
        $wpdb = $this->mockWpdb();
        $wpdb->method('get_row')->willReturn(null);
        $wpdb->expects($this->never())->method('update');
        $this->api->expects($this->never())->method('capture_payment');

        $this->assertFalse($this->plugin->complete_rest_donation('5O190127TN364715T', 'PAYER1', 4812));
    }

    public function test_rejects_when_captured_amount_differs_from_donation() {
        $wpdb = $this->mockWpdb();
        $wpdb->method('get_row')->willReturn($this->pendingDonation(array('amount' => 10000.00)));
        $wpdb->expects($this->never())->method('update');
        $this->api->method('capture_payment')->willReturn($this->captureResult('1.00', 'USD'));

        $this->assertFalse($this->plugin->complete_rest_donation('5O190127TN364715T', 'PAYER1', 4812));
    }

    public function test_rejects_when_captured_currency_differs_from_donation() {
        $wpdb = $this->mockWpdb();
        $wpdb->method('get_row')->willReturn($this->pendingDonation(array('currency' => 'GBP')));
        $wpdb->expects($this->never())->method('update');
        $this->api->method('capture_payment')->willReturn($this->captureResult('25.00', 'USD'));

        $this->assertFalse($this->plugin->complete_rest_donation('5O190127TN364715T', 'PAYER1', 4812));
    }

    public function test_completes_donation_when_token_amount_and_currency_match() {
        $wpdb = $this->mockWpdb();
        $wpdb->method('get_row')->willReturnOnConsecutiveCalls($this->pendingDonation(), null);
        $wpdb->expects($this->once())
             ->method('update')
             ->with(
                 'wp_donations',
                 $this->callback(function ($data) {
                     return $data['payment_status'] === 'completed' && $data['transaction_id'] === '5O190127TN364715T';
                 }),
                 array('id' => 4812)
             )
             ->willReturn(1);
        $this->api->expects($this->once())->method('capture_payment')->with('5O190127TN364715T')
                  ->willReturn($this->captureResult('25.00', 'USD'));

        $this->assertTrue($this->plugin->complete_rest_donation('5O190127TN364715T', 'PAYER1', 4812));
    }
}
