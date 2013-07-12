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
            return $this->replacePairs($from, $to);
        }

        if (null === $limit) {
            return str_replace($from, $to, $this);
        }

        $this->verifyPositive($limit, 'Limit');
        return $this->replaceWithLimit($this, $from, $to, $limit);
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

    protected function replacePairs($replacements, $limit) {
        if (null === $limit) {
            return strtr($this, $replacements);
        }

        $this->verifyPositive($limit, 'Limit');
        $str = $this;
        foreach ($replacements as $from => $to) {
            $str = $this->replaceWithLimit($str, $from, $to, $limit);
            if (0 === $limit) {
                break;
            }
        }
        return $str;
    }

    protected function replaceWithLimit($str, $from, $to, &$limit) {
        $lenDiff = $to->length() - $from->length();
        $index = 0;

        while (false !== $index = $str->indexOf($from, $index)) {
            $str = $str->replaceSlice($to, $index, $to->length()); 
            $index += $lenDiff;

            if (0 === --$limit) {
                break;
            }
        }

        return $str;
    }
}
