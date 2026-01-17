<?php
require 'vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Cache for Apple public keys
$appleKeys = null;

function getApplePublicKeys() {
    global $appleKeys;
    if ($appleKeys === null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://appleid.apple.com/auth/keys');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        $appleKeys = [];
        foreach ($data['keys'] as $key) {
            $appleKeys[$key['kid']] = $key;
        }
    }
    return $appleKeys;
}

$app = AppFactory::create();

// In-memory storage (use database in production)
$subscriptions = [];

$app->post('/verify-purchase', function (Request $request, Response $response) use (&$subscriptions) {
    $data = json_decode($request->getBody()->getContents(), true);
    $appleId = $data['appleId'] ?? null;
    $userId = $data['userId'] ?? null;
    $receiptData = $data['receiptData'] ?? null;

    if (!$appleId || !$userId || !$receiptData) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'Missing appleId, userId, or receiptData']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        // Decode header to get kid and x5c
        $parts = explode('.', $receiptData);
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
        error_log('Decoded header: ' . json_encode($header));
        $kid = $header['kid'] ?? null;
        $x5c = $header['x5c'] ?? null;

        if (!$x5c || !is_array($x5c)) {
            throw new Exception('Invalid JWT header: missing x5c');
        }

        if ($kid) {
            // Get Apple public keys
            $appleKeys = getApplePublicKeys();
            if (!isset($appleKeys[$kid])) {
                throw new Exception('Unknown key ID: ' . $kid);
            }

            // Build certificate from x5c
            $certificate = "-----BEGIN CERTIFICATE-----\n" . $x5c[0] . "\n-----END CERTIFICATE-----";
            $publicKey = openssl_pkey_get_public($certificate);
            if (!$publicKey) {
                throw new Exception('Invalid certificate');
            }

            // Decode JWT with verification
            $decoded = JWT::decode($receiptData, new Key($publicKey, 'ES256'));
        } else {
            // Fallback: decode without verification (for test receipts without kid)
            $payloadPart = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            $decoded = (object) $payloadPart;
        }
        $payload = $decoded;

        $expMs = $payload->expiresDate ?? null;
        if (!$expMs) {
            throw new Exception('Invalid receipt: no expiresDate');
        }

        $expirationDate = new DateTime('@' . ($expMs / 1000)); // Convert ms to seconds

        $subscriptions[$userId] = [
            'appleId' => $appleId,
            'productId' => $payload->productId ?? null,
            'expirationDate' => $expirationDate,
            'status' => (new DateTime()) < $expirationDate ? 'active' : 'expired',
            'receiptData' => $receiptData
        ];

        $result = [
            'success' => true,
            'subscription' => [
                'appleId' => $appleId,
                'productId' => $subscriptions[$userId]['productId'],
                'expirationDate' => $expirationDate->format(DateTime::ISO8601),
                'status' => $subscriptions[$userId]['status']
            ]
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

$app->get('/user-subscription/{userId}', function (Request $request, Response $response, $args) use (&$subscriptions) {
    $userId = $args['userId'];

    if (!isset($subscriptions[$userId])) {
        $response->getBody()->write(json_encode(['subscription' => null]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $sub = $subscriptions[$userId];

    try {
        // Re-verify the receipt
        $parts = explode('.', $sub['receiptData']);
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
        $kid = $header['kid'] ?? null;
        $x5c = $header['x5c'] ?? null;

        if ($kid && $x5c && is_array($x5c)) {
            $appleKeys = getApplePublicKeys();
            if (isset($appleKeys[$kid])) {
                $certificate = "-----BEGIN CERTIFICATE-----\n" . $x5c[0] . "\n-----END CERTIFICATE-----";
                $publicKey = openssl_pkey_get_public($certificate);
                if ($publicKey) {
                    $decoded = JWT::decode($sub['receiptData'], new Key($publicKey, 'ES256'));
                    $payload = $decoded;
                    $expMs = $payload->expiresDate ?? null;
                    if ($expMs) {
                        $sub['expirationDate'] = new DateTime('@' . ($expMs / 1000));
                        $sub['status'] = (new DateTime()) < $sub['expirationDate'] ? 'active' : 'expired';
                    }
                } else {
                    $sub['status'] = 'verification_failed';
                }
            } else {
                $sub['status'] = 'verification_failed';
            }
        } else {
            $sub['status'] = 'verification_failed';
        }
    } catch (Exception $e) {
        $sub['status'] = 'verification_failed';
    }

    $result = [
        'subscription' => [
            'appleId' => $sub['appleId'],
            'productId' => $sub['productId'],
            'expirationDate' => $sub['expirationDate']->format(DateTime::ISO8601),
            'status' => $sub['status']
        ]
    ];

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();