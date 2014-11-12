<?php

namespace Dime\Parser;

class ActivityDescriptionParser extends AbstractParser
{
    protected $regex = '/([@:\/])(\w+)/';
    protected $matches = array();

    public function clean($input)
    {
        return '';
    }

    public function run($input)
    {
        $this->result['description'] = $input;

        return $this->result;
    }

}
