<?php

declare(strict_types=1);

namespace Mutado\LaravelResourceSchema\Tests;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench, RefreshDatabase;

    protected function assertJsonSchemaEqualsArray(
        string           $json,
        Model|Collection $resource,
        Closure          $map
    ): void
    {
        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                $resource instanceof Collection
                    ? $resource->map($map)
                    : $map($resource)
            ),
            $json
        );
    }
}
