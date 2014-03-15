String API
==========

This document outlines the design descisions behind the string API. Note that the APIs (and the
considerations behind them) are work in progress. These are just ideas.

General considerations
----------------------

First of all a few general considerations on the design of the string API (and the APIs in
general). PHP has a lot of [string functions][string_funcs] and even more functions accepting
strings as various parameters. The API of the String type can not and should not handle all
existing functions.

There are several approaches to API design as to which and how many methods should be exposed.
On one hand there is the "minimal interface" that tries to cover the most important use cases
with the smallest possible API. On the other hand is what Rubyists call the "humane interface",
that tries to cover as many use cases as possible with a very large API. For example Ruby's
string type has more than 100 methods (not counting countless overloads of those methods).

There is some disagreement about what the better approach it. I personally think it's better to
have a smaller (yet convenient) API and this is what I will try to go for in this API design.

Scope of the API
----------------

Related to the general considerations above is the scope that the string API should cover: 
There are many functions that can be clearly classified as string functions, for example
`strlen` and `substr`. Others also work on strings, but they aren't "string functions" in the
narrow sense of the phrase. Examples include `htmlspecialchars`, `preg_match`, etc. Those
functions do work on strings, but in my eyes those really are separate concerns. Handling HTML
is obviously rather important in PHP, but it's not something inherent to the string type.

Drawing the line for what belongs in the API and what doesn't it pretty hard. In particular
I find it fairly hard to classify regular expressions. Regexes are very close to string handling
and something like `$str->match()` could be quite handy to use.

Unicode support
---------------

For the purpose of this API design I will ignore Unicode support, at least for the time being.
The introduction of object APIs for primitives might be a good time to reconsider Unicode support,
but this is a very complicated issue that I don't want to deal with at this point.

As such any reference to lengths and indices refers to byte-lengths and byte-offsets.

API overview
------------

    string {
        /* Information retrieval methods */

        int length()

        /* Slicing methods */

        string slice(int $offset, int $length = null)
        string replaceSlice(string $replacement, int $offset, int $length = null)

        /* Search methods */

        int|false indexOf(string $string, int $offset = 0)
        int|false lastIndexOf(string $string, int $offset = null)
        bool contains(string $string)
        bool startsWith(string $string)
        bool endsWith(string $string)
        int count(string $string, int $offset = 0, int $length = null)

        /* Unclassified */

        string[] chunk(int $chunkLength = 1)
        string repeat(int $times)
    }

Shared functionality
--------------------

The following defines a number of functions providing functionality that is shared between several
of the methods. These functions are **NOT** part of the public API. An implementation is **NOT**
required to make use of them, they exist merely to remove repetition from the specification.

    int _prepareOffset(int $offset)

Let `$realOffset = $offset >= 0 ? $offset : $offset + $this->length()`.

If `$offset < 0` or `$offset > $this->length()` throw `InvalidArgumentException`.

Return `$realOffset`.

> Notes:
>
 * The error condition for this function may be reformulated as: Throws `InvalidArgumentException`
   if `$offset > $this->length()` or `$offset < -$this->length()`.

---

    int _prepareLength(int $realOffset, int|null $length)

If `$length === null` return ` $this->length() - $realOffset`.

Let `$realLength = $length >= 0 ? $length : $this->length() + $length - $realOffset`.

If `$realLength < 0` or `$realOffset + $realLength > $this->length()` throw
`InvalidArgumentException`.

Return `$realLength`.

Individual methods
------------------

Note on terminology: The string that the method will be called on will be referred to as the
"main string" in the following to distinguish it from any strings passed to the methods.

### Information retrieval methods

    int length()

Return the length in bytes of the main string. The length does not include the terminating
NUL byte.

### Slicing methods

    string slice(int $offset, int $length = null)

Let `$realOffset = _prepareOffset($offset)`
and `$realLength = _prepareLength($realOffset, $length)`.

Return the substring starting at `$realOffset` and having length `$realLength`.

> Notes:
>
 * This method corresponds to `substr`.
 * The method is more strict regarding the `$offset` and `$length` parameters. If you called
   `strpos` with an (effective) offset smaller than 0, then 0 was assumed. If `$offset + $length`
   exceeded the main string length, it would clip at the string length. The `slice()` method on the
   other hand will throw an exception in these cases. Incorrect parameters are algorithmic mistakes
   and should not be silently ignored.
 * Taking `substr($str, strlen($str))` returned false, whereas `$str->slice($str->length())` will
   return the empty string. The old behavior seems like a bug.
 * An alternative name for this method would be `subString()`. I chose `slice()` because it works
   analogeous to the array `slice()` method.

---

    string replaceSlice(string $replacement, int $offset, $length = null)

Let `$realOffset = _prepareOffset($offset)`
and `$realLength = _prepareLength($realOffset, $length)`.

Return a new string, which is the main string with the substring starting at `$realOffset` and
having length `$realLength` replaced by `$replacement`.

> Notes:
>
 * This method corresponds to `substr_replace`.
 * I considered also having a method `deleteSlice()`, but this seems to be a rather rare case, so
   `replaceSlice("", ...)` should be sufficient.
 * Apart from this the notes from `slice()` apply.

### Search methods

The following methods are used to find occurances of some string in the main string. The following
applies to all methods in this category:

An occurance of `str` in the main string `this_str` is an index `pos` such that `pos >= 0` and
`pos <= this_str->length() - str->length()` and `memcmp(this_str + pos, str, str->length()) == 0`.
In particular, note that the empty string `""` occurs at every index of the main string.

	int|false indexOf(string $string, int $offset = 0)

Let `$realOffset = _prepareOffset($offset)`.

Return the index `$pos` of the first occurance of `$string` in the main string, such that
`$pos >= $realOffset`. If no such occurance exists return `false`.

> Notes:
>
 * This method corresponds to `strpos`.
 * Many languages choose to return `-1` in case the string was not found. In absence of type
   restrictions, I see no pertinent reason for doing so and stick with the usual `false` return
   value. Further the `indexOf()` method for arrays can not return `-1` as it is a valid array
   key. Returning `false` here ensures both methods can stay consistent.
 * This function could accept a length additionally to the offset. It seems like most languages
   leave this off though. Probably because the only purpose of the offset here is to allow
   looping through all occurances of a string.
 * Other names for this method are `index` and `find`. I think `indexOf` describes the function
   best.

---

	int|false lastIndexOf(string $string, int $offset = null)

If `$offset === null` let `$realOffset = $this->length()`. Otherwise
let `$realOffset = _prepareOffset($offset)`.

Return the index `$pos` of the last occurance of `$string` in the main string, such that
`$pos <= $realOffset`. If no such occurance exists return `false`.

> Notes:
>
 * This function corresponds to `strrpos`.
 * The meaning of the `$offset` parameter differs from the `strrpos` function. It now always
   specified the *last* valid starting position for the occurance. With `strrpos` it specified the
   *first* valid position for a positive offset and the *last* for a negative offset. This behavior
   is confusing, inconsistent with what other languages do and makes it somewhat unintuitive to
   loop through all occurances of a string (in reverse order).
 * Different languages assign a different meaning to the offset. Some use the condition
   `$pos <= $offset`, for others `$pos + $string->length() - 1 <= $offset`. In words, some languages
   use `$offset` as the limit for the first character of the occurance, while others use it as the
   limit for the last character. I think both behaviors are equally sound. The choice of
   `$pos <= $offset` here is mainly because this is what `strrpos` was using.
 * Apart from this, the notes from `indexOf()` apply.

---

	bool contains(string $string)

Return `true` if `$string` occurs in the main string, `false` otherwise.

	bool startsWith(string $string)

Return `true` if `$string` occurs at position `0` in the main string, `false` otherwise.

	bool endsWith(string $string)

Return `true` if `$string` occurs at position `$this->length() - $string->length()` in the main
string, `false` otherwise.

> Notes:
>
 * The `contains`, `startsWith` and `endsWith` methods are rather simple, but occur often in
   practical usage and are as such included.
 * Every string contains, starts with and ends with the empty string.

---

    int count(string $string, int $offset = 0, int $length = null)

Let `$realOffset = _prepareOffset($offset)`
and `$realLength = _prepareLength($realOffset, $length)`.

Return the number of non-overlapping occurances of `$string` in the main string.

> Notes:
>
 * This function corresponds to `substr_count`.
 * The function allows the empty string as the search string, just like it is allowed in
   `indexOf()` etc. Counting with the empty string will always return `$realLength + 1` occurances.
   (One for every offset and an additional one at the end of the string.)

### Unclassified

    string[] split(string $separator = null, int $limit = PHP_INT_MAX)

TODO:

 * Option to drop empty splits?
 * Default WS split?
 * General option for multichar split?

---

    string[] chunk(int $chunkLength = 1)

If `$chunkLength <= 0` throw an `InvalidArgumentException`.

Split the main string into chunks of length `$chunkLength` and return them as an array. If the
length of the main string is not divisible by the chunk length, then the last chunk has length
`$this->length() % $chunkLength`.

> Notes:
>
 * This function corresponds to `str_split`.
 * It is perfectly valid to have a chunk length that is longer than the main string.
 * The name `chunk()` was chosen, because `split()` seems more appropriate for the `explode`
   equivalent. Furthermore the corresponding array functionality is also named `chunk()`.

---

    string repeat(int $times)

If `$times < 0` throw an `InvalidArgumentException`.

Return the main string repeated `$times` times. If `$times` is zero, return the empty string.

> Notes:
>
 * This function corresponds to `str_repeat`.

  [string_funcs]: http://php.net/manual/en/ref.strings.php
