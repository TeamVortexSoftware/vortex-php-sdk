<?php

namespace TeamVortexSoftware\VortexSDK;

use Exception;

/**
 * Vortex PHP SDK Client
 *
 * Provides JWT generation and Vortex API integration for PHP applications.
 * Compatible with React providers and follows the same paradigms as other Vortex SDKs.
 */
class VortexClient
{
    private string $apiKey;
    private string $baseUrl;

    /**
     * Create a new Vortex client
     *
     * @param string $apiKey Your Vortex API key
     * @param string|null $baseUrl Optional custom base URL (defaults to production API)
     */
    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? getenv('VORTEX_API_BASE_URL') ?: 'https://api.vortexsoftware.com';
    }

    /**
     * Generate a JWT token for the given user data
     *
     * This uses the same algorithm as the Node.js SDK to ensure
     * complete compatibility with React providers.
     *
     * @param string $userId Unique identifier for the user
     * @param array $identifiers Array of identifier arrays with 'type' and 'value' keys
     * @param array $groups Array of group arrays with 'type', 'id', and 'name' keys
     * @param string|null $role Optional user role
     * @return string JWT token
     * @throws Exception If API key is invalid or JWT generation fails
     */
    public function generateJwt(
        string $userId,
        array $identifiers,
        array $groups,
        ?string $role = null
    ): string {
        // Parse API key: format is VRTX.base64encodedId.key
        $parts = explode('.', $this->apiKey);
        if (count($parts) !== 3) {
            throw new Exception('Invalid API key format');
        }

        [$prefix, $encodedId, $key] = $parts;

        if ($prefix !== 'VRTX') {
            throw new Exception('Invalid API key prefix');
        }

        // Decode the UUID from base64url
        $idBytes = $this->base64UrlDecode($encodedId);
        if (strlen($idBytes) !== 16) {
            throw new Exception('Invalid UUID byte length');
        }
        $id = $this->uuidFromBytes($idBytes);

        $expires = time() + 3600; // 1 hour from now

        // Step 1: Derive signing key from API key + ID
        $signingKey = hash_hmac('sha256', $id, $key, true);

        // Step 2: Build header + payload (same structure as Node.js)
        $header = [
            'iat' => time(),
            'alg' => 'HS256',
            'typ' => 'JWT',
            'kid' => $id,
        ];

        $payload = [
            'userId' => $userId,
            'groups' => $groups,
            'role' => $role,
            'expires' => $expires,
            'identifiers' => $identifiers,
        ];

        // Step 3: Base64URL encode header and payload
        $headerB64 = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        // Step 4: Sign with HMAC-SHA256
        $toSign = $headerB64 . '.' . $payloadB64;
        $signature = hash_hmac('sha256', $toSign, $signingKey, true);
        $signatureB64 = $this->base64UrlEncode($signature);

        return $toSign . '.' . $signatureB64;
    }

    /**
     * Make an API request to the Vortex service
     *
     * @param string $method HTTP method (GET, POST, DELETE, etc.)
     * @param string $path API endpoint path
     * @param array|null $body Request body data
     * @param array|null $queryParams Query parameters
     * @return array API response data
     * @throws Exception If the API request fails
     */
    private function apiRequest(
        string $method,
        string $path,
        ?array $body = null,
        ?array $queryParams = null
    ): array {
        $url = $this->baseUrl . $path;

        // Add query parameters
        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        // Prepare request
        $options = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $this->apiKey,
                    'User-Agent: vortex-php-sdk/1.0.0',
                ],
                'ignore_errors' => true,
            ],
        ];

        // Add body for POST/PUT requests
        if ($body && in_array($method, ['POST', 'PUT'])) {
            $options['http']['content'] = json_encode($body);
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        // Check for errors
        if ($response === false) {
            throw new Exception('Vortex API request failed');
        }

        // Get status code from headers
        $statusCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $statusCode = (int)$matches[1];
                    break;
                }
            }
        }

        if ($statusCode >= 400) {
            throw new Exception('Vortex API request failed: ' . $statusCode . ' - ' . $response);
        }

        // Handle empty responses
        if (empty($response)) {
            return [];
        }

        $decoded = json_decode($response, true);
        return $decoded ?? [];
    }

    /**
     * Get invitations by target (email or sms)
     *
     * @param string $targetType Type of target ('email' or 'sms')
     * @param string $targetValue Target value (email address or phone number)
     * @return array List of invitations
     * @throws Exception If the request fails
     */
    public function getInvitationsByTarget(string $targetType, string $targetValue): array
    {
        $response = $this->apiRequest('GET', '/api/v1/invitations', null, [
            'targetType' => $targetType,
            'targetValue' => $targetValue,
        ]);

        return $response['invitations'] ?? [];
    }

    /**
     * Get a specific invitation by ID
     *
     * @param string $invitationId The invitation ID
     * @return array The invitation data
     * @throws Exception If the request fails
     */
    public function getInvitation(string $invitationId): array
    {
        return $this->apiRequest('GET', "/api/v1/invitations/{$invitationId}");
    }

    /**
     * Revoke (delete) an invitation
     *
     * @param string $invitationId The invitation ID to revoke
     * @return array Success response
     * @throws Exception If the request fails
     */
    public function revokeInvitation(string $invitationId): array
    {
        return $this->apiRequest('DELETE', "/api/v1/invitations/{$invitationId}");
    }

    /**
     * Accept multiple invitations
     *
     * @param array $invitationIds List of invitation IDs to accept
     * @param array $target Target array with 'type' and 'value' keys
     * @return array The accepted invitation result
     * @throws Exception If the request fails
     */
    public function acceptInvitations(array $invitationIds, array $target): array
    {
        $body = [
            'invitationIds' => $invitationIds,
            'target' => $target,
        ];

        return $this->apiRequest('POST', '/api/v1/invitations/accept', $body);
    }

    /**
     * Delete all invitations for a specific group
     *
     * @param string $groupType The group type
     * @param string $groupId The group ID
     * @return array Success response
     * @throws Exception If the request fails
     */
    public function deleteInvitationsByGroup(string $groupType, string $groupId): array
    {
        return $this->apiRequest('DELETE', "/api/v1/invitations/by-group/{$groupType}/{$groupId}");
    }

    /**
     * Get all invitations for a specific group
     *
     * @param string $groupType The group type
     * @param string $groupId The group ID
     * @return array List of invitations for the group
     * @throws Exception If the request fails
     */
    public function getInvitationsByGroup(string $groupType, string $groupId): array
    {
        $response = $this->apiRequest('GET', "/api/v1/invitations/by-group/{$groupType}/{$groupId}");
        return $response['invitations'] ?? [];
    }

    /**
     * Reinvite a user (send invitation again)
     *
     * @param string $invitationId The invitation ID to reinvite
     * @return array The reinvited invitation result
     * @throws Exception If the request fails
     */
    public function reinvite(string $invitationId): array
    {
        return $this->apiRequest('POST', "/api/v1/invitations/{$invitationId}/reinvite");
    }

    /**
     * Base64URL encode (no padding, URL-safe)
     *
     * @param string $data Data to encode
     * @return string Base64URL encoded string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decode
     *
     * @param string $data Base64URL encoded string
     * @return string Decoded data
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padLen = 4 - $remainder;
            $data .= str_repeat('=', $padLen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Convert binary UUID data to string format
     *
     * @param string $bytes 16 bytes of UUID data
     * @return string UUID string in format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
     */
    private function uuidFromBytes(string $bytes): string
    {
        $hex = bin2hex($bytes);
        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
