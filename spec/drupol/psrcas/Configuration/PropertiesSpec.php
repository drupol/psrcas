<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Configuration;

use drupol\psrcas\Configuration\Properties;
use PhpSpec\ObjectBehavior;
use spec\drupol\psrcas\Cas;

class PropertiesSpec extends ObjectBehavior
{
    public function it_can_be_used_as_an_array()
    {
        $properties = Cas::getTestProperties();

        $this->beConstructedWith($properties->all());

        $this
            ->offsetGet('base_url')
            ->shouldReturn('http://local/cas');

        $this
            ->offsetSet('foo', 'bar');

        $this
            ->offsetUnset('base_url');
    }

    public function it_can_modify_the_configuration()
    {
        $properties = [
            'foo' => 'bar',
            'protocol' => [
                'test' => [
                ],
            ],
        ];

        $this->beConstructedWith($properties);

        $this
            ->all()
            ->shouldReturn([
                'foo' => 'bar',
                'protocol' => [
                    'test' => [
                        'default_parameters' => [],
                    ],
                ],
            ]);
    }

    public function it_is_initializable()
    {
        $properties = (array) Cas::getTestProperties();

        $this->beConstructedWith($properties);

        $this->shouldHaveType(Properties::class);
    }
}
