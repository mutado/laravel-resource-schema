# laravel-resource-schema

Laravel Resource Schema is a package that extends Laravel's API Resources, providing a flexible and powerful way to define and control the structure of your API responses.

Features:
- Define schema types for your resources
- Include properties dynamically
- Support for nested resources
- Optional properties that can be included on demand
- Easy to use with existing Laravel projects

## Installation
You can install the package via composer:
```bash
composer require mutado/laravel-resource-schema
```

## Usage

### Basic Usage

1. Create a new resource that extends SchemaResource:
    ```php
    php Mutado\LaravelResourceSchema\SchemaResource;
    
    class UserResource extends SchemaResource
    {
        // Define the schema types for the resource
        protected ?array $schemaTypes = [
            'mini' => [
                'id',
                'name',
            ],
            'full' => [
                'id',
                'name',
                'email',
                'posts',
                'created_at',
            ],
        ];
    
        protected function schema(Request $request): array
        {
            return [
                'id' => $this->id,
                'image' => fn() => ImageResource::make($this->image),
                'name' => $this->name,
                'email' => $this->email,
                // Use closure to lazy load the posts
                'posts' => fn() => PostResource::collection($this->posts),
                'created_at' => $this->created_at,
            ];
        }
    }
    ```
2. Use the resource in your controller:
    ```php
    public function show(User $user)
    {
        // Use the schema type 'full' and include the 'image' property
        return UserResource::make($user)->useSchemaType('full')->withPartial(['image']);
    }
    ```
   
### Nested Resources

You can define how to include nested resources in your schema
```php
protected ?array $schemaTypes = [
    'post' => [
        'id',
        'title',
        'content',
        // Set the schema type for the author
        'author/mini',
        // Set the schema for comments
        'comments' => [
            'id',
            'content',
            'author/mini',
        ]
    ]
];
```

### Optional Properties

You can define optional properties in you schema type and include them on demand using the '?' prefix:
```php
// In this example we don't include the 'email' property if user is not authenticated 
protected ?array $schemaTypes = [
    'profile' => [
        'id',
        'name',
        '?email',
        'posts',
        'created_at',
    ]
];

protected function show(User $user)
{
    return UserResource::make($user)
        ->useSchemaType('profile')
        // Email only included if user is authenticated
        ->withPartial([
            'email' => auth()->check(),
        ]);
}
```

### Custom Schema

If really need a custom schema for a specific request, you can define it using the `useSchemaType` method and by passing a schema array:
```php
protected function show(User $user)
{
    return UserResource::make($user)
        ->useSchemaType([
            'id',
            'name',
            '?email',
            'posts/mini',
            'created_at',
        ]);
}
```

### Schema Property Syntax

The Laravel Resource Schema package uses a simple but powerful syntax for defining schemas. Here's a breakdown of the schema syntax:

- `property` - A basic property is defined simply by its name
- `?property` - Optional properties are prefixed with a question mark `?`
- `nested_resource.nested_property` - You can include nested properties using dot notation
- `nested_resource[nested_property1, nested_property2]` - or array syntax
- `nested_resource.*` - To include all properties of a nested resource, use the wildcard `*`
- `nested_resource/mini` - You can specify a schema for a nested resource

Combining these syntaxes allows you to define complex and flexible schemas for your resources.

```php
protected ?array $schemaTypes = [
    '?friends.avatar/mini_profile',
]
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
