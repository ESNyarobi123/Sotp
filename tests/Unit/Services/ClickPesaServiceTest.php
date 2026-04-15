<?php

use App\Services\ClickPesaService;

test('generates consistent checksum regardless of key order', function () {
    $service = new ClickPesaService;
    $key = 'secret-key';

    $payload1 = ['currency' => 'TZS', 'amount' => '1000', 'orderReference' => 'TX123'];
    $payload2 = ['amount' => '1000', 'orderReference' => 'TX123', 'currency' => 'TZS'];

    $checksum1 = $service->generateChecksum($key, $payload1);
    $checksum2 = $service->generateChecksum($key, $payload2);

    expect($checksum1)->toBe($checksum2);
    expect(strlen($checksum1))->toBe(64); // HMAC-SHA256 hex digest
});

test('different payloads produce different checksums', function () {
    $service = new ClickPesaService;
    $key = 'secret-key';

    $checksum1 = $service->generateChecksum($key, ['amount' => '1000']);
    $checksum2 = $service->generateChecksum($key, ['amount' => '2000']);

    expect($checksum1)->not->toBe($checksum2);
});

test('handles nested objects in checksum', function () {
    $service = new ClickPesaService;
    $key = 'secret-key';

    $payload = [
        'amount' => '1000',
        'customer' => [
            'name' => 'John',
            'email' => 'john@example.com',
        ],
    ];

    $checksum = $service->generateChecksum($key, $payload);

    expect(strlen($checksum))->toBe(64);
});

test('maps clickpesa channels to payment methods', function () {
    expect(ClickPesaService::mapChannelToMethod('M-PESA'))->toBe('mpesa');
    expect(ClickPesaService::mapChannelToMethod('TIGO-PESA'))->toBe('tigo');
    expect(ClickPesaService::mapChannelToMethod('AIRTEL-MONEY'))->toBe('airtel');
    expect(ClickPesaService::mapChannelToMethod('HALOPESA'))->toBe('halopesa');
    expect(ClickPesaService::mapChannelToMethod('MIXX BY YAS'))->toBe('tigo');
    expect(ClickPesaService::mapChannelToMethod('UNKNOWN'))->toBe('mpesa');
});
