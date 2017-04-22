<?php

namespace Egf;

/**
 * Static class with some common functions.
 */
class Util {

    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Numeric                                                    **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Inspect variable. If it's a natural number then return true... false otherwise.
     * @param mixed $xVar       The variable to check.
     * @param bool  $bTypeCheck Decide to do type check or not. Default: FALSE.
     * @return bool True if it's a natural number.
     */
    public static function isNaturalNumber($xVar, $bTypeCheck = FALSE) {
        if ($bTypeCheck) {
            return (is_numeric($xVar) && !is_float($xVar) && (intval($xVar) > 0) && (intval($xVar) === $xVar));
        }
        else {
            return (is_integer(intval($xVar)) && $xVar == intval($xVar) && (intval($xVar) > 0));
        }
    }

    /**
     * Generate a random Float number.
     * @param float|integer $fMin      The minimum value.
     * @param float|integer $fMax      The maximum value.
     * @param int           $iDecimals The length of result.
     * @return float Random float number.
     */
    public static function getRandomFloat($fMin = 0.0, $fMax = 1.0, $iDecimals = 2) {
        $fScale = pow(10, $iDecimals);

        return mt_rand($fMin * $fScale, $fMax * $fScale) / $fScale;
    }

    /**
     * It gives back true with a percentage.
     * @param integer $iPercent The percent to be true.
     * @return bool Sometimes true.
     */
    public static function percentChance($iPercent) {
        return (mt_rand(0, 100) <= $iPercent);
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * String                                                     **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Generate a random string.
     * @param   int    $iLength Length of string. Default: 8.
     * @param   string $sType   Type of character pool. Default: alnum. Options: alnum, alpha, hexdec, numeric, nozero, distinct.
     * @return string Random string.
     */
    public static function getRandomString($iLength = 8, $sType = 'alnum') {
        if ($sType == 'alnum') {
            $sPool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        else if ($sType == 'alpha') {
            $sPool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        else if ($sType == 'hexdec') {
            $sPool = '0123456789abcdef';
        }
        else if ($sType == 'numeric') {
            $sPool = '0123456789';
        }
        else if ($sType == 'nozero') {
            $sPool = '123456789';
        }
        else if ($sType == 'distinct') {
            $sPool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
        }
        else {
            throw new \Exception("Invalid type for getRandomString function.");
        }

        // Split the pool into an array of characters //
        $arPool = str_split($sPool, 1);
        $sResult = '';

        // Select a random character from the pool and add it to the string //
        for ($i = 0; $i < $iLength; $i++) {
            $sResult .= $arPool[mt_rand(0, count($arPool) - 1)];
        }

        // Make sure alnum strings contain at least one letter and one digit //
        if ($sType === 'alnum' AND $iLength > 1) {
            // Add a random digit //
            if (ctype_alpha($sResult)) {
                $sResult[mt_rand(0, $iLength - 1)] = chr(mt_rand(48, 57));
            }
            // Add a random letter //
            else if (ctype_digit($sResult)) {
                $sResult[mt_rand(0, $iLength - 1)] = chr(mt_rand(65, 90));
            }
        }

        return $sResult;
    }

    /**
     * From the given string, it gives back placeholders. String between {{ and }}) in an array.
     * @param string $sInput         The input string.
     * @param bool   $bCutDelimiters If it's true, it cut down the {{ and }} characters from the results. Default: FALSE.
     * @return array Array of strings between {{ and }} characters.
     */
    public static function getPlaceholdersFromString($sInput, $bCutDelimiters = FALSE) {
        if (is_string($sInput)) {
            $iCnt = preg_match_all("/\{\{(.*?)\}\}/", $sInput, $aResults);
            if (is_numeric($iCnt) and is_array($aResults)) {
                if ($bCutDelimiters) {
                    $aCutResult = [];
                    foreach ($aResults[1] as $sSlug) {
                        $aCutResult[] = trim($sSlug);
                    }

                    return $aCutResult;
                }
                else {
                    return $aResults[0];
                }
            }
        }

        return [];
    }

    /**
     * It replace the dynamic parameters by the value that should be there.. Dynamic parameters are for example: "{{ id }}", "{{ status->id }}".
     * @param string $sToReplace The string to extend with values. It has to be translated by the SF2 service first if it's needed.
     * @param object $enObject   The (possibly) entity object to get the data from.
     * @return string The same string but the dynamic parameters were replaced by the data from the entity object.
     */
    public static function extendStringWithDynamicParameters($sToReplace, $enObject) {
        $aPlaceholders = static::getPlaceholdersFromString($sToReplace);
        $aValues = [];
        // Iterate the dynamic parameters.
        foreach ($aPlaceholders as $sKey) {
            // Trim the string.
            $sTrimmedKey = trim($sKey, "{ }");
            // If the entity has a method like this or it's a property chain , then load the data.
            if (static::hasObjectGetMethod($enObject, $sTrimmedKey) or strpos($sTrimmedKey, "->") !== FALSE) {
                $aValues[$sKey] = static::callObjectGetMethod($enObject, $sTrimmedKey);
            }
            // Method wasn't found and it's not a chain of properties.
            else {
                throw new \Exception("Entity doesn't have the asked method! \n Class: " . get_class($enObject) . " \n Method: get" . ucfirst($sTrimmedKey) . "() \n\n");
            }
        }
        // Update the string with the data from the parameter array.
        foreach ($aValues as $sKey => $sVal) {
            $sToReplace = str_replace($sKey, $sVal, $sToReplace);
        }

        return $sToReplace;
    }

    /**
     * Transform string into camelCase format.
     * @param string $sOriginal The string to transform.
     * @param string $sRemove   The characters those should be removed and count as space. Default is space, dash, and underline.
     * @return string The camelCased string.
     */
    public static function toCamelCase($sOriginal, $sRemove = ' -_') {
        return lcfirst(static::toStudlyCaps($sOriginal, $sRemove));
    }

    /**
     * Transform string into StudlyCaps format.
     * @param string $sOriginal The string to transform.
     * @param string $sRemove   The characters those should be removed and count as space. Default is space, dash, and underline.
     * @return mixed The StudyCapped string.
     */
    public static function toStudlyCaps($sOriginal, $sRemove = ' -_') {
        return str_replace(str_split($sRemove), '', ucwords($sOriginal, $sRemove));
    }

    /**
     * Make a string url friendly.
     * @param string $string
     * @return mixed
     */
    public static function stringToUrl($string) {
        return preg_replace('/^-+|-+$/', '', strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', static::removeAccentsFromString($string))));
    }

    /**
     * Remove accents from a string.
     * @param string $str
     * @param string $charset
     * @return mixed|string
     */
    public static function removeAccentsFromString($str, $charset = 'utf-8') {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);
        $str = preg_replace('[ő]', 'o', $str);
        $str = preg_replace('[ű]', 'u', $str);
        $str = preg_replace('[Ő]', 'O', $str);
        $str = preg_replace('[Ű]', 'U', $str);

        return $str;
    }

    /**
     * Truncate string to a length without cutting words into half.
     * @param string  $sString    String to truncate.
     * @param integer $iMaxLength Expected length of string.
     * @param string  $sPostFix   Append to the end of truncated string.
     * @return string Truncated string.
     */
    public static function truncateToLength($sString, $iMaxLength, $sPostFix = '...') {
        $aParts = preg_split('/([\s\n\r]+)/u', $sString, NULL, PREG_SPLIT_DELIM_CAPTURE);

        $iLength = 0;
        $iLastPart = 0; // todo into for...
        $sFinalPostFix = '';
        for (; $iLastPart < count($aParts); ++$iLastPart) {
            $iLength += strlen($aParts[$iLastPart]);
            if ($iLength > $iMaxLength) {
                $sFinalPostFix = $sPostFix;

                break;
            }
        }

        return implode(array_slice($aParts, 0, $iLastPart)) . $sFinalPostFix;
    }

    /**
     * Add an extension postfix to string if it does not have it.
     * @param string $sFile Path to file.
     * @param string $sExt  Expected extension.
     * @return string Path to file with an extension.
     */
    public static function addFileExtensionIfNeeded($sFile, $sExt) {
        // Add dot to extension if it is not there.
        $sExt = (substr($sExt, 0, 1) !== '.' ? ('.' . $sExt) : $sExt);

        // Add extension if it is not there.
        return $sFile . (substr($sFile, -(strlen($sExt))) !== $sExt ? $sExt : '');
    }

    /**
     * Trim slashes (and backslash) from a string.
     * @param string $sVar
     * @return string
     */
    public static function trimSlash($sVar) {
        return trim($sVar, '/ \\');
    }

    /**
     * Similar to the builtin explode, but in case of empty input string, it gives back an empty array... as it should be in the first place.
     * It does not have a limit parameter, because it causes a problem. Passing NULL in variables is used 1 and fracks up the whole thing.
     * Trims the string instead.
     * @param string $sDelimiter The delimiter string.
     * @param string $sInput     The variable to explode.
     * @param string $sTrim      Trim characters from string.
     * @return array             Fragments of the input string.
     */
    public static function trimExplode($sDelimiter, $sInput, $sTrim = '') {
        $sInput = trim($sInput, $sTrim);

        return (strlen($sInput) ? explode($sDelimiter, $sInput) : []);
    }

    /**
     * Concat arguments together with directory separator.
     * @params     Concat all arguments together.
     * @return string
     * @deprecated Should be...
     */
    public static function concatWithDirSep(/***/) {
        $sResult = '';
        foreach (func_get_args() as $sArg) {
            $sArg = static::trimSlash($sArg);
            if ($sArg) {
                $sResult .= $sArg . DIRECTORY_SEPARATOR;
            }
        }

        return static::trimSlash($sResult);
    }

    /**
     * Check if the given parameter is a valid eMail or not.
     * @param string $sEmail
     * @return bool
     */
    public static function isValidEmail($sEmail) {
        return ( !filter_var($sEmail, FILTER_VALIDATE_EMAIL) === FALSE);
    }

    /**
     * Check if the given string is a valid ip address.
     * @param string $sIp Variable to check. If null, ask server for remote ip address.
     * @return bool True if valid ip address.
     */
    public static function isValidIpAddress($sIp = NULL) {
        return (filter_var(($sIp ? $sIp : $_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP) !== FALSE);
    }

    /**
     * Transform number into first/second/third/4th/.. format.
     * @param int  $iNum  The number.
     * @param bool $bText Text instead. Default false.
     * @return string
     */
    public static function stNdRdTh($iNum, $bText = FALSE) {
        if ($iNum == 1) {
            return $bText ? 'first' : '1st';
        }
        else if ($iNum == 2) {
            return $bText ? 'second' : '2nd';
        }
        else if ($iNum == 3) {
            return $bText ? 'third' : '3rd';
        }
        else {
            return "{$iNum}th";
        }
    }

    /** @const Flags to work with the slashing method. */
    const
        slashingNs = 1,
        slashingDir = 2,
        slashingTrimBoth = 4,
        slashingTrimLeft = 8,
        slashingTrimRight = 16,
        slashingAddBoth = 36,
        slashingAddLeft = 72,
        slashingAddRight = 144;

    /**
     * Do some slashing stuff with a string. The slashing constants are verbose enough. One of the slashingNs or slashingDir is required.
     * @param string $sText  String to work with.
     * @param int    $iFlags Bitwise operators.
     * @return string
     */
    public static function slashing($sText, $iFlags) {
        // Ns or dir separator.
        if ($iFlags & static::slashingNs) {
            $sSep = '\\';
            $sText = preg_replace("/\\{$sSep}+/", $sSep, str_replace('/', $sSep, $sText));
        }
        elseif ($iFlags & static::slashingDir) {
            $sSep = '/';
            $sText = preg_replace("/\\{$sSep}+/", $sSep, str_replace('\\', $sSep, $sText));
        }
        else {
            throw new \Exception("Egf\\Util::slashing(str, flags) method needs slashingNs or slashingDir flag to do anything at all.");
        }

        // Trim.
        if ($iFlags & static::slashingTrimBoth) {
            $sText = trim($sText, "{$sSep} ");
        }
        else if ($iFlags & static::slashingTrimLeft) {
            $sText = ltrim($sText, "{$sSep} ");
        }
        else if ($iFlags & static::slashingTrimRight) {
            $sText = rtrim($sText, "{$sSep} ");
        }

        // Add.
        if ($iFlags & static::slashingAddBoth) {
            $sText = $sSep . $sText . $sSep;
        }
        else if ($iFlags & static::slashingAddLeft) {
            $sText = $sSep . $sText;
        }
        else if ($iFlags & static::slashingAddRight) {
            $sText = $sText . $sSep;
        }

        return $sText;
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Array                                                      **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Check for in_array but does not throw an error if it's not an array.
     * @param array $aHaystack  The array that can have the searched element.
     * @param mixed $xNeedle    The searched element of haystack array.
     * @param bool  $bTypeCheck Decide to do type check. Default: FALSE.
     * @return bool True if the element was found in array.
     */
    public static function inArray($aHaystack, $xNeedle, $bTypeCheck = FALSE) {
        if (is_array($aHaystack)) {
            foreach ($aHaystack as $xItem) {
                if (($bTypeCheck && $xItem === $xNeedle) || ( !$bTypeCheck && $xItem == $xNeedle)) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * Check a multi dimensional array for a value. If it's founded, then return true, else return false.
     * @param array  $aHaystack Inspected array.
     * @param string $xNeedle   Searched value.
     * @return boolean True if it's found.
     */
    public static function inArrayMulti($aHaystack, $xNeedle) {
        if (is_array($aHaystack)) {
            foreach ($aHaystack as $xItem) {
                if (is_array($xItem)) {
                    if (static::inArrayMulti($xNeedle, $xItem)) {
                        return TRUE;
                    }
                }
                else if ($xItem == $xNeedle) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * Sort a multi dimensional array by an index.
     * @param array   $array         Array what need a sort.
     * @param string  $index         Sort by index.
     * @param string  $order         Direction of sorting. Default: ASC. Options: ASC, DESC.
     * @param boolean $natSort       Is natural sorting. Default: FALSE.
     * @param boolean $caseSensitive Is case sensitive. Default: FALSE.
     * @return array Sorted array.
     */
    public static function sortArrayMulti($array, $index, $order = 'ASC', $natSort = FALSE, $caseSensitive = FALSE) {
        $order = strtoupper($order);
        $sorted = array();

        if (is_array($array) && count($array) > 0) {
            foreach (array_keys($array) as $key) {
                $temp[$key] = $array[$key][$index];
            }

            if ( !$natSort) {
                if (strtoupper($order) == 'ASC') {
                    asort($temp);
                }
                else {
                    arsort($temp);
                }
            }
            else {
                if ($caseSensitive === TRUE) {
                    natsort($temp);
                }
                else {
                    natcasesort($temp);
                }
                if (strtoupper($order) != 'ASC') {
                    $temp = array_reverse($temp, TRUE);
                }
            }

            foreach (array_keys($temp) as $key) {
                if (is_numeric($key)) {
                    $sorted[] = $array[$key];
                }
                else {
                    $sorted[$key] = $array[$key];
                }
            }

            return $sorted;
        }
        else {
            return array();
        }
    }

    /**
     * It looks for a row in a 2d array. Search by any key.
     * @param array   $aaRows     Array of arrays.
     * @param string  $sKey       The key of array.
     * @param mixed   $xVal       Searched value.
     * @param boolean $bTypeCheck Decide if the type should be checked.
     * @return array|null The first accepted row of array, null otherwise.
     */
    public static function oneFromArrayByKey(array $aaRows, $sKey, $xVal, $bTypeCheck = FALSE) {
        foreach ($aaRows as $aRow) {
            if (array_key_exists($sKey, $aRow)) {
                if (($bTypeCheck && $aRow[$sKey] === $xVal) || ( !$bTypeCheck && $aRow[$sKey] == $xVal)) {
                    return $aRow;
                }
            }
            else {
                throw new \Exception('The key "' . $sKey . '" does not exist in the row!');
            }
        }

        return NULL;
    }

    /**
     * It looks for a row in a 2d array. Search by id.
     * @param array          $aaRows     Array of arrays.
     * @param integer|string $iId        The number that should be behind the id assoc key.
     * @param boolean        $bTypeCheck Decide if the type should be checked.
     * @return array|null The first accepted row of array, null otherwise.
     */
    public static function oneFromArrayById(array $aaRows, $iId, $bTypeCheck = FALSE) {
        return static::oneFromArrayByKey($aaRows, 'id', $iId, $bTypeCheck);
    }

    /**
     * It looks for some rows in a 2d array. Search by key.
     * @param array   $aaRows     Array of arrays.
     * @param string  $sKey       The key of array.
     * @param mixed   $xVal       Searched value.
     * @param boolean $bTypeCheck Decide if the type should be checked.
     * @return array The array of accepted rows from input array.
     */
    public static function fromArrayByKey(array $aaRows, $sKey, $xVal, $bTypeCheck = FALSE) {
        $aResult = [];

        foreach ($aaRows as $aRow) {
            if (array_key_exists($sKey, $aRow)) {
                if (($bTypeCheck && $aRow[$sKey] === $xVal) || ( !$bTypeCheck && $aRow[$sKey] == $xVal)) {
                    $aResult[] = $aRow;
                }
            }
            else {
                throw new \Exception('The key "' . $sKey . '" does not exist in the row!');
            }
        }

        return $aResult;
    }

    /**
     * Removes element from array by its key.
     * @param $array {array} The array.
     * @param $key   {mixed} The key of the element.
     * @return array The array without element with key.
     */
    public static function removeFromArrayByKey($array, $key) {
        unset($array[$key]);

        return $array;
    }

    /**
     * Removes element from array by uts value.
     * @param $array   {array} The array.
     * @param $element {mixed} The value of the element.
     * @return array The array without element with value.
     */
    public static function removeFromArrayByValue($array, $element) {
        return array_diff($array, array($element));
    }

    /**
     * Decide if the given array is sequential or not.
     * @param array $array Variable to check.
     * @return bool True if sequential, false otherwise.
     * @todo What to do with empty arrays? Right now it gives back false.
     */
    public static function isArraySequential(array $array) {
        return (array_keys($array) === range(0, count($array) - 1));
    }

    /**
     * Gives back a random element from the array.
     * @param array $array Input elements.
     * @return mixed Output element.
     */
    public static function getRandomArrayElem(array $array) {
        return $array[mt_rand(0, count($array) - 1)];
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * DateTime                                                   **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Gives back the given DateTime or convert the string to DateTime and gives back that. It accepts much more date formats. Integer is used as timestamp.
     * @param \DateTime|string|integer|null $xDateTime A DateTime or string to convert to DateTime.
     * @param string                        $d1        [Default: Y] The first thing in the date. In some fhucked up countries it's not the year.
     * @param string                        $d2        [Default: m] The second thing in the date. In some fhucked up countries it's not the month.
     * @param string                        $d3        [Default: d] The third thing in the date. In some fhucked up countries it's not the day.
     * @return \DateTime|null DateTime or null if variable cannot be converted.
     */
    public static function toDateTime($xDateTime = NULL, $d1 = 'Y', $d2 = 'm', $d3 = 'd') {
        if ($xDateTime instanceof \DateTime) {
            return $xDateTime;
        }
        elseif (is_integer($xDateTime)) {
            return (new \DateTime())->setTimestamp($xDateTime);
        }
        elseif (is_string($xDateTime)) {
            $bWithTime = (13 < strlen(trim($xDateTime)));
            $asDateSeparators = ['.', '. ', '-', '/', ' '];
            $asDateTimeSeparators = [' ', '\T'];
            $asTimeSeparators = [':'];
            foreach ($asDateSeparators as $sDateSep) {
                foreach ([$sDateSep, ''] as $sDateEnd) {
                    // It has time values too.
                    if ($bWithTime) {
                        foreach ($asDateTimeSeparators as $sDateTimeSep) {
                            foreach ($asTimeSeparators as $sTimeSep) {
                                foreach ([($sTimeSep . 's'), ''] as $sSeconds) {
                                    $sFormat = ($d1 . $sDateSep . $d2 . $sDateSep . $d3 . $sDateEnd . $sDateTimeSep . 'H' . $sTimeSep . 'i' . $sSeconds);
                                    $dt = \DateTime::createFromFormat(trim($sFormat), trim($xDateTime));
                                    if ($dt instanceof \DateTime) {
                                        return $dt;
                                    }
                                }
                            }
                        }
                    }
                    // It has date value only.
                    else {
                        $sFormat = ($d1 . $sDateSep . $d2 . $sDateSep . $d3 . $sDateEnd);
                        $dt = \DateTime::createFromFormat(trim($sFormat), trim($xDateTime));
                        if ($dt instanceof \DateTime) {
                            return $dt;
                        }
                    }
                }
            }
        }

        return NULL;
    }

    /**
     * Check if the date is a valid or not. It checks only if the DateTime object can be created from this string. For example it doesn't throw error on 30th of February.
     * @param string $sDateString String which is a date possibly.
     * @return bool True if the string is valid.
     */
    public static function isDateTimeStringValid($sDateString) {
        return boolval(strtotime($sDateString));
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Object                                                     **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Check if a method exists.
     * @param object $oObject
     * @param string $sMethod
     * @return boolean
     */
    public static function hasObjectMethod($oObject, $sMethod) {
        return method_exists($oObject, $sMethod);
    }

    /**
     * Call a method of an object by string of the method.
     * @param object $oObject     Object.
     * @param string $sMethod     Method of object.
     * @param array  $aParameters [Default: null] Parameters of method.
     * @return mixed Result of the method.
     */
    public static function callObjectMethod($oObject, $sMethod, $aParameters = array()) {
        if (static::hasObjectMethod($oObject, $sMethod)) {
            return call_user_func_array(array($oObject, $sMethod), (is_array($aParameters) ? $aParameters : array($aParameters)));
        }
        else {
            throw new \Exception("Not existing method on object! \n Class: " . get_class($oObject) . " \n Method: " . $sMethod . " \n\n ");
        }
    }

    /**
     * Decide if the entity has a get method for the property.
     * @param object $oObject   The entity object to check.
     * @param string $sProperty The property that should have a setter method.
     * @return bool True if the entity has get field for property.
     */
    public static function hasObjectSetMethod($oObject, $sProperty) {
        return static::hasObjectMethod($oObject, ("set" . ucfirst($sProperty)));
    }

    /**
     * Call a set method of entity by string of dataMember.
     * @param   object $entity     Entity object.
     * @param   string $method     DataMember of entity.
     * @param   array  $parameters [Default: null] Parameters of set method.
     * @return  mixed                              Result of set method.
     */
    public static function callObjectSetMethod($entity, $method, $parameters = NULL) {
        return static::callObjectMethod($entity, ("set" . ucfirst($method)), $parameters);
    }

    /**
     * Decide if the entity has a get method for the property.
     * @param object $oObject   The entity object to check.
     * @param string $sProperty The property that should have a getter method.
     * @return bool True if the entity has get field for property.
     */
    public static function hasObjectGetMethod($oObject, $sProperty) {
        return static::hasObjectMethod($oObject, "get" . ucfirst($sProperty));
    }

    /**
     * Call a get method of entity by string of dataMember.
     * @param object $oEntity    Entity object.
     * @param string $sMethod    DataMember of entity. If some words are separated by "->", then it call the properties recursivly.
     * @param array  $parameters [Default: null] Parameters of get method.
     * @return mixed Result of the get method.
     */
    public static function callObjectGetMethod($oEntity, $sMethod, $parameters = NULL) {
        // Simple getter.
        if (static::hasObjectGetMethod($oEntity, $sMethod)) {
            $entityGetMethod = "get" . ucfirst($sMethod);
            if ($parameters) {
                return static::callObjectMethod($oEntity, $entityGetMethod, $parameters);
            }
            else {
                return static::callObjectMethod($oEntity, $entityGetMethod);
            }
        }
        // If the getter method is a chain (relatedObject->secondRelatedObject->finalAttribute), then it load the data recursively.
        else if (strpos($sMethod, "->") !== FALSE) {
            return static::getPropertyRecursively($oEntity, explode("->", $sMethod));
        }
        else {
            throw new \Exception("Object doesn't have getter! \n Class: " . get_class($oEntity) . " \n Method: get" . ucfirst($sMethod) . " \n\n ");
        }
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Private methods                                            **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Recursively iterate on related entities until it get the final data.
     * It's used in static::callObjectGetMethod() method.
     * @param object $enObject   The object that will have the next property.
     * @param array  $aFragments The chain of properties listed in an array.
     * @return string The result of property chain.
     */
    protected static function getPropertyRecursively($enObject, array $aFragments) {
        // First item in chain.
        $sProperty = array_shift($aFragments);
        // Check if the object has the getter.
        if (static::hasObjectGetMethod($enObject, $sProperty)) {
            // Load data.
            $xRelated = static::callObjectGetMethod($enObject, $sProperty);
            // If it's an object and the chain isn't over, then it loads that data.
            if (is_object($xRelated) and count($aFragments)) {
                return static::getPropertyRecursively($xRelated, $aFragments);
            }
            // Give back the final result.
            else {
                return $xRelated;
            }
        }
        else {
            throw new \Exception("Object doesn't have getter! \n Object: " . get_class($enObject) . " \n Method: get" . ucfirst($sProperty) . " \n\n ");
        }
    }

}
