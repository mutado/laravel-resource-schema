<?php

namespace Workbench\App\Http\Resources;

use Illuminate\Http\Request;
use Mutado\LaravelResourceSchema\SchemaResource;

class PostResource extends SchemaResource
{
    protected ?array $schemaTypes = [
        'mini' => [
            'id',
            'title',
            'content',
        ],
        'mini_post' => [
            'id',
            'title',
        ]
    ];


    protected function schema(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'test' => 'test',
            'user' => fn() => UserResource::make($this->user),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
