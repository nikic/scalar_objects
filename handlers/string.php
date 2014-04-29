<?php

namespace str;

class Handler {
    public function length() {
        return strlen($this);
    }

    /*
     * Slicing methods
     */

    public function slice($offset, $length = null) {
        $offset = $this->prepareOffset($offset);
        $length = $this->prepareLength($offset, $length);

        if (0 === $length) {
            return '';
        }

        return substr($this, $offset, $length);
    }

    public function replaceSlice($replacement, $offset, $length = null) {
        $offset = $this->prepareOffset($offset);
        $length = $this->prepareLength($offset, $length);

        return substr_replace($this, $replacement, $offset, $length);
    }

    /*
     * Search methods
     */

    public function indexOf($string, $offset = 0) {
        $offset = $this->prepareOffset($offset);

        if ('' === $string) {
            return $offset;
        }

        return strpos($this, $string, $offset);
    }

    public function lastIndexOf($string, $offset = null) {
        if (null === $offset) {
            $offset = $this->length();
        } else {
            $offset = $this->prepareOffset($offset);
        }

        if ('' === $string) {
            return $offset;
        }

        /* Converts $offset to a negative offset as strrpos has a different
         * behavior for positive offsets. */
        return strrpos($this, $string, $offset - $this->length());
    }

    public function contains($string) {
        return false !== $this->indexOf($string);
    }

    public function startsWith($string) {
        return 0 === $this->indexOf($string);
    }

    public function endsWith($string) {
        return $this->lastIndexOf($string) === $this->length() - $string->length();
    }

    public function count($string, $offset = 0, $length = null) {
        $offset = $this->prepareOffset($offset);
        $length = $this->prepareLength($offset, $length);

        if ('' === $string) {
            return $length + 1;
        }

        return substr_count($this, $string, $offset, $length);
    }

    /* This function has two prototypes:
     *
     * replace(array(string $from => string $to) $replacements, int $limit = PHP_MAX_INT)
     * replace(string $from, string $to, int $limit = PHP_MAX_INT)
     */
    public function replace($from, $to = null, $limit = null) {
        if (is_array($from)) {
            $replacements = $from;
            $limit = $to;

            $this->verifyNotContainsEmptyString(
                array_keys($replacements), 'Replacement array keys'
            );

            // strtr() with an empty replacements array will crash in some PHP versions
            if (empty($replacements)) {
                return $this;
            }

            if (null === $limit) {
                return strtr($this, $from);
            } else {
                $this->verifyPositive($limit, 'Limit');
                return $this->replaceWithLimit($this, $replacements, $limit);
            }
        } else {
            $this->verifyNotEmptyString($from, 'From string');

            if (null === $limit) {
                return str_replace($from, $to, $this);
            } else {
                $this->verifyPositive($limit, 'Limit');
                return $this->replaceWithLimit($this, [$from => $to], $limit);
            }
        }
    }

    public function split($separator, $limit = PHP_INT_MAX) {
        return explode($separator, $this, $limit);
    }

    public function chunk($chunkLength = 1) {
        $this->verifyPositive($chunkLength, 'Chunk length');
        return str_split($this, $chunkLength);
    }

    public function repeat($times) {
        $this->verifyNotNegative($times, 'Number of repetitions');
        return str_repeat($this, $times);
    }

    public function reverse() {
        return strrev($this);
    }

    public function toLower() {
        return strtolower($this);
    }

    public function toUpper() {
        return strtoupper($this);
    }

    public function trim($characters = " \t\n\r\v\0") {
        return trim($this, $characters);
    }

    public function trimLeft($characters = " \t\n\r\v\0") {
        return ltrim($this, $characters);
    }

    public function trimRight($characters = " \t\n\r\v\0") {
        return rtrim($this, $characters);
    }

    public function padLeft($length, $padString = " ") {
        return str_pad($this, $length, $padString, STR_PAD_LEFT);
    }

    public function padRight($length, $padString = " ") {
        return str_pad($this, $length, $padString, STR_PAD_RIGHT);
    }

    protected function prepareOffset($offset) {
        $len = $this->length();
        if ($offset < -$len || $offset > $len) {
            throw new \InvalidArgumentException('Offset must be in range [-len, len]');
        }

        if ($offset < 0) {
            $offset += $len;
        }

        return $offset;
    }

    protected function prepareLength($offset, $length) {
        if (null === $length) {
            return $this->length() - $offset;
        }
        
        if ($length < 0) {
            $length += $this->length() - $offset;

            if ($length < 0) {
                throw new \InvalidArgumentException('Length too small');
            }
        } else {
            if ($offset + $length > $this->length()) {
                throw new \InvalidArgumentException('Length too large');
            }
        }

        return $length;
    }

    protected function verifyPositive($value, $name) {
        if ($value <= 0) {
            throw new \InvalidArgumentException("$name has to be positive");
        }
    }

    protected function verifyNotNegative($value, $name) {
        if ($value < 0) {
            throw new \InvalidArgumentException("$name can not be negative");
        }
    }

    protected function verifyNotEmptyString($value, $name) {
        if ((string) $value === '') {
            throw new \InvalidArgumentException("$name can not be an empty string");
        }
    }

    protected function verifyNotContainsEmptyString(array $array, $name) {
        foreach ($array as $value) {
            if ((string) $value === '') {
                throw new \InvalidArgumentException("$name can not contain an empty string");
            }
        }
    }

    /* This effectively implements strtr with a limit */
    protected function replaceWithLimit($str, array $replacements, $limit) {
        if (empty($replacements)) {
            return $str;
        }

        $this->sortKeysByStringLength($replacements);
        $regex = $this->createFromStringRegex($replacements);

        return preg_replace_callback($regex, function($matches) use($replacements) {
            return $replacements[$matches[0]];
        }, $str, $limit);
    }

    protected function sortKeysByStringLength(array &$array) {
        uksort($array, function($str1, $str2) {
            return $str2->length() - $str1->length();
        });
    }

    protected function createFromStringRegex(array $replacements) {
        $fromRegexes = [];
        foreach ($replacements as $from => $_) {
            $fromRegexes[] = preg_quote($from, '~');
        }

        return '~(?:' . implode('|', $fromRegexes) . ')~S';
    }
}
