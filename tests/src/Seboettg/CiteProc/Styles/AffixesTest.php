<?php

namespace Seboettg\CiteProc\Styles;


use Seboettg\CiteProc\TestSuiteTestCaseTrait;

class AffixesTest extends \PHPUnit_Framework_TestCase
{
    use TestSuiteTestCaseTrait;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testRenderTestSuite()
    {
        $this->_testRenderTestSuite('affix_');
    }
}