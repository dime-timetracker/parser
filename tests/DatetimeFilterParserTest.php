<?php

use Dime\Parser\DatetimeFilterParser;
use Moment\Moment;

class DatetimeFilterParserTest extends \PHPUnit_Framework_TestCase
{
    protected function assertEqualTimestamp($a, $b) {
        if ($a instanceof Moment) {
            $a = $a->format('Y-m-d H:i');
        }
        $this->assertInstanceOf('Moment\Moment', $b);
        $this->assertEquals($a, $b->format('Y-m-d H:i'));
    }

    public function setUp()
    {
        $this->parser = new DatetimeFilterParser(new Moment('2015-08-31 22:25'));
    }

    protected function beforeEach()
    {
        $this->parser->setResult(array());
    }

    public function testStartEndGivenByTime()
    {
        $input = '10:00-12:00';
        $result = $this->parser->run($input);
        $this->assertArrayHasKey('range', $result);
        $this->assertArrayHasKey('start', $result['range']);
        $this->assertArrayHasKey('stop', $result['range']);
        $this->assertEqualTimestamp('2015-08-31 10:00', $result['range']['start']);
        $this->assertEqualTimestamp('2015-08-31 12:00', $result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('', $output);
    }

    public function testStartEndGivenByHour()
    {
        $input = 'ab-ab 10-12';
        $result = $this->parser->run($input);
        $this->assertEqualTimestamp('2015-08-31 10:00', $result['range']['start']);
        $this->assertEqualTimestamp('2015-08-31 12:00', $result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('ab-ab', $output);
    }

    public function testStartGivenByTime()
    {
        $input = '10:00-';
        $result = $this->parser->run($input);
        $this->assertEqualTimestamp('2015-08-31 10:00', $result['range']['start']);
        $this->assertNull($result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('', $output);
    }

    public function testEndGivenByTime()
    {
        $input = '-12:00';
        $result = $this->parser->run($input);
        $this->assertNull($result['range']['start']);
        $this->assertEqualTimestamp('2015-08-31 12:00', $result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('', $output);
    }

    public function testStartGivenByHour()
    {
        $input = '10-';
        $result = $this->parser->run($input);
        $this->assertEqualTimestamp('2015-08-31 10:00', $result['range']['start']);
        $this->assertNull($result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('', $output);
    }

    public function testEndGivenByHour()
    {
        $input = '-12';
        $result = $this->parser->run($input);
        $this->assertNull($result['range']['start']);
        $this->assertEqualTimestamp('2015-08-31 12:00', $result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('', $output);
    }

    public function testEmpty()
    {
        $input = '';
        $result = $this->parser->run($input);
        $this->assertEmpty($result);
        $output = $this->parser->clean($input);
        $this->assertEquals('', $output);
    }

    public function testFilterLastMonth()
    {
        $input = 'I know what you did last month, bro';
        $result = $this->parser->run($input);
        $this->assertNotEmpty($result);
        $this->assertEqualTimestamp('2015-07-01 00:00', $result['range']['start']);
        $this->assertEqualTimestamp('2015-07-31 23:59', $result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('I know what you did , bro', $output);
    }

    public function testFilterYesterday()
    {
        $input = 'I know what you did yesterday, bro';
        $result = $this->parser->run($input);
        $this->assertNotEmpty($result);
        $this->assertEqualTimestamp('2015-08-30 00:00', $result['range']['start']);
        $this->assertEqualTimestamp('2015-08-30 23:59', $result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('I know what you did , bro', $output);
    }

    public function testFilterLastFiveWeeks()
    {
        $input = 'last 5 weeks';
        $result = $this->parser->run($input);
        $this->assertNotEmpty($result);
        $this->assertEqualTimestamp('2015-07-27 22:25', $result['range']['start']);
        $this->assertNull($result['range']['stop']);

        $output = $this->parser->clean($input);
        $this->assertEquals('', $output);
    }

    public function testFilterToday()
    {
        $input = 'I\'ll do that today after lunch. Or better tomorrow.';
        $result = $this->parser->run($input);
        $this->assertNotEmpty($result);
        $this->assertEqualTimestamp('2015-08-31 00:00', $result['range']['start']);
        $this->assertNull($result['range']['stop']);
        $output = $this->parser->clean($input);
        $this->assertEquals('I\'ll do that  after lunch. Or better tomorrow.', $output);
    }
}
