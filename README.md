# Vortex PHP SDK

This package provides the Vortex PHP SDK for authentication and invitation management.

With this SDK, you can generate JWTs for use with the Vortex Widget and make API calls to the Vortex API.

## Installation

Install the SDK via Composer:

```bash
composer require teamvortexsoftware/vortex-php-sdk
```

## Getting Started

Once you have the SDK installed, [login](https://admin.vortexsoftware.com/signin) to Vortex and [create an API Key](https://admin.vortexsoftware.com/members/api-keys). Keep your API key safe! Vortex does not store the API key and it is not retrievable once it has been created.

Your API key is used to:
- Sign JWTs for use with the Vortex Widget
- Make API calls against the [Vortex API](https://api.vortexsoftware.com/api)

## Usage

### Generate a JWT for the Vortex Widget

The Vortex Widget requires a JWT to authenticate users. Here's how to generate one:

```php
<?php

require_once 'vendor/autoload.php';

use TeamVortexSoftware\VortexSDK\VortexClient;

// Initialize the Vortex client with your API key
$vortex = new VortexClient(getenv('VORTEX_API_KEY'));

// User ID from your system
$userId = 'users-id-in-my-system';

// Identifiers associated with the user
$identifiers = [
    ['type' => 'email', 'value' => 'user@example.com'],
    ['type' => 'sms', 'value' => '18008675309']
];

// Groups the user belongs to (specific to your product)
$groups = [
    ['type' => 'workspace', 'id' => 'workspace-123', 'name' => 'My Workspace'],
    ['type' => 'document', 'id' => 'doc-456', 'name' => 'Project Plan']
];

// User role (if applicable)
$role = 'admin';

// Generate the JWT
$jwt = $vortex->generateJwt($userId, $identifiers, $groups, $role);

echo $jwt;
```

### Create an API Endpoint to Provide JWT

Here's an example using a simple PHP endpoint:

```php
<?php

require_once 'vendor/autoload.php';

use TeamVortexSoftware\VortexSDK\VortexClient;

header('Content-Type: application/json');

$vortex = new VortexClient(getenv('VORTEX_API_KEY'));

$userId = $_SESSION['user_id']; // Get from your session
$identifiers = [
    ['type' => 'email', 'value' => $_SESSION['user_email']]
];
$groups = $_SESSION['user_groups'] ?? [];
$role = $_SESSION['user_role'] ?? null;

$jwt = $vortex->generateJwt($userId, $identifiers, $groups, $role);

echo json_encode(['jwt' => $jwt]);
```

### Use with Laravel

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TeamVortexSoftware\VortexSDK\VortexClient;

class VortexController extends Controller
{
    public function getJwt(Request $request)
    {
        $vortex = new VortexClient(config('services.vortex.api_key'));

        $user = $request->user();

        $jwt = $vortex->generateJwt(
            userId: $user->id,
            identifiers: [
                ['type' => 'email', 'value' => $user->email]
            ],
            groups: $user->groups->map(fn($g) => [
                'type' => $g->type,
                'id' => $g->id,
                'name' => $g->name
            ])->toArray(),
            role: $user->role
        );

        return response()->json(['jwt' => $jwt]);
    }
}
```

## API Methods

### Invitation Management

#### Get Invitations by Target

```php
$invitations = $vortex->getInvitationsByTarget('email', 'user@example.com');
```

#### Get Invitation by ID

```php
$invitation = $vortex->getInvitation('invitation-id');
```

#### Revoke Invitation

```php
$vortex->revokeInvitation('invitation-id');
```

#### Accept Invitations

```php
$result = $vortex->acceptInvitations(
    ['invitation-id-1', 'invitation-id-2'],
    ['type' => 'email', 'value' => 'user@example.com']
);
```

#### Get Invitations by Group

```php
$invitations = $vortex->getInvitationsByGroup('workspace', 'workspace-123');
```

#### Delete Invitations by Group

```php
$vortex->deleteInvitationsByGroup('workspace', 'workspace-123');
```

#### Reinvite

```php
$result = $vortex->reinvite('invitation-id');
```

## Requirements

- PHP 8.0 or higher
- `json` extension (typically enabled by default)

## License

MIT

## Support

For support, please contact support@vortexsoftware.com or visit our [documentation](https://docs.vortexsoftware.com).
