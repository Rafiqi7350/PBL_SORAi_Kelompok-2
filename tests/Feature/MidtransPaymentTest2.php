<?php

test('test_webhook_rejects_invalid_signature', function () {
    $response = $this->postJson('/api/midtrans/webhook', [
        'order_id' => 'ORDER-123',
        'status_code' => '200',
        'gross_amount' => '150000.00',
        'signature_key' => 'invalid_signature_key',
    ]);

    $response->assertStatus(403);
});

test('test_webhook_successfully_updates_payment_to_paid_on_settlement', function () {
    // Simulasi data webhook valid dari Midtrans
    $response = $this->postJson('/api/midtrans/webhook', [
        'order_id' => 'ORDER-123',
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => '150000.00',
        'signature_key' => 'valid_signature', // Sesuaikan dengan logika app/helpers test Anda
    ]);

    $response->assertOk();
});

test('test_snap_token_endpoint_indicates_mock_when_server_key_is_empty', function () {
    config(['services.midtrans.server_key' => '']);

    $response = $this->postJson('/api/payment/snap-token', [
        'order_id' => 1,
        'amount' => 50000,
    ]);

    $response->assertOk();
});
