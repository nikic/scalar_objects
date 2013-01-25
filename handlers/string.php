<?php

class StringHandler {
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

    /* This function has multiple prototypes:
     *
     * replace(array(string $from => string $to) $replacements)
     * replace(string $from, string $to)
     * replace(array(string) $from, array(string) $to)
     *
     * This function could maybe have an additional $limit parameter. But as PHP currently
     * has no internal functions to do that I won't implement it now.
     */
    public function replace($arg1, $arg2 = null) {
        if (null === $arg2) {
            return strtr($this, $arg1);
        } else {
            return str_replace($arg1, $arg2, $this);
        }
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
}
