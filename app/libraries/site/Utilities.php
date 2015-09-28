
<?php

/**
* --------------------------------------------------------------------------
* Utilities:
*       Wrapper functions for the utilities used all over the site.
*       All functions can be called directly from the templates
* Usage:
*       PHP     | $constant = SiteConstants::functionName();
*       BLADE   | {{ SiteConstants::functionName() }}
* --------------------------------------------------------------------------
*/
class Utilities {

    /**
     * underscoreToCamelCase
     * Returning a string in CamelCase.
     * --------------------------------------------------
     * @param string $input
     * @param boolean $keepSpace
     * @return string
     * --------------------------------------------------
    */
    public static function underscoreToCamelCase($input, $keepSpace=FALSE) {
        $output = ucwords(str_replace('_',' ', $input));
        return $keepSpace ? $output : str_replace(' ', '', $output);
    }

    /**
     * formatNumber
     * Formatting a number based on parameters.
     * --------------------------------------------------
     * @param numeric $input
     * @param boolean $currency
     * @return string
     * --------------------------------------------------
    */
    public static function formatNumber($input, $format='%d') {
        return sprintf($format, $input);
    }

}