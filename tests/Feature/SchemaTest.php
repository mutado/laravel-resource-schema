<?php

namespace Mutado\LaravelResourceSchema\Tests\Feature;

use Mutado\LaravelResourceSchema\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Http\Resources\UserResource;
use Workbench\App\Models\User;

class SchemaTest extends TestCase
{
    /**
     * Can get correct json for schema defined in resource
     * @return void
     */
    #[Test]
    #[Group('schema')]
    public function test_can_get_correct_json_schema()
    {
        User::factory()->count(3)->create();

        $json = UserResource::make(User::first(), 'mini')->toJson();
        $map = function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        };

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            $map
        );

        $json = UserResource::collection(User::all(), 'mini')->toJson();

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::all(),
            $map
        );

        $json = UserResource::make(User::first(), 'full')->toJson();
        $map = function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        };

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            $map
        );

        $json = UserResource::collection(User::all(), 'full')->toJson();

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::all(),
            $map
        );
    }

    #[Test]
    public function test_can_get_nested_schema()
    {
        User::factory()->count(3)->hasPosts(2)->create();

        $json = UserResource::make(User::first(), 'profile')->toJson();
        $map = function ($user) {
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
        };

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::first(),
            $map
        );

        $json = UserResource::collection(User::all(), 'profile')->toJson();

        $this->assertJsonSchemaEqualsArray(
            $json,
            User::all(),
            $map
        );
    }
}
