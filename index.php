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


$lsid = '';

if (isset($_GET['lsid']))
{
	$lsid = $_GET['lsid'];
}
else
{
	// No LSID so have welcome page here
?>

<html>
	<head>
	</head>
	<body>
	<h1>Life Science Identifiers (LSID) for taxonomic names</h1>
	</body>
</html>


<?	
	exit();
}


if(preg_match('/^urn:lsid:\w+\.[a-z]{3}:\w+:.*/i', $lsid))
{
	$format = get_format($lsid);
}
else
{
    header("HTTP/1.0 400 Bad Request");
    echo "Unrecognised LSID format: \"$lsid\"";
    exit;
}

// Resolve LSID

// try to get LSID from disk
$xml = '';

if (preg_match('/urn:lsid:(?<domain>[^:]+):(?<type>[^:]+):(?<id>.*)/', $lsid, $m))
{
	$path_array = explode(".", $m['domain']);
	$path_array = array_reverse($path_array);
	$path_array[] = $m['type'];
	
	// local identifier
	$id = $m['id'];	
	$integer_id = preg_replace('/-\d+$/', '', $id);
	
	// map to location of archive
	$dir_id = floor($integer_id / 10000);
	$gz_id = floor($integer_id / 1000);
	
	$path = 'lsid/' . join('/', $path_array) . '/' . $dir_id . '/' . $gz_id . '.xml.gz';
		
	if (file_exists($path))
	{
		// Explode archive, find line with record for LSID	
		$lines = gzfile($path);
	
		//print_r($lines);

		$xml = '';

		$n = count($lines);

		for ($i = 0;$i < $n; $i++)
		{
			if (preg_match('/' . $lsid . '/', $lines[$i]))
			{
				$xml = $lines[$i];
				break;
			}
		}
	}
	
}

if ($xml == '')
{
	header("HTTP/1.0 404 Not Found");
	echo "LSID \"$lsid\" not found";
	exit;
}

$graph = new \EasyRdf\Graph();

$graph->parse($xml);
output($graph, $format);


//----------------------------------------------------------------------------------------
function get_format($lsid)
{
        
    $format_string = null;
    $formats = \EasyRdf\Format::getFormats();
    
    // Get it from URL
    if (isset($_GET['format']))
    {
       if(in_array($_GET['format'], $formats)){
            $format_string = $_GET['format'];
        }else{
            header("HTTP/1.0 400 Bad Request");
            echo "Unrecognised data format \"{$_GET['format']}\"";
            exit;
        }    
    }
    else
    {
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
            . "://$_SERVER[HTTP_HOST]/";
            
        // debugging on local server
        if ("$_SERVER[HTTP_HOST]" == 'localhost')
        {
   			$redirect_url .= '~rpage/lsid-cache/';
   		}
            
            
          $redirect_url .= $lsid . '/' . $format_string;

            header("Location: $redirect_url",TRUE,303);
            echo "Found: Redirecting to data";
            exit;

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
    
    // do any post-processing here...
    
    header('Content-Type: ' . $format->getDefaultMimeType());

    print_r($data);
    exit;

}

