<?php

namespace str;

class Handler {
    public function length($self) {
        return strlen($self);
    }

    /*
     * Slicing methods
     */

    public function slice($self, $offset, $length = null) {
        $offset = $this->prepareOffset($self, $offset);
        $length = $this->prepareLength($self, $offset, $length);

        if (0 === $length) {
            return '';
        }

        return substr($self, $offset, $length);
    }

    public function replaceSlice($self, $replacement, $offset, $length = null) {
        $offset = $this->prepareOffset($self, $offset);
        $length = $this->prepareLength($self, $offset, $length);

        return substr_replace($self, $replacement, $offset, $length);
    }

    /*
     * Search methods
     */

    public function indexOf($self, $string, $offset = 0) {
        $offset = $this->prepareOffset($self, $offset);

        if ('' === $string) {
            return $offset;
        }

        return strpos($self, $string, $offset);
    }

    public function lastIndexOf($self, $string, $offset = null) {
        if (null === $offset) {
            $offset = $this->length($self);
        } else {
            $offset = $this->prepareOffset($self, $offset);
        }

        if ('' === $string) {
            return $offset;
        }

        /* Converts $offset to a negative offset as strrpos has a different
         * behavior for positive offsets. */
        return strrpos($self, $string, $offset - $this->length($self));
    }

    public function contains($self, $string) {
        return false !== $this->indexOf($self, $string);
    }

    public function startsWith($self, $string) {
        return 0 === $this->indexOf($self, $string);
    }

    public function endsWith($self, $string) {
        return $this->lastIndexOf($self, $string) === $this->length($self) - $string->length();
    }

    public function count($self, $string, $offset = 0, $length = null) {
        $offset = $this->prepareOffset($self, $offset);
        $length = $this->prepareLength($self, $offset, $length);

        if ('' === $string) {
            return $length + 1;
        }

        return substr_count($self, $string, $offset, $length);
    }

    /* This function has two prototypes:
     *
     * replace(array(string $from => string $to) $replacements, int $limit = PHP_MAX_INT)
     * replace(string $from, string $to, int $limit = PHP_MAX_INT)
     */
    public function replace($self, $from, $to = null, $limit = null) {
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
                return strtr($self, $from);
            } else {
                $this->verifyPositive($limit, 'Limit');
                return $this->replaceWithLimit($self, $replacements, $limit);
            }
        } else {
            $this->verifyNotEmptyString($from, 'From string');

            if (null === $limit) {
                return str_replace($from, $to, $self);
            } else {
                $this->verifyPositive($limit, 'Limit');
                return $this->replaceWithLimit($self, [$from => $to], $limit);
            }
        }
    }

    public function split($self, $separator, $limit = PHP_INT_MAX) {
        return explode($separator, $self, $limit);
    }

    public function chunk($self, $chunkLength = 1) {
        $this->verifyPositive($chunkLength, 'Chunk length');
        return str_split($self, $chunkLength);
    }

    public function repeat($self, $times) {
        $this->verifyNotNegative($times, 'Number of repetitions');
        return str_repeat($self, $times);
    }

    public function reverse($self) {
        return strrev($self);
    }

    public function toLower($self) {
        return strtolower($self);
    }

    public function toUpper($self) {
        return strtoupper($self);
    }

    public function trim($self, $characters = " \t\n\r\v\0") {
        return trim($self, $characters);
    }

    public function trimLeft($self, $characters = " \t\n\r\v\0") {
        return ltrim($self, $characters);
    }

    public function trimRight($self, $characters = " \t\n\r\v\0") {
        return rtrim($self, $characters);
    }

    public function padLeft($self, $length, $padString = " ") {
        return str_pad($self, $length, $padString, STR_PAD_LEFT);
    }

    public function padRight($self, $length, $padString = " ") {
        return str_pad($self, $length, $padString, STR_PAD_RIGHT);
    }

    protected function prepareOffset($self, $offset) {
        $len = $this->length($self);
        if ($offset < -$len || $offset > $len) {
            throw new \InvalidArgumentException('Offset must be in range [-len, len]');
        }

        if ($offset < 0) {
            $offset += $len;
        }

        return $offset;
    }

    protected function prepareLength($self, $offset, $length) {
        if (null === $length) {
            return $this->length($self) - $offset;
        }
        
        if ($length < 0) {
            $length += $this->length($self) - $offset;

            if ($length < 0) {
                throw new \InvalidArgumentException('Length too small');
            }
        } else {
            if ($offset + $length > $this->length($self)) {
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
