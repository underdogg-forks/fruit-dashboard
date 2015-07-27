<?php

/**
* -------------------------------------------------------------------------- 
* SiteFunctions: 
*       Class for the global site functions.
* Usage:
*       PHP     | $returnValue = SiteFunctions::functionName();
*       BLADE   | {{ SiteFunctions::functionName() }}
* -------------------------------------------------------------------------- 
*/
class SiteFunctions {
    /* -- Class properties -- */
    
    /**
     * ================================================== *
     *                   PUBLIC SECTION                   *
     * ================================================== *
     */

    /**
     * postUrl
     * --------------------------------------------------
     * Creates a POST request, and returns the response
     * @param (array) ($url)      The url endpoint and params
     *          (string) (endpoint) The URL endpoint
     *          (array)  (params)   The URL parameters
     * @return (array) ($response) JSON decoded response
     * --------------------------------------------------
     */
    public static function postUrl($url) {
        /* Build request */
        $request = curl_init($url['endpoint']);
        curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($url['params']));
        curl_setopt($request, CURLOPT_HEADER,           FALSE);
        curl_setopt($request, CURLOPT_POST,             TRUE);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION,   TRUE);
        curl_setopt($request, CURLOPT_RETURNTRANSFER,   TRUE);
        //curl_setopt($request, CURLOPT_FRESH_CONNECT,    TRUE);
        //curl_setopt($request, CURLOPT_TIMEOUT_MS,       10);

        // TODO: Additional error handling
        $respCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
        
        /* Get response */
        $response = json_decode(curl_exec($request), TRUE);
        
        /* Close connection*/
        curl_close($request);

        /* Return response */
        return $response;
    }
    
} /* SiteFunctions */