<?php

// Much of this code is a blatant steal from Roger Hyam's WFO code

require_once('vendor/autoload.php');

error_reporting(E_ALL);


// unregister a few formats we don't have serialisers for
\EasyRdf\Format::unregister('rdfa');
\EasyRdf\Format::unregister('json-triples');
\EasyRdf\Format::unregister('json-triples');
\EasyRdf\Format::unregister('sparql-xml');
\EasyRdf\Format::unregister('sparql-json');


// path should be of the form /wfo-id/format or /terms/
$path_parts = explode('/', $_SERVER["REQUEST_URI"]);
array_shift($path_parts); // lose the first blank one

// do the welcome page if there is no LSID
if(strlen($path_parts[0]) == 0){
    // include('welcome.php');
    echo "Hi";
    exit;
}

$format = get_format($path_parts);

$lsid = '';

// first argument is the LSID
if(preg_match('/^urn:lsid:\w+\.[a-z]{3}:\w+:.*/i', $path_parts[0]))
{
    // LSID
    $lsid  = $path_parts[0];
}
else
{
    header("HTTP/1.0 400 Bad Request");
    echo "Unrecognised LSID format: \"{$path_parts[0]}\"";
    exit;
}

// Resolve LSID

// try to get LSID from disk


$filename = 'i.xml';



$graph = new \EasyRdf\Graph();

$graph->parseFile($filename);
output($graph, $format);

    


//----------------------------------------------------------------------------------------
function get_format($path_parts){
        
    $format_string = null;
    $formats = \EasyRdf\Format::getFormats();

    // if we don't have any format in URL
    if(count($path_parts) < 2 || strlen($path_parts[1]) < 1){

        // try and get it from the http header
        $headers = getallheaders();
        if(isset($headers['Accept'])){
            $mimes = explode(',', $headers['Accept']);
       
            foreach($mimes as $mime){
                foreach($formats as $format){
                    $accepted_mimes = $format->getMimeTypes();
                    foreach($accepted_mimes as $a_mime => $weight){
                        if($a_mime == $mime){
                            $format_string = $format->getName();
                            break;
                        }
                    }
                    if($format_string) break;
                }
                if($format_string) break;
            }
        }

        // if we can't get it from accept header then use default
        if(!$format_string){
            $format_string = 'rdfxml';
        }

        // redirect them
        // if the format is missing we redirect to the default format
        // always 303 redirect from the core object URIs
        $redirect_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://$_SERVER[HTTP_HOST]/"
            . $path_parts[0]
            . '/'
            . $format_string;

            header("Location: $redirect_url",TRUE,303);
            echo "Found: Redirecting to data";
            exit;


    }else{

        // we have a format in the url string
        if(in_array($path_parts[1], $formats)){
            $format_string = $path_parts[1];
        }else{
            header("HTTP/1.0 400 Bad Request");
            echo "Unrecognised data format \"{$path_parts[1]}\"";
            exit;
        }

    }

    return $format_string;
    
}

//----------------------------------------------------------------------------------------
function output($graph, $format_string){

    $format = \EasyRdf\Format::getFormat($format_string);

    $serialiserClass  = $format->getSerialiserClass();
    $serialiser = new $serialiserClass();
    
    // if we are using GraphViz then we add some parameters 
    // to make the images nicer
    if(preg_match('/GraphViz/', $serialiserClass)){
        $serialiser->setAttribute('rankdir', 'LR');
    }
    
    $data = $serialiser->serialise($graph, $format_string);
    
    header('Content-Type: ' . $format->getDefaultMimeType());

    print_r($data);
    exit;

}

