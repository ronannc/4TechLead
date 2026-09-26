<?php

namespace App\Mcp;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class McpAuthenticationResponse
{
    public static function required(): ResponseFactory
    {
        return Response::make(Response::error('Authentication is required to access 4TechLead data.'))
            ->withMeta([
                'mcp/www_authenticate' => [
                    'Bearer resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/4techlead').'", scope="mcp:use"',
                ],
            ]);
    }
}
