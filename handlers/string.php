<?php

namespace str;

class Handler {
    public function length() {
        return strlen($this);
    }

    public function indexOf($string, $offset = 0) {
        return strpos($this, $string, $offset);
    }

    public function lastIndexOf($string, $offset = 0) {
        return strrpos($this, $string, $offset);
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
        if (null === $length) {
            return substr_count($this, $string, $offset);
        } else {
            return substr_count($this, $string, $offset, $length);
        }
    }

    public function slice($offset, $length = null) {
        if (null === $length) {
            return substr($this, $offset);
        } else {
            return substr($this, $offset, $length);
        }
    }

    public function replaceSlice($replacement, $offset, $length = null) {
        if (null === $length) {
            return substr_replace($this, $replacement, $offset);
        } else {
            return substr_replace($this, $replacement, $offset, $length);
        }
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

        $limit = $this->verifyLimit($limit);
        return $this->replaceWithLimit($this, $from, $to, $limit);
    }

    public function split($separator, $limit = PHP_INT_MAX) {
        return explode($separator, $this, $limit);
    }

    public function chunk($chunkLength = 1) {
        return str_split($this, $chunkLength);
    }

    public function repeat($times) {
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

    protected function verifyLimit($limit) {
        $limit = (int) $limit;
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit has to be positive');
        }
        return $limit;
    }

    protected function replacePairs($replacements, $limit) {
        if (null === $limit) {
            return strtr($this, $replacements);
        }

        $limit = $this->verifyLimit($limit);
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
