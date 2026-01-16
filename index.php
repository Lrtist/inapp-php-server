<?php
require 'vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
        // Decode JWT without verification (for testing; verify signature in production)
        $parts = explode('.', $receiptData);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        $decoded = (object) $payload;
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
        // Re-decode
        $parts = explode('.', $sub['receiptData']);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        $decoded = (object) $payload;
        $payload = $decoded;
        $expMs = $payload->expiresDate ?? null;
        if ($expMs) {
            $sub['expirationDate'] = new DateTime('@' . ($expMs / 1000));
            $sub['status'] = (new DateTime()) < $sub['expirationDate'] ? 'active' : 'expired';
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