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

    public function slice($offset, $length = null) {
        if (null === $length) {
            return substr($this, $offset);
        } else {
            return substr($this, $offset, $length);
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
}
