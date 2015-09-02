<?php

namespace Dime\Parser;

use Moment\Moment;

/**
 * a time range parser
 *
 * Example:
 * 10:00-12:00 => [start: "10:00", stop: "12:00"]
 * 10-12       => [start: "10:00", stop: "12:00"]
 * 10:00-      => [start: "10:00", stop: ""]
 * -12:00      => [start: "", stop: "12:00"]
 */
class DatetimeFilterParser extends AbstractParser
{
    protected $regex = array(
        '/(?P<start>\d+(?::\d+)?)\s*-\s*(?P<stop>\d+(?::\d+)?)/',
        '/((?P<start>\d+(?::\d+)?)\s*-)/',
        '/(-\s*(?P<stop>\d+(?::\d+)?))/'
    );
    protected $matches = array();
    protected $matched = false;
    protected $now;

    /**
     * Constructor allows to set current timestamp (for testing purposes)
     *
     * @param Moment $now
     */
    public function __construct($now=null)
    {
        if (is_null($now)) {
            $now = new Moment();
        }
        $this->now = $now;
    }

    protected function getKeywords()
    {
        if (!$this->now) {
            $this->now = new Moment();
        }
        $moment = $this->now;
        return [
            '/today/' => function($match) use ($moment) {
                return [
                    'start' => $moment->startOf('day'),
                    'stop' => null
                ];
            },
            '/yesterday/' => function ($match) use ($moment) {
                return [
                    'start' => $moment->cloning()->subtractDays(1)->startOf('day'),
                    'stop'  => $moment->cloning()->subtractDays(1)->endOf('day')
                ];
            },
            '/current (day|week|month|year)/' => function ($matches) use ($moment) {
                return [
                    'start' => $moment->startOf('week'),
                    'stop' => null
                ];
            },
            '/last (day|week|month|year)/' => function ($matches) use ($moment) {
                $unit = $matches[1];
                $subtract = 'subtract' . ucfirst($unit) . 's';
                return [
                    'start' => $moment->cloning()->$subtract(1)->startOf($unit),
                    'stop' => $moment->cloning()->$subtract(1)->endOf($unit)
                ];
            },
            '/last (\d*) (days|weeks|months|years)/' => function ($matches) use ($moment) {
                list ($match, $qty, $unit) = $matches;
                $subtract = 'subtract' . ucfirst($unit);
                return [
                    'start' => $moment->$subtract($qty)->startOf($unit),
                    'stop' => null
                ];
            }
        ];
    }

    protected function getMoment($input)
    {
        if (0 === strlen($input)) {
            return null;
        }
        if (strstr($input, ':') === false) {
            $input .= ':00';
        }
        list($hour, $minute) = explode(':', $input);

        return $this->now->cloning()->setHour($hour)->setMinute($minute);
    }

    public function clean($input)
    {
        if ($this->matched && isset($this->matches[0])) {
            $input = trim(str_replace($this->matches[0], '', $input));
        }

        return $input;
    }

    public function run($input)
    {
        for ($i = 0; $i < count($this->regex); $i++) {
            if (preg_match($this->regex[$i], $input, $this->matches)) {
                $start = isset($this->matches['start']) ? $this->matches['start'] : '';
                $stop = isset($this->matches['stop']) ? $this->matches['stop'] : '';

                if (!empty($start) || !empty($stop)) {
                    $this->matched = true;
                    $this->result['range'] = array(
                        'start' => $this->getMoment($start),
                        'stop' => $this->getMoment($stop)
                    );
                }

                break;
            }
        }
        if (!isset($this->result['range'])) {
            $keywords = $this->getKeywords();
            foreach ($keywords as $pattern=>$getRange) {
                if (preg_match($pattern, $input, $this->matches)) {
                    $this->matched = true;
                    $this->result['range'] = $getRange($this->matches);
                    break;
                }
            }
        }

        return $this->result;
    }
}
