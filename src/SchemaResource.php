<?php

namespace Mutado\LaravelResourceSchema;

use ArgumentCountError;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

abstract class SchemaResource extends JsonResource
{
    /**
     * Extend the resource with the given properties.
     * Array of strings. Use '*' to include all properties.
     * Example: ['id', 'name', 'description']
     * Or you can use nested resources to include nested properties
     * Example: ['products.images', 'comments.user']
     * @notice Nested resources work only if the nested resource extends SchemaResource
     * @var string[]
     */
    private array $withPartial;

    /**
     * Extend the resource with the given optional properties.
     * Array of strings. Use '*' to include all optional properties.
     * Optional properties must be defined in the schema with '?' prefix.
     * Optional properties are included only if they are requested.
     * @var array
     */
    private array $withOptional;

    /**
     * The schema type to use.
     * @var string|array|null
     */
    private string|array|null $schemaType;


    /**
     * Properties that are always included in the resource.
     * Define this in the resource class that extends this class.
     * Example: ['id', 'name', 'description']
     * Or you can use nested resources to include nested properties
     * Example: ['products.images', 'comments.user']
     * @notice Nested resources work only if the nested resource extends SchemaResource
     * @var string[]
     */
    protected array $alwaysIncluded = [];

    /**
     * Define the schema types for the resource for easier use.
     * Example: ['mini' => ['id', 'name', 'description'], 'full' => ['id', 'name', 'description', 'price']]
     * Or you can use nested resources to include nested properties
     * Example: ['mini' => ['id', 'name', 'description'], 'full' => ['id', 'name', 'description', 'price', 'products.images']]
     * You can use '*' or 'products.*' to include all properties.
     * @notice Nested resources work only if the nested resource extends SchemaResource
     * @var array|null
     */
    protected ?array $schemaTypes = null;

    protected array $options = [];

    /**
     * @param mixed $resource
     * @param string|array|null $schemaType
     * @param array|null $properties
     * @param array $options
     * @param array $withOptional
     */
    public function __construct($resource, string|array|null $schemaType = null, array $properties = null, array $options = [], array $withOptional = [])
    {
        parent::__construct($resource);
        $this->schemaType = $schemaType;
        $this->withPartial = $properties === null ? ['*'] : $properties;
        $this->withOptional = $withOptional;
        $this->options = $options;
    }


    /**
     * Select the schema type from the defined schema types.
     * @throws Exception
     */
    public function useSchemaType(array|string $schemaType): self
    {
        if (is_string($schemaType)) {
            if ($this->schemaTypes === null) {
                throw new Exception("Schema types are not defined in class " . get_class($this) . " but tried to use schema type '$schemaType'.");
            }
            if (!in_array($schemaType, array_keys($this->schemaTypes))) {
                throw new Exception("Schema type '$schemaType' is not defined in class " . get_class($this) . ".");
            }
        }
        $this->schemaType = $schemaType;
        if ($this->withPartial === ['*']) {
            $this->withPartial = [];
        }
        return $this;
    }

    /**
     * Include all properties in the resource.
     * Marks all properties as included.
     * @notice This will include all properties only in the current resource. Nested resources will not be affected.
     * @param bool $full
     * @return self
     */
    public function useFull(bool $full = true): self
    {
        $this->withPartial = $full ? ['*'] : [];

        return $this;
    }

    /**
     * Include only the given properties in the resource.
     * If used with useSchemaType(), the properties will be included in addition to the schema type.
     * @param array $properties
     * @return self
     */
    public function withPartial(array $properties): self
    {
        $this->withPartial = $properties;

        return $this;
    }

    /**
     * Include optional properties in the resource.
     * Optional properties must be defined in the schema with '?' prefix.
     * If property is not define in the schema, it will be included. This is for security reasons.
     * @param array|string $properties
     * @return self
     */
    public function withOptional(array|string $properties): self
    {
        if (is_string($properties)) {
            $properties = explode(',', $properties);
        }

        // if it's not a list, return only keys which values are true
        if (!array_is_list($properties)) {
            $properties = array_keys(array_filter($properties));
        }

        $this->withOptional = $properties;

        return $this;
    }

    public function withOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Determine if the given property should be included in the resource.
     * @param string $property
     * @return bool
     */
    public function isIncluded(string $property): bool
    {
        return in_array($property, $this->withPartial) || in_array('*', $this->withPartial) || in_array($property, $this->alwaysIncluded);
    }

    public function hasOption(string $option): bool
    {
        return in_array($option, $this->options);
    }

    /**
     * Define the schema for the resource.
     * @param Request $request
     * @return array
     */
    abstract protected function schema(Request $request): array;

    private function isComputedProperty($value): bool
    {
        return is_callable($value);
    }

    /**
     * Transform the resource into an array.
     * @param Request $request
     * @return array
     * @throws Exception
     */
    final function toArray(Request $request): array
    {

        // If resource is null, return empty array
        if ($this->resource === null) {
            return [];
        }

        // Final result
        $result = [];

        // Template for the resource
        // add always included properties before everything
        $template = collect($this->alwaysIncluded ?? []);

        // If schema type is defined, use it
        if ($this->schemaType) {
            try {

                // remove '*' from template, which is included by default
                $this->withPartial = array_diff($this->withPartial, ['*']);
            } catch (\Exception $e) {
                dd($this->withPartial);
            }

            // check if schema is array
            if (is_array($this->schemaType)) {
                // using custom schema
                $template = $template->merge($this->schemaType);
            } else {
                // check if schema types are defined
                if (!$this->schemaTypes) {
                    throw new Exception("Schema types are not defined in resource " . get_class($this));
                }
                // check if schema type key is defined
                if (!array_key_exists($this->schemaType, $this->schemaTypes)) {
                    throw new Exception("Schema type '$this->schemaType' is not defined in resource " . get_class($this));
                }
                // merge schema type properties with template
                $template = $template->merge($this->schemaTypes[$this->schemaType]);
            }
        }

        // add included partial properties
        // unique by value if key is not numeric
        $template = $template->merge($this->withPartial)->unique(
            fn($value, $key) => is_numeric($key) ? $value : $key
        );

        $schema = collect($this->schema($request));

        // check if template contains '*'
        // if it does, remove it and add all attributes from schema
        $getAllAttributes = false;
        if ($template->contains('*')) {
            // remove '*' from template
            $template = $template->filter(fn($value) => $value !== '*');
            // add all properties from schema
            $getAllAttributes = true;

            // todo: check this
            // add all properties from schema that are not in template
            // and are not computed properties
            $schema->each(function ($value, $key) use ($template) {
                if (!$template->contains($key) && !$this->isComputedProperty($value)) {
                    $template->push($key);
                }
            });
        }

        foreach ($template as $key => $value) {

            /**
             * property schema, defines how to include properties
             * Syntax:
             * ?param[nested_param, nested_param2]/schema[nested_param, nested_param2]
             * Examples:
             * param - property
             * ?param - optional property (only if requested)
             * param.nested_param - property with sub-property (nested resource)
             * param[nested_param, nested_param2] - property with multiple sub-properties (nested resource)
             * param.* - all properties of nested resource
             * param/schema - property with schema (nested resource with specific schema)
             * param/schema[nested_param, nested_param2] - nested resource with specific schema and multiple sub-properties
             * param.* /schema - all properties of nested resource with specific schema (IDK if this is useful)
             * ?param/schema - optional property with schema
             */
            $propertySchema = is_numeric($key) ? $value : $key;


            $pattern = '/^' .
                '(\??)' .                        // Optional marker
                '([^.\[\/]+)' .                  // Main parameter
                '(?:\.(\*|\w+))?' .              // Nested parameter or wildcard
                '(?:\[((?:[^[\]]+|\[.*?\])*)\])?' . // Array of nested parameters
                '(\/[\w\[\],]+)?$' .             // Schema
                '/';

            preg_match($pattern, $propertySchema, $matches);

            /*
             * Optional property only loaded if requested
             * Use case: From API request, include only if query parameter is set
             * Schema Syntax: ?param
             */
            $isOptional = !empty($matches[1]);

            /**
             * Actual attribute in the schema
             */
            $attribute = $matches[2];

            /*
             * Nested attributes
             * Schema Syntax:
             * - param.nested_param
             * - param[nested_param, nested_param2]
             * - param.*
             */
            if (is_array($value)) {
                $nestedAttributes = $value;
            } else {
                $nestedAttribute = $matches[3] ?? null;
//            $nestedAttributes = $matches[4] ?? [];
                $nestedAttributes = $nestedAttribute ? [$nestedAttribute] : [];
            }

            /*
             * Nested resource schema
             * Schema Syntax:
             * - param/schema
             */
            $nestedSchema = ltrim($matches[5] ?? '', '/');

            /**
             * If nested property is optional and not requested, skip it
             */
            if ($isOptional && !in_array($attribute, $this->withOptional)) {
                continue;
            }

            if (!$schema->has($attribute)) {
                throw new Exception("Property '$attribute' is not defined in the schema of resource " . get_class($this) . ".");
            }

            $result[$attribute] = $this->getProperty(
                $schema,
                $attribute,
                $nestedAttributes,
                $nestedSchema
            );
        }

        if ($getAllAttributes) {
            foreach (array_diff(array_keys($schema), $template) as $property) {
                $value = $schema[$property];
                if (is_callable($value)) {
                    $value = $value();
                }

                if (!$value instanceof SchemaResource && !$value instanceof SchemaResourceCollection) {
                    $result[$property] = $value;
                }
//                $data[$property] = $this->getProperty($schema, $property, null, null);
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function getProperty(Collection $schema, string $property, ?array $nestedAttributes, ?string $schemaType)
    {
        /*
         * Check if property is closure (lazy load)
         */
        if (is_callable($schema[$property])) {
            try {
                $value = $schema[$property]();
            } catch (ArgumentCountError $e) {
                // closure need to be without arguments
                throw new Exception("Property $property in resource " . get_class($this) . " is a closure and needs to be without arguments.");
            }
        } else {
            $value = $schema[$property];
        }

        if ($value instanceof SchemaResource) {
            if ($schemaType) {
                $value->useSchemaType($schemaType);
            }
            if ($nestedAttributes) {
                $value->withPartial($nestedAttributes);
            }
        } else if ($value instanceof SchemaResourceCollection) {
            if ($schemaType) {
                $value->schemaType = $schemaType;
            }
            if ($nestedAttributes) {
                $value->properties = $nestedAttributes;
            }
        }

        return $value;
    }

    /**
     * Create new anonymous collection resource.
     * @param mixed $resource
     * @param string|array|null $schemaType
     * @param array|null $properties
     * @param array $options
     * @param array|string $optional
     * @return SchemaResourceCollection
     */
    public static function collection($resource, string|array|null $schemaType = null, array $properties = null, array $options = [], array|string $optional = []): SchemaResourceCollection
    {
        return tap(static::newCollection($resource, $schemaType, $properties, $options, $optional), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    protected static function newCollection($resource, $schemaType = null, $properties = null, $options = [], array|string $optional = [])
    {
        return new SchemaResourceCollection($resource, static::class, $schemaType, $properties, $options, $optional);
    }
}
