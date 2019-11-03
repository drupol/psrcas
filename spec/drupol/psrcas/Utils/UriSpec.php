<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Utils;

use drupol\psrcas\Utils\Uri;
use PhpSpec\ObjectBehavior;

class UriSpec extends ObjectBehavior
{
    public function it_can_check_if_some_query_parameters_exists()
    {
        $url = 'http://host/path?param1=param1&param2=param2#fragment';

        $uri = new \Nyholm\Psr7\Uri($url);

        $this::hasParams($uri, 'param1')
            ->shouldReturn(true);

        $this::hasParams($uri, 'param2')
            ->shouldReturn(true);

        $this::hasParams($uri, 'param3')
            ->shouldReturn(false);

        $this::hasParams($uri, 'param1', 'param2')
            ->shouldReturn(true);

        $this::hasParams($uri, 'param1', 'param2', 'param3')
            ->shouldReturn(false);
    }

    public function it_can_create_a_new_uri_with_a_new_param()
    {
        $url = 'http://host/path?param1=param1&param2=param2#fragment';

        $uri = new \Nyholm\Psr7\Uri($url);

        $this::withParam($uri, 'param3', 'param3')
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&param3=param3#fragment');

        $this::withParam($uri, 'param3', 'param3', true)
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&param3=param3#fragment');

        $this::withParam($uri, 'param3', 'param3', false)
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&param3=param3#fragment');

        $this::withParam($uri, 'param1', 'PARAM1')
            ->__toString()
            ->shouldReturn('http://host/path?param1=PARAM1&param2=param2#fragment');

        $this::withParam($uri, 'param1', 'PARAM1', true)
            ->__toString()
            ->shouldReturn('http://host/path?param1=PARAM1&param2=param2#fragment');

        $this::withParam($uri, 'param1', 'PARAM1', false)
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2#fragment');
    }

    public function it_can_create_a_new_uri_with_new_parameters()
    {
        $url = 'http://host/path?param1=param1&param2=param2#fragment';

        $uri = new \Nyholm\Psr7\Uri($url);

        $this::withParams($uri, ['param3' => 'param3', 'param4' => 'param4'])
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&param3=param3&param4=param4#fragment');

        $this::withParams($uri, ['param3' => 'param3', 'param4' => 'param4'], true)
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&param3=param3&param4=param4#fragment');

        $this::withParams($uri, ['param3' => 'param3', 'param4' => 'param4'], false)
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&param3=param3&param4=param4#fragment');

        $this::withParams($uri, ['param1' => 'PARAM1', 'param3' => 'param3', 'param4' => 'param4'])
            ->__toString()
            ->shouldReturn('http://host/path?param1=PARAM1&param2=param2&param3=param3&param4=param4#fragment');

        $this::withParams($uri, ['param1' => 'PARAM1', 'param3' => 'param3', 'param4' => 'param4'], true)
            ->__toString()
            ->shouldReturn('http://host/path?param1=PARAM1&param2=param2&param3=param3&param4=param4#fragment');

        $this::withParams($uri, ['param1' => 'PARAM1', 'param3' => 'param3', 'param4' => 'param4'], false)
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&param3=param3&param4=param4#fragment');
    }

    public function it_can_get_a_single_param_from_uri()
    {
        $url = 'http://host/path?param1=param1&param2=param2#fragment';

        $uri = new \Nyholm\Psr7\Uri($url);

        $this
            ->getParam($uri, 'param2', 'foo')
            ->shouldReturn('param2');

        $this
            ->getParam($uri, 'param3')
            ->shouldBeNull();

        $this
            ->getParam($uri, 'param3', 'foo')
            ->shouldReturn('foo');
    }

    public function it_can_get_query_parameters()
    {
        $url = 'http://host/path?param1=param1#fragment';

        $uri = new \Nyholm\Psr7\Uri($url);

        $this::getParams($uri)
            ->shouldReturn(['param1' => 'param1']);
    }

    public function it_can_remove_parameters()
    {
        $url = 'http://host/path?param1=param1&param2=param2#fragment';

        $uri = new \Nyholm\Psr7\Uri($url);

        $this::removeParams($uri, 'param1')
            ->__toString()
            ->shouldReturn('http://host/path?param2=param2#fragment');

        $this::removeParams($uri, 'param1', 'param2')
            ->__toString()
            ->shouldReturn('http://host/path#fragment');

        $this::removeParams($uri, 'param1', 'param2', 'param1', 'param2')
            ->__toString()
            ->shouldReturn('http://host/path#fragment');

        $this::removeParams($uri, 'param3', 'param2', 'param1')
            ->__toString()
            ->shouldReturn('http://host/path#fragment');
    }

    public function it_can_set_multiple_params_at_the_same_time()
    {
        $url = 'http://host/path?param1=param1&param2=param2#fragment';

        $uri = new \Nyholm\Psr7\Uri($url);

        $this
            ->withParams($uri, ['key' => 'value'])
            ->__toString()
            ->shouldReturn('http://host/path?param1=param1&param2=param2&key=value#fragment');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Uri::class);
    }
}
