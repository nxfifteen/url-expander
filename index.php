<?php
$url = $_GET['url'];

if ($url == "") {
    $report = array();
    $report['origin'] = "Undefined";
    $report['destination'] = "Undefined";
    $report['jumps'] = array();

    if (array_key_exists("debug", $_GET)) {
        print "<pre>";
        print_r($report);
        print "</pre>";
    } elseif (array_key_exists("format", $_GET) and $_GET['format'] == "text") {
        print $report['destination'];
    } else {
        print json_encode($report);
    }

    die();
}

$url      = explode("://",$url);
$protical = $url[0];
$address  = explode("/",$url[1]);
$domain   = array_shift($address);
$path     = implode("/",$address);

$origin_target = $protical."://".$domain;

if (!empty($path))  $origin_target = $origin_target . "/" . $path;

$search_target = $origin_target;

$report = array();
$report['origin'] = $origin_target;
$report['destination'] = "";

if (testurl($origin_target) == "301" or preg_match("/https?:\/\/nx15\.at\/.*/", $origin_target) or preg_match("/https?:\/\/nxfifteen\.me\.uk\/land\/.*/", $origin_target)) {
    $search_for_base = true;
}

$report['jumps'] = array();
$jumps = 1;
while($search_for_base) {
    $found_target = getlocation($search_target);
    $report['jumps'][$jumps] = $found_target;

    if (testurl($found_target) == "301" or preg_match("/https?:\/\/nx15\.at\/.*/", $found_target) or preg_match("/https?:\/\/nxfifteen\.me\.uk\/land\/.*/", $found_target)) {
        $search_target = $found_target;
    } else {
        $search_for_base = false;
    }

    $jumps = $jumps + 1;
    if ($jumps > 10)
        $search_for_base = false;
}

$report['destination'] = $report['jumps'][(count($report['jumps']))];

if (array_key_exists("debug", $_GET)) {
    print "<pre>";
    print_r($report);
    print "</pre>";
} elseif (array_key_exists("format", $_GET) and $_GET['format'] == "text") {
    print $report['destination'];
} else {
    print json_encode($report);
}

function testurl($finalDestination) {
    $handle = curl_init($finalDestination);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_exec($handle);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    return($httpCode);
}

function getlocation($finalDestination) {
     if (preg_match("/https?:\/\/nx15\.at\/.*/", $finalDestination)) {
         return @file_get_contents("http://nx15.at/api?short=" . urlencode($finalDestination) . "&format=text");
     } else if (preg_match("/https?:\/\/nxfifteen\.me\.uk\/land\/.*/", $finalDestination)) {
         $finalDestination = explode("/-/", $finalDestination);
         return "http://nxfifteen.me.uk/" . $finalDestination[1];
     } else {
        $header_locations = Array();

        $realLocations = page_get_headers($finalDestination);

         /** @noinspection PhpUnusedLocalVariableInspection */
         foreach ( $realLocations as $index => $location ) {
            if (array_key_exists("location",$location)) {
                array_push($header_locations,$location['location']);
            } elseif (array_key_exists("Location",$location)) {
                array_push($header_locations,$location['Location']);
            }
        }

        return($header_locations[0]);
    }
}

function page_get_headers($crawl_uri) {
    // get the array values where to split the second get headers return
    $headers = get_headers($crawl_uri, 1);
    if (empty($headers)) {
        return array();
    }
    $splitmarks = array();
    foreach ($headers as $key=>$h) {
        if (is_numeric($key)) {
            array_push($splitmarks, $h);
        }
    }

    // get the "real" headers
    $headers_final = array();
    $i = 0;
    $headers = get_headers($crawl_uri);
    foreach ($headers as $head) {
        // if the value is one of the splitmarks, start the next header
        if (array_key_exists($i, $splitmarks) && $head === $splitmarks[$i]) {
            $i++;
        }
        else {
            // get the headers name
            $tmp = explode(": ", $head, 2);
            // check if the header is already existing, if yes, parse the similar header to an array
            if (!empty($headers_final[$i-1][$tmp[0]])) {
                if (is_array($headers_final[$i-1][$tmp[0]])) {
                    array_push($headers_final[$i-1][$tmp[0]], $tmp[1]);
                }
                else {
                    $headers_final[$i-1][$tmp[0]] = array($headers_final[$i-1][$tmp[0]], $tmp[1]);
                }
            }
            else {
                $headers_final[$i-1][$tmp[0]] = $tmp[1];
            }
        }
    }
    return $headers_final;
}