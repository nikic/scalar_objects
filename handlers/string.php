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

    /* This function has multiple prototypes:
     *
     * replace(array(string $from => string $to) $replacements, int $limit = PHP_MAX_INT)
     * replace(string $from, string $to, int $limit = PHP_MAX_INT)
     * replace(string[] $from, string[] $to, int $limit = PHP_MAX_INT)
     */
    public function replace($from, $to = null, $limit = null) {
        if (is_array($from) && !is_array($to)) {
            $limit = $to;
            $to    = array_values($from);
            $from  = array_keys($from);
        }

        if (null !== $limit) {
            return $this->replaceWithLimit($from, $to, $limit);
        } else {
            return str_replace($from, $to, $this);
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

    protected function replaceWithLimit($from, $to, $limit) {
        $limit = (int) $limit;
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit has to be positive');
        }

        if (!is_array($from)) {
            return $this->replaceWithLimitSingle($this, $from, $to, $limit);
        }

        $str = $this;

        reset($to);
        foreach ($from as $fromStr) {
            $toStr = current($to);
            if (false !== $toStr) {
                next($to);
            } else {
                $toStr = '';
            }

            $str = $this->replaceWithLimitSingle($str, $fromStr, $toStr, $limit);
            if (0 === $limit) {
                return $str;
            }
        }

        return $str;
    }

    protected function replaceWithLimitSingle($str, $from, $to, &$limit) {
        $lenDiff = $to->length() - $from->length();
        $index = 0;

        while (false !== $index = $str->indexOf($from, $index)) {
            $str = $str->replaceSlice($to, $index, $to->length()); 
            $index += $lenDiff;

            if (0 === --$limit) {
                return $str;
            }
        }

        return $str;
    }
}
