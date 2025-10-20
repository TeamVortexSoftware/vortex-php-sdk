<?php

/**
 * Basic usage example for the Vortex PHP SDK
 */

require_once __DIR__ . '/../vendor/autoload.php';

use TeamVortexSoftware\VortexSDK\VortexClient;

// Initialize the Vortex client
$vortex = new VortexClient(getenv('VORTEX_API_KEY'));

// Example user data
$userId = 'user-123';
$identifiers = [
    ['type' => 'email', 'value' => 'user@example.com'],
    ['type' => 'sms', 'value' => '18008675309']
];
$groups = [
    ['type' => 'workspace', 'id' => 'ws-1', 'name' => 'Main Workspace'],
    ['type' => 'team', 'id' => 'team-1', 'name' => 'Engineering']
];
$role = 'admin';

// Generate a JWT
echo "Generating JWT...\n";
$jwt = $vortex->generateJwt($userId, $identifiers, $groups, $role);
echo "JWT: " . $jwt . "\n\n";

// Example: Get invitations by target
try {
    echo "Fetching invitations by email...\n";
    $invitations = $vortex->getInvitationsByTarget('email', 'user@example.com');
    echo "Found " . count($invitations) . " invitations\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
