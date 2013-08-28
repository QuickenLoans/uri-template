<?php
/**
 * @copyright ©2005—2013 Quicken Loans Inc. All rights reserved.
 */

namespace QL\UriTemplate;

use PHPUnit_Framework_TestCase;
use stdClass;

class ExpanderTest extends PHPUnit_Framework_TestCase
{
    private $vars = [
        'var' => 'value',
        'hello' => 'Hello World!',
        'half' => '50%',
        'empty' => '',
        'x' => 1024,
        'y' => 768,
        'list' => ['red', 'green', 'blue'],
        'keys' => ['semi' => ';', 'dot' => '.', 'comma' => ','],
        'path' => '/foo/bar',
        'semi' => ';',
        'year' => [1965, 2000, 2012],
        'dom' => ['example', 'com'],
        'some_empty' => ['first' => 'a', 'second' => '', 'third' => 'fun', 'fourth' => '', 'fifth' => 'time!'],
        'count' => ['one', 'two', 'three'],
        'dub' => 'me/too',
        'who' => 'fred',
        'base' => 'http://example.com/home/',
        'v' => 6,
        'empty_keys' => [],
        'drink' => '饮',
        'oneping' => 'Один пинга только пожалуйста.',
    ];

    /**
     * @covers QL\UriTemplate\Expander
     * @dataProvider successfulExpansions
     */
    public function testExpandSuccessful($tpl, $vars, $expected)
    {
        $expander = new Expander;
        $actual = $expander($tpl, $vars);
        $this->assertSame($expected, $actual);
    }

    /**
     * @covers QL\UriTemplate\Expander
     * @dataProvider failedExpansions
     */
    public function testExpandFailure($tpl, $vars, $expected, $error)
    {
        $expander = new Expander;
        $actual = $expander($tpl, $vars);
        $this->assertSame($error, $expander->lastError());
        $this->assertSame($expected, $actual);
    }

    /**
     * @covers QL\UriTemplate\Expander
     * @dataProvider failedVars
     */
    public function testInputVarsBadFormat($tpl, $vars, $expected, $error)
    {
        $expander = new Expander;
        $actual = $expander($tpl, $vars);
        $this->assertSame($expected, $actual);
        $this->assertSame($error, $expander->lastError());
    }

    public function failedVars()
    {
        return [
            ['/foo/{bar}', ['bar' => null], '/foo/', null],
            ['/foo/{bar}', ['bar' => true], '/foo/1', null],
            ['/foo/{bar}', ['bar' => false], '/foo/', null],
            ['/foo/{bar}', ['bar' => 4.25], '/foo/4.25', null],
            ['/foo/{bar}', ['bar' => new stdClass], '/foo/{bar}', 'Objects without a __toString() method are not allowed as variable values.'],
            ['/foo/{bar}', ['bar' => new ExpanderTest_Stringy], '/foo/i%20am%20string.%20FEAR%20ME.', null],
            ['/foo/{bar}', ['bar' => STDIN], '/foo/{bar}', 'Resources are not allowed as variable values.'],
            ['/foo/{bar}', ['bar' => ['baz' => null]], '/foo/baz,', null],
            ['/foo/{bar}', ['bar' => ['baz' => true]], '/foo/baz,1', null],
            ['/foo/{bar}', ['bar' => ['baz' => false]], '/foo/baz,', null],
            ['/foo/{bar}', ['bar' => ['baz' => 4.25]], '/foo/baz,4.25', null],
            ['/foo/{bar}', ['bar' => ['baz' => new stdClass]], '/foo/{bar}', 'Objects without a __toString() method are not allowed as variable values.'],
            ['/foo/{bar}', ['bar' => ['baz' => new ExpanderTest_Stringy]], '/foo/baz,i%20am%20string.%20FEAR%20ME.', null],
            ['/foo/{bar}', ['bar' => ['baz' => ['barf']]], '/foo/{bar}', 'Variable values may not be arrays that include other arrays as values.'],
            ['/foo/{bar}', ['bar' => ['baz' => STDIN]], '/foo/{bar}', 'Resources are not allowed as variable values.'],
        ];
    }

    public function failedExpansions()
    {
        return [
            [chr(0x80).chr(0x80), [], chr(0x80).chr(0x80), 'Input template not valid UTF-8'],
            ['/path {foo}', ['foo' => 'bar'], '/path {foo}', 'Invalid character at position 5: /path {foo}'],
            ['/path"{foo}', ['foo' => 'bar'], '/path"{foo}', 'Invalid character at position 5: /path"{foo}'],
            ['/path\'{foo}', ['foo' => 'bar'], '/path\'{foo}', 'Invalid character at position 5: /path\'{foo}'],
            ['/path<{foo}', ['foo' => 'bar'], '/path<{foo}', 'Invalid character at position 5: /path<{foo}'],
            ['/path>{foo}', ['foo' => 'bar'], '/path>{foo}', 'Invalid character at position 5: /path>{foo}'],
            ['/path\{foo}', ['foo' => 'bar'], '/path\{foo}', 'Invalid character at position 5: /path\{foo}'],
            ['/path^{foo}', ['foo' => 'bar'], '/path^{foo}', 'Invalid character at position 5: /path^{foo}'],
            ['/path`{foo}', ['foo' => 'bar'], '/path`{foo}', 'Invalid character at position 5: /path`{foo}'],
            ['/path|{foo}', ['foo' => 'bar'], '/path|{foo}', 'Invalid character at position 5: /path|{foo}'],
            ['/path%{foo}', ['foo' => 'bar'], '/path%{foo}', 'Invalid pct-encode at position 5: /path%{foo}'],
            ['/path%0h{foo}', ['foo' => 'bar'], '/path%0h{foo}', 'Invalid pct-encode at position 5: /path%0h{foo}'],
            ['/path{fo', ['foo' => 'bar'], '/path{fo', 'Unclosed expression at offset 5: /path{fo'],
            ['/path{var}/{}', $this->vars, '/pathvalue/{}', 'Empty expression at position 11: /path{var}/{}'],
            ['/path{%asdf}', $this->vars, '/path{%asdf}', 'Invalid operator at position 6: /path{%asdf}'],
            ['/path{df%as}', $this->vars, '/path{df%as}', 'Invalid expression at position 5: /path{df%as}'],
            ['/path{foo好*}', [], '/path{foo好*}', 'Invalid expression at position 5: /path{foo好*}'],
            ['/path/{var}/{%asdf}', $this->vars, '/path/value/{%asdf}', 'Invalid operator at position 13: /path/{var}/{%asdf}'],
            ['/path/{foo:asdf}', $this->vars, '/path/{foo:asdf}', 'Invalid expression at position 6: /path/{foo:asdf}'],
            ['/path/{foo:000}', $this->vars, '/path/{foo:000}', 'Invalid expression at position 6: /path/{foo:000}'],
            ['/path/{foo*d}', $this->vars, '/path/{foo*d}', 'Invalid expression at position 6: /path/{foo*d}'],
            ['/some/{@op}', $this->vars, '/some/{@op}', 'Invalid operator at position 7: /some/{@op}'],
            ['/some/{foo}/%', $this->vars, '/some//%', 'Invalid pct-encode at position 12: /some/{foo}/%'],
            ['/path/{var:}', $this->vars, '/path/{var:}', 'Invalid expression at position 6: /path/{var:}'],
            ['/path/{var:14159}', $this->vars, '/path/{var:14159}', 'Invalid expression at position 6: /path/{var:14159}'],
            ['/some/{%3}', $this->vars, '/some/{%3}', 'Invalid operator at position 7: /some/{%3}'],
        ];
    }

    public function successfulExpansions()
    {
        return [
            ['{var}', $this->vars, 'value'],
            ['{hello}', $this->vars, 'Hello%20World%21'],
            ['{+var}', $this->vars, 'value'],
            ['{+hello}', $this->vars, 'Hello%20World!'],
            ['{+path}/here', $this->vars, '/foo/bar/here'],
            ['here?ref={+path}', $this->vars, 'here?ref=/foo/bar'],
            ['X{#var}', $this->vars, 'X#value'],
            ['X{#hello}', $this->vars, 'X#Hello%20World!'],
            ['map?{x,y}', $this->vars, 'map?1024,768'],
            ['{x,hello,y}', $this->vars, '1024,Hello%20World%21,768'],
            ['{+x,hello,y}', $this->vars, '1024,Hello%20World!,768'],
            ['{+path,x}/here', $this->vars, '/foo/bar,1024/here'],
            ['{#x,hello,y}', $this->vars, '#1024,Hello%20World!,768'],
            ['{#path,x}/here', $this->vars, '#/foo/bar,1024/here'],
            ['X{.var}', $this->vars, 'X.value'],
            ['X{.x,y}', $this->vars, 'X.1024.768'],
            ['{/var}', $this->vars, '/value'],
            ['{/var,x}/here', $this->vars, '/value/1024/here'],
            ['{;x,y}', $this->vars, ';x=1024;y=768'],
            ['{;x,y,empty}', $this->vars, ';x=1024;y=768;empty'],
            ['{?x,y}', $this->vars, '?x=1024&y=768'],
            ['{?x,y,empty}', $this->vars, '?x=1024&y=768&empty='],
            ['?fixed=yes{&x}', $this->vars, '?fixed=yes&x=1024'],
            ['{&x,y,empty}', $this->vars, '&x=1024&y=768&empty='],
            ['{var:3}', $this->vars, 'val'],
            ['{var:30}', $this->vars, 'value'],
            ['{list}', $this->vars, 'red,green,blue'],
            ['{list*}', $this->vars, 'red,green,blue'],
            ['{semi}', $this->vars, '%3B'],
            ['{semi:2}', $this->vars, '%3B'],
            ['find{?year*}', $this->vars, 'find?year=1965&year=2000&year=2012'],
            ['www{.dom*}', $this->vars, 'www.example.com'],
            ['{count}', $this->vars, 'one,two,three'],
            ['{count*}', $this->vars, 'one,two,three'],
            ['{/count}', $this->vars, '/one,two,three'],
            ['{/count*}', $this->vars, '/one/two/three'],
            ['{;count}', $this->vars, ';count=one,two,three'],
            ['{;count*}', $this->vars, ';count=one;count=two;count=three'],
            ['{?count}', $this->vars, '?count=one,two,three'],
            ['{?count*}', $this->vars, '?count=one&count=two&count=three'],
            ['{&count*}', $this->vars, '&count=one&count=two&count=three'],
            ['{half}', $this->vars, '50%25'],
            ['O{empty}X', $this->vars, 'OX'],
            ['O{undef}X', $this->vars, 'OX'],
            ['{x,hello,y}', $this->vars, '1024,Hello%20World%21,768'],
            ['?{x,empty}', $this->vars, '?1024,'],
            ['?{x,undef}', $this->vars, '?1024'],
            ['?{undef,y}', $this->vars, '?768'],
            ['{keys}', $this->vars, 'semi,%3B,dot,.,comma,%2C'],
            ['{keys*}', $this->vars, 'semi=%3B,dot=.,comma=%2C'],
            ['{+half}', $this->vars, '50%25'],
            ['{base}index', $this->vars, 'http%3A%2F%2Fexample.com%2Fhome%2Findex'],
            ['{+base}index', $this->vars, 'http://example.com/home/index'],
            ['O{+empty}X', $this->vars, 'OX'],
            ['O{+undef}X', $this->vars, 'OX'],
            ['up{+path}{var}/here', $this->vars, 'up/foo/barvalue/here'],
            ['{+x,hello,y}', $this->vars, '1024,Hello%20World!,768'],
            ['{+path:6}/here', $this->vars, '/foo/b/here'],
            ['{+list}', $this->vars, 'red,green,blue'],
            ['{+list*}', $this->vars, 'red,green,blue'],
            ['{+keys}', $this->vars, 'semi,;,dot,.,comma,,'],
            ['{+keys*}', $this->vars, 'semi=;,dot=.,comma=,'],
            ['{#var}', $this->vars, '#value'],
            ['{#hello}', $this->vars, '#Hello%20World!'],
            ['{#half}', $this->vars, '#50%25'],
            ['foo{#empty}', $this->vars, 'foo#'],
            ['foo{#undef}', $this->vars, 'foo'],
            ['{#x,hello,y}', $this->vars, '#1024,Hello%20World!,768'],
            ['{#path,x}/here', $this->vars, '#/foo/bar,1024/here'],
            ['{#path:6}/here', $this->vars, '#/foo/b/here'],
            ['{#list}', $this->vars, '#red,green,blue'],
            ['{#list*}', $this->vars, '#red,green,blue'],
            ['{#keys}', $this->vars, '#semi,;,dot,.,comma,,'],
            ['{#keys*}', $this->vars, '#semi=;,dot=.,comma=,'],
            ['{.who}', $this->vars, '.fred'],
            ['{.who,who}', $this->vars, '.fred.fred'],
            ['{.half,who}', $this->vars, '.50%25.fred'],
            ['X{.var}', $this->vars, 'X.value'],
            ['X{.empty}', $this->vars, 'X.'],
            ['X{.undef}', $this->vars, 'X'],
            ['X{.var:3}', $this->vars, 'X.val'],
            ['X{.list}', $this->vars, 'X.red,green,blue'],
            ['X{.list*}', $this->vars, 'X.red.green.blue'],
            ['X{.keys}', $this->vars, 'X.semi,%3B,dot,.,comma,%2C'],
            ['X{.keys*}', $this->vars, 'X.semi=%3B.dot=..comma=%2C'],
            ['X{.empty_keys}', $this->vars, 'X'],
            ['X{.empty_keys*}', $this->vars, 'X'],
            ['{/who}', $this->vars, '/fred'],
            ['{/who,who}', $this->vars, '/fred/fred'],
            ['{/half,who}', $this->vars, '/50%25/fred'],
            ['{/who,dub}', $this->vars, '/fred/me%2Ftoo'],
            ['{/var}', $this->vars, '/value'],
            ['{/var,empty}', $this->vars, '/value/'],
            ['{/var,undef}', $this->vars, '/value'],
            ['{/var,x}/here', $this->vars, '/value/1024/here'],
            ['{/var:1,var}', $this->vars, '/v/value'],
            ['{/list}', $this->vars, '/red,green,blue'],
            ['{/list*}', $this->vars, '/red/green/blue'],
            ['{/list*,path:4}', $this->vars, '/red/green/blue/%2Ffoo'],
            ['{/keys}', $this->vars, '/semi,%3B,dot,.,comma,%2C'],
            ['{/keys*}', $this->vars, '/semi=%3B/dot=./comma=%2C'],
            ['{;who}', $this->vars, ';who=fred'],
            ['{;half}', $this->vars, ';half=50%25'],
            ['{;empty}', $this->vars, ';empty'],
            ['{;v,empty,who}', $this->vars, ';v=6;empty;who=fred'],
            ['{;v,bar,who}', $this->vars, ';v=6;who=fred'],
            ['{;x,y}', $this->vars, ';x=1024;y=768'],
            ['{;x,y,empty}', $this->vars, ';x=1024;y=768;empty'],
            ['{;x,y,undef}', $this->vars, ';x=1024;y=768'],
            ['{;hello:5}', $this->vars, ';hello=Hello'],
            ['{;list}', $this->vars, ';list=red,green,blue'],
            ['{;list*}', $this->vars, ';list=red;list=green;list=blue'],
            ['{;keys}', $this->vars, ';keys=semi,%3B,dot,.,comma,%2C'],
            ['{;keys*}', $this->vars, ';semi=%3B;dot=.;comma=%2C'],
            ['{?who}', $this->vars, '?who=fred'],
            ['{?half}', $this->vars, '?half=50%25'],
            ['{?x,y}', $this->vars, '?x=1024&y=768'],
            ['{?x,y,empty}', $this->vars, '?x=1024&y=768&empty='],
            ['{?x,y,undef}', $this->vars, '?x=1024&y=768'],
            ['{?var:3}', $this->vars, '?var=val'],
            ['{?list}', $this->vars, '?list=red,green,blue'],
            ['{?list*}', $this->vars, '?list=red&list=green&list=blue'],
            ['{?keys}', $this->vars, '?keys=semi,%3B,dot,.,comma,%2C'],
            ['{?keys*}', $this->vars, '?semi=%3B&dot=.&comma=%2C'],
            ['{&who}', $this->vars, '&who=fred'],
            ['{&half}', $this->vars, '&half=50%25'],
            ['?fixed=yes{&x}', $this->vars, '?fixed=yes&x=1024'],
            ['{&x,y,empty}', $this->vars, '&x=1024&y=768&empty='],
            ['{&x,y,undef}', $this->vars, '&x=1024&y=768'],
            ['{&var:3}', $this->vars, '&var=val'],
            ['{&list}', $this->vars, '&list=red,green,blue'],
            ['{&list*}', $this->vars, '&list=red&list=green&list=blue'],
            ['{&keys}', $this->vars, '&keys=semi,%3B,dot,.,comma,%2C'],
            ['{&keys*}', $this->vars, '&semi=%3B&dot=.&comma=%2C'],
            ['/my%20list/{id}', $this->vars, '/my%20list/'],
            ['/你好/{who}', $this->vars, '/你好/fred'],
            ['/{oneping:4}', $this->vars, '/%D0%9E%D0%B4%D0%B8%D0%BD'],
            ['/Вы-говорите-на/{var}/русском?', $this->vars, '/Вы-говорите-на/value/русском?'],
            ['/{var%20}', ['var%20' => 'awesome'], '/awesome'],
            ['/{%20var}', ['%20var' => 'awesome'], '/awesome'],
            ['/wut{?some_empty*}', $this->vars, '/wut?first=a&second=&third=fun&fourth=&fifth=time%21'],
            ['/authorize/{username}{?password}', ['username' => 'mnagi', 'password' => 'hunter2'], '/authorize/mnagi?password=hunter2'],
        ];
    }
}

class ExpanderTest_Stringy
{
    public function __toString()
    {
        return 'i am string. FEAR ME.';
    }
}
