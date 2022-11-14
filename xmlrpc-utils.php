<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XMLRPC utils for qtype_algebra.
 *
 * @package    qtype_algebra
 * @copyright  Roger Moore <rwmoore@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file was part of, or distributed with, libXMLRPC - a C library for
// xml-encoded function calls.
// Author: Dan Libby (dan@libby.com)
// Epinions.com may be contacted at feedback@epinions-inc.com
// It was adapted to Moodle standards and coding style.

// Copyright 2001 Epinions, Inc.

// Subject to the following 3 conditions, Epinions, Inc.  permits you, free
// of charge, to (a) use, copy, distribute, modify, perform and display this
// software and associated documentation files (the "Software"), and (b)
// permit others to whom the Software is furnished to do so as well.

// 1) The above copyright notice and this permission notice shall be included
// without modification in all copies or substantial portions of the
// Software.

// 2) THE SOFTWARE IS PROVIDED "AS IS", WITHOUT ANY WARRANTY OR CONDITION OF
// ANY KIND, EXPRESS, IMPLIED OR STATUTORY, INCLUDING WITHOUT LIMITATION ANY
// IMPLIED WARRANTIES OF ACCURACY, MERCHANTABILITY, FITNESS FOR A PARTICULAR
// PURPOSE OR NONINFRINGEMENT.

// 3) IN NO EVENT SHALL EPINIONS, INC. BE LIABLE FOR ANY DIRECT, INDIRECT,
// SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES OR LOST PROFITS ARISING OUT
// OF OR IN CONNECTION WITH THE SOFTWARE (HOWEVER ARISING, INCLUDING
// NEGLIGENCE), EVEN IF EPINIONS, INC.  IS AWARE OF THE POSSIBILITY OF SUCH
// DAMAGES.

defined('MOODLE_INTERNAL') || die();

// Ensure extension is loaded.
if (!extension_loaded('xmlrpc')) {
    debugging('The php xml-rpc extension is not loaded, SAGE evaluation will fail.', DEBUG_DEVELOPER);
}

/**
 * Generic function to call an http server with post method.
 *
 * @param object $request
 * @param object $host
 * @param string $uri
 * @param int $port
 * @param bool $debug
 * @param int $timeout
 * @param object $user
 * @param object $pass
 * @param bool $secure
 * @return string
 */
function xu_query_http_post($request, $host, $uri, $port, $debug,
                            $timeout, $user, $pass, $secure = false) {
    $responsebuf = "";
    if ($host && $uri && $port) {
        $contentlen = strlen($request);

        $fsockopen = $secure ? "fsockopen_ssl" : "fsockopen";

        $queryfd = $fsockopen($host, $port, $errno, $errstr, 10);

        if ($queryfd) {
            $auth = "";
            if ($user) {
                $auth = "Authorization: Basic " .
                    base64_encode($user . ":" . $pass) . "\r\n";
            }

            $myhttprequest = "POST $uri HTTP/1.0\r\n" .
            "User-Agent: xmlrpc-epi-php/0.2 (PHP)\r\n" .
            "Host: $host:$port\r\n" .
            $auth .
            "Content-Type: text/xml\r\n" .
            "Content-Length: $contentlen\r\n" .
            "\r\n" .
            $request;

            fputs($queryfd, $myhttprequest, strlen($myhttprequest));

            $headerparsed = false;

            $line = fgets($queryfd, 4096);
            while ($line) {
                if (!$headerparsed) {
                    if ($line === "\r\n" || $line === "\n") {
                        $headerparsed = 1;
                    }
                } else {
                    $responsebuf .= $line;
                }
                $line = fgets($queryfd, 4096);
            }

            fclose($queryfd);
        } else {
            debugging('Socket open faile', DEBUG_DEVELOPER);
        }
    } else {
        debugging('Missing param(s)', DEBUG_DEVELOPER);
    }

    return $responsebuf;
}

/**
 * Xu fault code.
 *
 * @param object $code
 * @param string $string
 * @return array
 */
function xu_fault_code($code, $string) {
    return array('faultCode' => $code,
            'faultString' => $string);
}

/**
 * Find and decode XML.
 *
 * @param string $buf
 * @param bool $debug
 * @return mixed
 */
function find_and_decode_xml($buf, $debug) {
    if (strlen($buf)) {
        $xmlbegin = substr($buf, strpos($buf, "<?xml"));
        if (strlen($xmlbegin)) {
            $retval = xmlrpc_decode($xmlbegin);
        } else {
            debugging('xml start token not found', DEBUG_DEVELOPER);
        }
    } else {
        debugging('no data', DEBUG_DEVELOPER);
    }
    return $retval;
}


/**
 * a struct containing 3 or more of these key/val pairs:
 *
 * host     remote host (required)
 * uri      remote uri     (required)
 * port     remote port (required)
 * method   name of method to call
 * args     arguments to send (parameters to remote xmlrpc server)
 * debug    debug level (0 none, 1, some, 2 more)
 * timeout  timeout in secs.  (0 = never)
 * user     user name for authentication.
 * pass     password for authentication
 * secure   secure. wether to use fsockopen_ssl. (requires special php build).
 * output   array. xml output options. can be null.  details below:
 *
 *     output_type: return data as either php native data types or xml
 *                  encoded. ifphp is used, then the other values are ignored. default = xml
 *     verbosity:   determine compactness of generated xml. options are
 *                  no_white_space, newlines_only, and pretty. default = pretty
 *     escaping:    determine how/whether to escape certain characters. 1 or
 *                  more values are allowed. If multiple, they need to be specified as
 *                  a sub-array. options are: cdata, non-ascii, non-print, and
 *                  markup. default = non-ascii | non-print | markup
 *     version:     version of xml vocabulary to use. currently, three are
 *                  supported: xmlrpc, soap 1.1, and simple. The keyword auto is also
 *                  recognized to mean respond in whichever version the request came
 *                  in. default = auto (when applicable), xmlrpc
 *     encoding:    the encoding that the data is in. Since PHP defaults to
 *                  iso-8859-1 you will usually want to use that. Change it if you know
 *                  what you are doing. default=iso-8859-1
 *
 *   example usage
 *
 *                   $output_options = array('output_type' => 'xml',
 *                                           'verbosity' => 'pretty',
 *                                           'escaping' => array('markup', 'non-ascii', 'non-print'),
 *                                           'version' => 'xmlrpc',
 *                                           'encoding' => 'utf-8'
 *                                         );
 *                   or
 *
 *                   $output_options = array('output_type' => 'php');
 *
 * @param object $params
 * @return mixed
 */
function xu_rpc_http_concise($params) {
    $host = $uri = $port = $method = $args = $debug = null;
    $timeout = $user = $pass = $secure = $debug = null;

    foreach ($params as $key => $value) {
        $$key = $value;
    }

    // Default values.
    if (!$port) {
        $port = 80;
    }
    if (!$uri) {
        $uri = '/';
    }
    if (!isset($output)) {
        $output = array('version' => 'xmlrpc');
    }

    $responsebuf = "";
    if ($host && $uri && $port) {
        $requestxml = xmlrpc_encode_request($method, $args, $output);
        $responsebuf = xu_query_http_post($requestxml, $host, $uri, $port, $debug,
                                           $timeout, $user, $pass, $secure);

        $retval = find_and_decode_xml($responsebuf, $debug);
    }
    return $retval;
}

/**
 * Call a xmlrpc method on a remote http server. legacy support.
 *
 * @param object $method
 * @param object $args
 * @param object $host
 * @param object $uri
 * @param object $port
 * @param bool $debug
 * @param int $timeout
 * @param object $user
 * @param object $pass
 * @param object $secure
 * @return mixed
 */
function xu_rpc_http($method, $args, $host, $uri="/", $port=80, $debug=false,
                     $timeout=0, $user=false, $pass=false, $secure=false) {
    return xu_rpc_http_concise(
        array(
            'method'  => $method,
            'args'    => $args,
            'host'    => $host,
            'uri'     => $uri,
            'port'    => $port,
            'debug'   => $debug,
            'timeout' => $timeout,
            'user'    => $user,
            'pass'    => $pass,
            'secure'  => $secure
        ));
}


/**
 * Xu is fault.
 *
 * @param object $arg
 * @return bool
 */
function xu_is_fault($arg) {
    // The xmlrpc extension finally supports this.
    return is_array($arg) ? xmlrpc_is_fault($arg) : false;
}

/**
 * Sets some http headers and prints xml.
 *
 * @param string $xml
 * @return void
 */
function xu_server_send_http_response($xml) {
    header("Content-type: text/xml");
    header("Content-length: " . strlen($xml) );
    echo $xml;
}
