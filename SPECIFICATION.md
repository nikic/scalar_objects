<pre>
String Class
============

bool String::contains($subString);
bool String::containsAll(array $elements);
bool String::containsAny(array $elements);
bool String::isNumeric();

String::subStr($start, $length); // JS-like
String::subString($start, $end); // JS-like

String::replace($from, $to, $limit=null); // TODO check arrays
String::replacePairs(array $pairs); // TODO limit?

String String::reverse();
String String::toLower();
String String::toUpper();
String String::ucFirst();
String String::lcFirst();

String String::trim(string $chars="..."); // e.g. "abbcba"->trim("ab") === "c"
String String::trim(array $elements); // e.g. "abbbba"->trim(["ab", "ba"]) === "bb" // TODO check naming
String String::trimLeft($chars); // TODO array
String String::trimRight($chars); // TODO array
String String::padLeft($length, string $chars=" "); // "20"->padLeft(4, " ") === "  20"
String String::padRight($length, string $chars=" ");
String String::repeat(uint $times);

String String::template(array $vars); // TODO syntax: printf?, see http://underscorejs.org/#template

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
string String::escapeJs();
string String::unescapeXml();
string String::unescapeJs();


Mutable:
$str = "foo"; // Immutable by default
$str->Mutable()->replace("foo", "bar");

Chaining:
$html = "Hello :name"->template(["name"=>"world"])->escapeHtml();


TODO:
String::Format
substr_count
substr_replace
str_split explode
String::split(string $separator, $limit=null);
String::split(array $separators, $limit=null);
String::match(string $regex) => return matches?
check https://github.com/Respect/Validation
check filter: http://de.php.net/filter
check js, jQuery, underscore.js, c#
md5/sha1
error handling: parameter = false, parameter = null, parameter = object (__toString()? __toArray()?)
error handling: negative parameters for start, end, length, offset, etc.
</pre>