<?php
/**
 * @copyright ©2005—2013 Quicken Loans Inc. All rights reserved.
 */

namespace QL\UriTemplate;

use PHPUnit\Framework\TestCase;
use stdClass;

class UriTemplateTest extends TestCase
{
    /**
     * @covers QL\UriTemplate\UriTemplate
     * @expectedException \QL\UriTemplate\Exception
     */
    public function testConstructingBadUriTemplateThrowsException()
    {
        new UriTemplate('/foo/{bar');
    }

    /**
     * @covers QL\UriTemplate\UriTemplate
     */
    public function testExpandingWithBadVariablesThrowsException()
    {
        $expander = new Expander;

        $tpl = new UriTemplate('/foo/{mytpl}', $expander);

        $thrown = false;
        try {
            $tpl->expand(['mytpl' => new stdClass]);
        } catch (Exception $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    /**
     * @covers QL\UriTemplate\UriTemplate
     */
    public function testGoodExpansionOnUriTemplate()
    {
        $expander = new Expander;
        $tpl = new UriTemplate('/foo/{woot}', $expander);
        $actual = $tpl->expand(['woot' => 'bar']);

        $this->assertSame('/foo/bar', $actual);
    }
}
