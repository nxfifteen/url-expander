<?php

$url = str_replace("/apps/expand/http","http",$_SERVER['REQUEST_URI']);

if ($url == "") {
    header("Location: http://nxfifteen.me.uk");
}

$url      = explode("://",$url);
$protical = $url[0];
$address  = explode("/",$url[1]);
$domain   = array_shift($address);
$path     = implode("/",$address);
$query    = $_SERVER['QUERY_STRING'];

$destination = $protical."://".$domain;

if (!empty($path))  $destination = $destination . "/" . $path;
if (!empty($query)) $destination = $destination . "?" . $query;

$url = $destination;
$ch = curl_init($url);
curl_setopt($ch,CURLOPT_HEADER,true);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION,false);
$data = curl_exec($ch);
$pdata = http_parse_headers($data);
echo "Short URL: {$url}<br/>";
echo "Original URL: {$pdata['Location']}";

print_r($pdata); 
 
function http_parse_headers( $header )
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?< =^|[\x09\x20\x2D])./e', 'strtoupper("")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }
