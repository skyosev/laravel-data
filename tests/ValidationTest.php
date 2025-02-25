<?php

namespace Spatie\LaravelData\Tests;

use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\WithoutValidation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Tests\Fakes\DummyBackedEnum;
use Spatie\LaravelData\Tests\Fakes\SimpleData;
use Spatie\LaravelData\Tests\Fakes\SimpleDataWithExplicitValidationRuleAttributeData;
use Spatie\LaravelData\Tests\TestSupport\DataValidationAsserter;

class ValidationTest extends TestCase
{
    /**
     * Simple properties
     */

    /** @test */
    public function it_can_validate_a_string()
    {
        $dataClass = new class () extends Data {
            public string $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['property' => 'Hello World'])
            ->assertRules([
                'property' => ['string', 'required'],
            ]);
    }

    /** @test */
    public function it_can_validate_a_float()
    {
        $dataClass = new class () extends Data {
            public float $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['property' => 10.0])
            ->assertRules([
                'property' => ['numeric', 'required'],
            ]);
    }

    /** @test */
    public function it_can_validate_an_integer()
    {
        $dataClass = new class () extends Data {
            public int $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['property' => 10.0])
            ->assertRules([
                'property' => ['numeric', 'required'],
            ]);
    }

    /** @test */
    public function it_can_validate_an_array()
    {
        $dataClass = new class () extends Data {
            public array $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['property' => ['Hello World']])
            ->assertRules([
                'property' => ['array', 'required'],
            ]);
    }


    /** @test */
    public function it_can_validate_a_bool()
    {
        $dataClass = new class () extends Data {
            public bool $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['property' => true])
            ->assertRules([
                'property' => ['boolean'],
            ]);
    }

    /** @test */
    public function it_can_validate_a_nullable_type()
    {
        $dataClass = new class () extends Data {
            public ?array $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['property' => ['Hello World']])
            ->assertOk(['property' => null])
            ->assertOk([])
            ->assertRules([
                'property' => ['array', 'nullable'],
            ]);
    }

    /** @test */
    public function it_can_validate_a_property_with_custom_rules()
    {
        $dataClass = new class () extends Data {
            public ?array $property;

            public static function rules(): array
            {
                return [
                    'property' => ['array', 'min:5'],
                ];
            }
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'property' => ['array', 'min:5'],
            ]);
    }

    /** @test */
    public function it_can_validate_a_property_with_custom_rules_as_string()
    {
        $dataClass = new class () extends Data {
            public ?array $property;

            public static function rules(): array
            {
                return [
                    'property' => 'array|min:5',
                ];
            }
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'property' => ['array', 'min:5'],
            ]);
    }

    /** @test */
    public function it_can_validate_a_property_with_custom_rules_as_object()
    {
        $dataClass = new class () extends Data {
            public ?array $property;

            public static function rules(): array
            {
                return [
                    'property' => [new ArrayType(), new Min(5)],
                ];
            }
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'property' => ['array', 'min:5'],
            ]);
    }

    /** @test */
    public function it_can_validate_a_property_with_attributes()
    {
        $dataClass = new class () extends Data {
            #[Min(5)]
            public ?array $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'property' => ['array', 'min:5', 'nullable'],
            ]);
    }

    /** @test */
    public function it_can_validate_an_optional_attribute()
    {
        DataValidationAsserter::for(new class () extends Data {
            public array|Optional $property;
        })
            ->assertOk([])
            ->assertOk(['property' => []])
            ->assertErrors(['property' => null])
            ->assertRules([
                'property' => ['sometimes', 'array'],
            ]);

        DataValidationAsserter::for(new class () extends Data {
            public array|Optional|null $property;
        })
            ->assertOk([])
            ->assertOk(['property' => []])
            ->assertOk(['property' => null])
            ->assertRules([
                'property' => ['sometimes', 'array', 'nullable'],
            ]);

        DataValidationAsserter::for(new class () extends Data {
            #[Max(10)]
            public array|Optional $property;
        })
            ->assertOk([])
            ->assertOk(['property' => []])
            ->assertErrors(['property' => null])
            ->assertRules([
                'property' => ['sometimes', 'array', 'max:10'],
            ]);
    }

    /** @test */
    public function it_can_validate_a_native_enum()
    {
        $dataClass = new class () extends Data {
            public DummyBackedEnum $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['property' => 'foo'])
            ->assertRules([
                'property' => [new Enum(DummyBackedEnum::class), 'required'],
            ]);
    }

    /** @test */
    public function it_will_use_name_mapping_within_validation()
    {
        $dataClass = new class () extends Data {
            #[MapInputName('some_property')]
            public string $property;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['some_property' => 'foo'])
            ->assertRules([
                'some_property' => ['string', 'required'],
            ]);
    }

    /** @test */
    public function it_can_disable_validation()
    {
        $dataClass = new class () extends Data {
            #[WithoutValidation]
            public string $property;

            #[DataCollectionOf(SimpleData::class), WithoutValidation]
            public DataCollection $collection;

            #[WithoutValidation]
            public SimpleData $data;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk([])
            ->assertRules([]);
    }

    /** @test */
    public function it_can_write_custom_rules_based_upon_payloads()
    {
        $dataClass = new class () extends Data {
            public bool $strict;

            public string $property;

            #[MapInputName(SnakeCaseMapper::class)]
            public string $mappedProperty;

            public static function rules(array $payload): array
            {
                if ($payload['strict'] === true) {
                    return [
                        'property' => ['in:strict'],
                        'mapped_property' => ['in:strict'],
                    ];
                }

                return [];
            }
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules(
                rules: [
                    'strict' => ['boolean'],
                    'property' => ['in:strict'],
                    'mapped_property' => ['in:strict'],
                ],
                payload: [
                    'strict' => true,
                ]
            )
            ->assertRules(
                rules: [
                    'strict' => ['boolean'],
                    'property' => ['string', 'required'],
                    'mapped_property' => ['string', 'required'],
                ],
                payload: [
                    'strict' => false,
                ]
            );
    }

    /**
     * Nested data
     */

    /** @test */
    public function it_can_validate_nested_data()
    {
        eval(<<<'PHP'
            use Spatie\LaravelData\Data;
            class NestedClassA extends Data {
                public string $name;
            }
        PHP);

        $dataClass = new class () extends Data {
            public \NestedClassA $nested;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['nested' => ['name' => 'Hello World']])
            ->assertErrors(['nested' => []])
            ->assertErrors(['nested' => null])
            ->assertRules([
                'nested' => ['required', 'array'],
                'nested.name' => ['string', 'required'],
            ]);
    }

    /** @test */
    public function it_can_validate_nested_nullable_data()
    {
        eval(<<<'PHP'
            use Spatie\LaravelData\Data;
            class NestedClassB extends Data {
                public string $name;
            }
        PHP);

        $dataClass = new class () extends Data {
            public ?\NestedClassB $nested;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['nested' => ['name' => 'Hello World']])
            ->assertOk(['nested' => ['name' => null]])
            ->assertOk(['nested' => null])
            ->assertOk(['nested' => []])
            ->assertRules([
                'nested' => ['nullable', 'array'],
                'nested.name' => ['nullable', 'string'],
            ]);
    }


    /** @test */
    public function it_can_validate_nested_optional_data()
    {
        $this->markTestIncomplete('Failures');

        eval(<<<'PHP'
            use Spatie\LaravelData\Data;
            class NestedClassC extends Data {
                public string $name;
            }
        PHP);

        $dataClass = new class () extends Data {
            public \NestedClassC|Optional $nested;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['nested' => ['name' => 'Hello World']])
            ->assertOk(['nested' => null])
            ->assertErrors(['nested' => ['name' => null]])
            ->assertErrors(['nested' => []])
            ->assertRules([
                'nested' => ['sometimes', 'array'],
                'nested.name' => ['nullable', 'string'],
            ]);
    }

    /** @test */
    public function it_can_add_additional_rules_to_nested_data()
    {
        eval(<<<'PHP'
            use Spatie\LaravelData\Attributes\Validation\In;use Spatie\LaravelData\Data;
            class NestedClassD extends Data {
                public string $name;
            }
        PHP);

        $dataClass = new class () extends Data {
            #[Min(100)]
            public \NestedClassD|Optional $nested;
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'nested' => ['sometimes', 'array', 'min:100'],
                'nested.name' => ['nullable', 'string'],
            ]);
    }

    /** @test */
    public function it_will_use_name_mapping_with_nested_objects()
    {
        $dataClass = new class () extends Data {
            #[MapInputName('some_nested')]
            public SimpleData $nested;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk(['some_nested' => ['string' => 'Hello World']])
            ->assertRules([
                'some_nested' => ['required', 'array'],
                'some_nested.string' => ['string', 'required'],
            ]);
    }

    /** @test */
    public function it_can_use_nested_payloads_in_nested_data()
    {
        $this->markTestIncomplete('Implementation required');

        // Also implement for collections -> complicated we would have to create rules for each individual payload

        eval(<<<'PHP'
            use Spatie\LaravelData\Attributes\Validation\In;use Spatie\LaravelData\Data;
            class NestedClassF extends Data {
                public bool $strict;

                public string $string;

                public static function rules(array $payload) : array{
                    // Maybe introduce parameter as $nestedPayload?

                    if($payload['strict']){
                        return ['name' => ['in:strict']];
                    }
                }
            }
        PHP);

        $dataClass = new class () extends Data {
            public \NestedClassF $nested;
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules(
                rules: [
                    'some_nested' => ['required', 'array'],
                    'some_nested.strict' => ['boolean'],
                    'some_nested.string' => ['in:strict'],
                ],
                payload: [
                    'some_nested.strict' => true,
                ]
            )
            ->assertRules(
                rules: [
                    'some_nested' => ['required', 'array'],
                    'some_nested.strict' => ['boolean'],
                    'some_nested.string' => ['required', 'string'],
                ],
                payload: [
                    'some_nested.strict' => false,
                ]
            );
    }

    /** @test */
    public function rules_in_nested_data_are_rewritten_according_to_their_fields()
    {
        $this->markTestIncomplete('Feature to add');

        // Should we do the same with the `rules` method?
        // Also implement for collections
        eval(<<<'PHP'
            use Spatie\LaravelData\Attributes\Validation\In;
            use Spatie\LaravelData\Attributes\Validation\RequiredIf;use Spatie\LaravelData\Attributes\Validation\RequiredWith;use Spatie\LaravelData\Data;
            class NestedClassG extends Data {
                public bool $alsoAString;

                #[RequiredIf('alsoAString', true)]
                public string $string;
            }
        PHP);

        $dataClass = new class () extends Data {
            public \NestedClassG $nested;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk([
                'nested' => ['alsoAString' => '0'],
            ])
            ->assertErrors([
                'nested' => ['alsoAString' => '1'],
            ]) // Fails when we prefix the rule with nested.
            ->assertRules(
                rules: [
                    'nested' => ['required', 'array'],
                    'nested.alsoAString' => ['boolean'],
                    'nested.string' => ['required_if:alsoAString,1', 'string'],
                ]
            );
    }

    /**
     * Collections
     */

    /** @test */
    public function it_will_validate_a_collection()
    {
        $dataClass = new class () extends Data {
            #[DataCollectionOf(SimpleData::class)]
            public DataCollection $collection;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk([
                'collection' => [
                    ['string' => 'Never Gonna'],
                    ['string' => 'Give You Up'],
                ],
            ])
            ->assertOk(['collection' => []])
            ->assertErrors(['collection' => null])
            ->assertErrors([])
            ->assertErrors([
                'collection' => [
                    ['other_string' => 'Hello World'],
                ],
            ])
            ->assertRules([
                'collection' => ['present', 'array'],
                'collection.*.string' => ['string', 'required'],
            ]);
    }

    /** @test */
    public function it_will_validate_a_nullable_collection()
    {
        $dataClass = new class () extends Data {
            #[DataCollectionOf(SimpleData::class)]
            public ?DataCollection $collection;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk([
                'collection' => [
                    ['string' => 'Never Gonna'],
                    ['string' => 'Give You Up'],
                ],
            ])
            ->assertOk(['collection' => []])
            ->assertOk(['collection' => null])
            ->assertOk([])
            ->assertErrors([
                'collection' => [
                    ['other_string' => 'Hello World'],
                ],
            ])
            ->assertRules([
                'collection' => ['nullable', 'array'],
                'collection.*.string' => ['string', 'required'],
            ]);
    }

    /** @test */
    public function it_will_validate_an_optional_collection()
    {
        $dataClass = new class () extends Data {
            #[DataCollectionOf(SimpleData::class)]
            public Optional|DataCollection $collection;
        };

        DataValidationAsserter::for($dataClass)
            ->assertOk([
                'collection' => [
                    ['string' => 'Never Gonna'],
                    ['string' => 'Give You Up'],
                ],
            ])
            ->assertOk(['collection' => []])
            ->assertOk([])
            ->assertErrors(['collection' => null])
            ->assertErrors([
                'collection' => [
                    ['other_string' => 'Hello World'],
                ],
            ])
            ->assertRules([
                'collection' => ['sometimes', 'array'],
                'collection.*.string' => ['string', 'required'],
            ]);
    }

    /** @test */
    public function it_can_overwrite_collection_class_rules()
    {
        $dataClass = new class () extends Data {
            #[DataCollectionOf(SimpleData::class)]
            public DataCollection $collection;

            public static function rules(): array
            {
                return [
                    'collection' => ['array', 'min:1', 'max:5'],
                    'collection.*.string' => ['required', 'string', 'min:100'],
                ];
            }
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'collection' => ['array', 'min:1', 'max:5'],
                'collection.*.string' => ['required', 'string', 'min:100'],
            ]);
    }

    /** @test */
    public function it_can_add_collection_class_rules_using_attributes()
    {
        $dataClass = new class () extends Data {
            #[DataCollectionOf(SimpleDataWithExplicitValidationRuleAttributeData::class)]
            #[Min(10)]
            public DataCollection $collection;
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'collection' => ['present', 'array', 'min:10'],
                'collection.*.email' => ['string', 'required', 'email:rfc'],
            ]);
    }

    /**
     * Complex Examples
     */

    /** @test */
    public function it_can_nest_data_in_collections()
    {
        eval(<<<'PHP'
            use Spatie\LaravelData\Data;
            class NestedClassE extends Data {
                public string $string;
            }

            class CollectionClassA extends Data {
                public \NestedClassE $nested;
            }
        PHP);

        $dataClass = new class () extends Data {
            #[DataCollectionOf(\CollectionClassA::class)]
            public DataCollection $collection;
        };

        DataValidationAsserter::for($dataClass)
            ->assertRules([
                'collection' => ['present', 'array'],
                'collection.*.nested' => ['required', 'array'],
                'collection.*.nested.string' => ['required', 'string'],
            ])
            ->assertOk(['collection' => [['nested' => ['string' => 'Hello World']]]]);
    }
}
