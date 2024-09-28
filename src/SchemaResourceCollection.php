<?php

namespace Mutado\LaravelResourceSchema;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class SchemaResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    public string|array|null $schemaType = null;
    public ?array $properties = null;
    public array $options = [];
    public array $optional = [];

    /**
     * Create a new anonymous resource collection.
     *
     * @param mixed $resource
     * @param string $collects
     * @param string|array|null $schemaType
     * @param array|null $properties
     * @param array $options
     * @param array|string $optional
     */
    public function __construct(mixed $resource, string $collects, string|array|null $schemaType = null, array $properties = null, array $options = [], array|string $optional = [])
    {
        $this->resource = $resource;
        $this->collects = $collects;
        $this->schemaType = $schemaType;
        $this->properties = $properties;
        $this->options = $options;
        $this->optional = is_array($optional) ? $optional : explode(',', $optional);
    }

    public function useSchemaType(string|array|null $schemaType): self
    {
        $this->schemaType = $schemaType;
        return $this;
    }

    public function useProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    public function useOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    protected function collectResource($resource)
    {
        if ($resource instanceof MissingValue) {
            return $resource;
        }

        if (is_array($resource)) {
            $resource = new Collection($resource);
            if ($resource->isEmpty()) {
                return $resource;
            }
        }

        $collects = $this->collects();

        $this->collection = $collects && !$resource->first() instanceof $collects
            ? $resource->map(function ($value, $key) use ($collects) {
                return new $collects($value, $this->schemaType, $this->properties, $this->options, $this->optional);
            })
            : $resource->toBase();

        return ($resource instanceof AbstractPaginator || $resource instanceof AbstractCursorPaginator)
            ? $resource->setCollection($this->collection)
            : $this->collection;
    }

    public function toArray($request)
    {
        $this->resource = $this->collectResource($this->resource);
        return parent::toArray($request);
    }
}
