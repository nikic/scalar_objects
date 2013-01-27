<pre>
string Class
============

bool String::contains(string $subString);
bool String::containsAll(array $elements);
bool String::containsAny(array $elements);
bool String::isNumeric();

String::subStr($start, $length); // JS-like, TODO return?
String::subString($start, $end); // JS-like, TODO return?

String::replace($from, $to, $limit=null); // TODO check arrays, TODO return?
String::replacePairs(array $pairs); // TODO limit?, TODO return?

string String::reverse();
string String::toLower();
string String::toUpper();
string String::ucFirst();
string String::lcFirst();

string String::trim(string $chars="..."); // e.g. "abbcba"->trim("ab") === "c"
string String::trim(array $elements); // e.g. "abbbba"->trim(["ab", "ba"]) === "bb" // TODO check naming
string String::trimLeft(string $chars); // TODO array
string String::trimRight(string $chars); // TODO array
string String::padLeft($length, string $chars=" "); // "20"->padLeft(4, " ") === "  20"
string String::padRight($length, string $chars=" ");
string String::repeat(uint $times); // TODO force uint?

string String::template(array $vars); // TODO syntax: printf?, see http://underscorejs.org/#template

array String::split(string $separator, uint $limit=null); // TODO force uint?
array String::split(array $separators, $limit=null); // TODO naming?

int String::length();
int String::indexOf($subString, $offset=0); // start with 0, return -1 if not found // TODO check array
int String::lastIndexOf($subString, $offset=0); // start with 0, return -1 if not found // TODO check array
bool String::startsWith(string $subString); // e.g. "abc"->startsWith("a") === true
bool String::startsWithAny(array $subString); // e.g. "abc"->startsWith(["d","e"]) === true
bool String::endsWith(string $subString);
bool String::endsWithAny(array $subString);
bool String::matches(string $regex); // TODO check

string String::escapeHtml(); // TODO $allowableTags, $allowableTagAttributes
string String::unescapeHtml();
string String::stripTags($allowableTags); // TODO check, http://de.php.net/strip_tags
string String::stripTagAttributes($allowableAttributes); // TODO check
string String::containsTags(); // TODO check
bool String::containsTags(); // TODO check
string String::escapeXml();
string String::unescapeXml();
string String::escapeJs();
string String::unescapeJs();

string String::convert(string $charset, bool $translit=false); // default: ignore, see http://de1.php.net/manual/en/function.iconv.php

array String::parseUrl($parseQuery=false); // TODO check http://de.php.net/parse_url, http://de.php.net/parse_str

Mutable:
$str = "foo"; // Immutable by default
$str->Mutable()->replace("foo", "bar"); // $str==="bar"

Chaining:
$html = "Hello :name"->template(["name"=>"world"])->escapeHtml();

Backporting / Userland-only:
$html = String("Hello :name")->template(["name"=>"world"])->escapeHtml();

Default charset: UTF-8

Charset conversion:
$sub = String("FooBar", "ISO-8859-1")->subStr(3); // === iconv("ISO-8859-1", "UTF-8", "FooBar")->subStr(3)


TODO:
String::Format
substr_count
html_ entity_ decode
htmlentities
substr_replace
str_split explode preg_split chunk_split
wordwrap
str_word_count
nl2br => plainToHtml?
addslashes
urldecode, urlencode
basename, dirname ?
String::match(string $regex) preg_match preg_match_all => return matches?
preg_replace
check callback functions
check https://github.com/Respect/Validation
check filter: http://de.php.net/filter
check js, jQuery, underscore.js, c#
sha1
error handling: parameter = false, parameter = null, parameter = object (__toString()? __toArray()?)
error handling: negative parameters for start, end, length, offset, etc.
</pre>