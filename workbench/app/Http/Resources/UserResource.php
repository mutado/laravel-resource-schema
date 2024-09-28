<?php

namespace Workbench\App\Http\Resources;

use Illuminate\Http\Request;
use Mutado\LaravelResourceSchema\SchemaResource;

class UserResource extends SchemaResource
{
    protected array $alwaysIncluded = [
        'id',
    ];

    protected ?array $schemaTypes = [
        'mini' => [
            'id',
            'name',
            'email',
        ],
        'full' => [
            'id',
            'name',
            'email',
            'created_at',
            'updated_at',
        ],
        'empty' => [],
        'profile' => [
            'id',
            'name',
            'posts/mini',
        ]
    ];

    protected function schema(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'posts' => fn() => PostResource::collection($this->posts),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
