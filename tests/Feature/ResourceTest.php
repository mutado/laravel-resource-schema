<?php

namespace Mutado\LaravelResourceSchema\Tests\Feature;

use Mutado\LaravelResourceSchema\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Http\Resources\UserResource;
use Workbench\App\Models\User;

class ResourceTest extends TestCase
{
    #[Test]
    public function test_can_get_correct_json_for_schema_with_optional_properties()
    {
        User::factory()->count(3)->create();

        $json = UserResource::collection(User::all(), 'mini', ['email_verified_at'])->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                User::all()->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                    ];
                })
            ),
            $json
        );

        $json = UserResource::collection(User::all(), 'full', ['email_verified_at'])->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                User::all()->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ];
                })
            ),
            $json
        );
    }

    #[Test]
    public function test_optional_properties()
    {
        User::factory()->count(3)->create();

        $json = UserResource::make(User::first())
            ->useSchemaType([
                'id',
                '?name'
            ])
            ->withOptional('name')
            ->toJson();

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            }
        );

        $json = UserResource::make(User::first())
            ->useSchemaType([
                'id',
                '?name'
            ])
            ->withOptional('something')
            ->toJson();

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            function ($user) {
                return [
                    'id' => $user->id,
                ];
            }
        );

        $json = UserResource::make(User::first())
            ->useSchemaType([
                'id',
                '?name',
                '?email'
            ])
            ->withOptional([
                'name' => false,
                'email' => true
            ])
            ->toJson();

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                ];
            }
        );
    }

    #[Test]
    public function test_always_included_always_returned()
    {
        User::factory()->count(3)->create();

        $json = UserResource::collection(User::all(), 'empty')->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                User::all()->map(function ($user) {
                    return [
                        'id' => $user->id,
                    ];
                })
            ),
            $json
        );
    }

    #[Test]
    public function test_can_can_get_correct_relationship_schema()
    {
        User::factory()->count(3)->hasPosts(2)->create();

        $json = UserResource::collection(User::all(), 'profile')->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                User::all()->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'posts' => $user->posts->map(function ($post) {
                            return [
                                'id' => $post->id,
                                'title' => $post->title,
                                'content' => $post->content,
                            ];
                        }),
                    ];
                })
            ),
            $json
        );
    }

    #[Test]
    #[Group('custom-schema')]
    public function test_can_specify_custom_schema()
    {
        User::factory()->hasPosts(3)->count(3)->create();

        $json = UserResource::make(User::first())
            ->useSchemaType(
                [
                    'id',
                    'name',
                    'posts.test[?comments/big_comment, views]/mini_post',
                ]
            )->toJson();
        $map = function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'posts' => $user->posts->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'test' => 'test',
                    ];
                }),
            ];
        };

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            $map
        );

        $json = UserResource::collection(User::all(), [
            'id',
            'name',
            'posts.test[?comments/big_comment, views]/mini_post',
        ])->toJson();

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::all(),
            $map
        );
    }

    #[Test]
    #[Group('custom-schema')]
    public function test_can_specify_custom_schema_with_array_properties()
    {
        User::factory()->hasPosts(3)->count(3)->create();

        $json = UserResource::make(User::first())
            ->useSchemaType(
                [
                    'id',
                    'name',
                    'posts/mini_post' => [
                        'test'
                    ]
                ]
            )->toJson();
        $map = function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'posts' => $user->posts->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'test' => 'test',
                    ];
                }),
            ];
        };

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            $map
        );
    }

}
