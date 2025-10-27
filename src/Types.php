<?php

namespace TeamVortexSoftware\VortexSDK;

/**
 * Type documentation for Vortex SDK responses
 * PHP uses dynamic typing with arrays, but this documents the expected structure
 */
class Types
{
    /**
     * Group structure for JWT generation (input)
     *
     * @example
     * [
     *     'type' => 'workspace',
     *     'id' => 'workspace-123',       // Legacy field (deprecated, use groupId)
     *     'groupId' => 'workspace-123',  // Preferred field
     *     'name' => 'My Workspace'
     * ]
     *
     * @var array{type: string, id?: string, groupId?: string, name: string}
     */
    const GROUP_INPUT = [
        'type' => 'string',      // Required: Group type (e.g., "workspace", "team")
        'id' => 'string',        // Optional: Legacy field (deprecated, use groupId)
        'groupId' => 'string',   // Optional: Preferred - Customer's group ID
        'name' => 'string'       // Required: Group name
    ];

    /**
     * InvitationGroup structure from API responses
     * This matches the MemberGroups table structure from the API
     *
     * @example
     * [
     *     'id' => '550e8400-e29b-41d4-a716-446655440000',
     *     'accountId' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
     *     'groupId' => 'workspace-123',
     *     'type' => 'workspace',
     *     'name' => 'My Workspace',
     *     'createdAt' => '2025-01-27T12:00:00.000Z'
     * ]
     *
     * @var array{id: string, accountId: string, groupId: string, type: string, name: string, createdAt: string}
     */
    const INVITATION_GROUP = [
        'id' => 'string',          // Vortex internal UUID
        'accountId' => 'string',   // Vortex account ID
        'groupId' => 'string',     // Customer's group ID (the ID they provided to Vortex)
        'type' => 'string',        // Group type (e.g., "workspace", "team")
        'name' => 'string',        // Group name
        'createdAt' => 'string'    // ISO 8601 timestamp when the group was created
    ];

    /**
     * Invitation structure from API responses
     *
     * @example
     * [
     *     'id' => '550e8400-e29b-41d4-a716-446655440000',
     *     'accountId' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
     *     'groups' => [INVITATION_GROUP, ...],
     *     // ... other fields
     * ]
     *
     * @var array
     */
    const INVITATION = [
        'id' => 'string',
        'accountId' => 'string',
        'clickThroughs' => 'int',
        'configurationAttributes' => 'array',
        'attributes' => 'array',
        'createdAt' => 'string',
        'deactivated' => 'bool',
        'deliveryCount' => 'int',
        'deliveryTypes' => 'array', // of string
        'foreignCreatorId' => 'string',
        'invitationType' => 'string',
        'modifiedAt' => 'string',
        'status' => 'string',
        'target' => 'array', // of ['type' => string, 'value' => string]
        'views' => 'int',
        'widgetConfigurationId' => 'string',
        'projectId' => 'string',
        'groups' => 'array', // of INVITATION_GROUP structures
        'accepts' => 'array' // of acceptance structures
    ];
}
