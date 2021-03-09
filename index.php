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
	
	$example_lsid = 'urn:lsid:organismnames.com:name:1776318';
?>

<html>
	<head>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mini.css/3.0.1/mini-default.min.css">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
	<div class="container">
	<h1>Life Science Identifier (LSID) Resolver<small>Persistent identifiers for taxonomic names</small></h1>
	<p><a href="http://www.lsid.info">Life Sciences Identifier (LSID)</a> is a type of persistent identifier
	adopted by several biodiversity informatics projects, notably taxonomic name databases. 
	When a LSID is resolved it returns information about the corresponding entity in 
	<a href="https://en.wikipedia.org/wiki/Resource_Description_Framework">RDF</a>. For a variety of reasons LSIDs failed to gain much traction as a persistent identifier. 
	They are non-trivial to set up, require specialised software to resolve, and return RDF rather than human-readable content. </p>
	<p>
	However there are millions of LSIDs for taxonomic names "in the wild", and they continue to be minted for new names. 
	This service aims to make LSIDs resolvable by acting as a cache for LSID metadata and providing a simple
	interface for their resolution.</p>

	<p>Currently the following LSIDs are supported:</p>
	
	<ul>
		<li>Index of Organisms Names (ION), e.g. <a href="./urn:lsid:organismnames.com:name:1776318/jsonld">urn:lsid:organismnames.com:name:1776318</a></li>
		<li>International Plant Names Index (IPNI), e.g. <a href="./urn:lsid:ipni.org:names:298405-1/jsonld">urn:lsid:ipni.org:names:298405-1</a></li>
		<li>Index Fungorum, e.g. <a href="./urn:lsid:indexfungorum.org:names:356289/jsonld">urn:lsid:indexfungorum.org:names:356289</a></li>
	</ul>
	
	<h2>How to resolve a LSID</h2>
	
	<p>To resolve a LSID, such as <mark><?php echo $example_lsid; ?></mark> you just
	append it to this server address, i.e. 
	<mark><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/"; ?></mark>
	creating the URL	
	<mark><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $example_lsid; ?></mark>. 
	</p>
	<p>By default the LSID metadata is returned in RDFXML. You can ask for other formats by appending "/" and then the name of the format.</p>
	

	<table>
	
	 <caption>Supported formats</caption>
	 <thead>
	<tr><th>Name</th><th>MIME type</th><th>Example</th></tr>
	<thead>
	<tbody>
	<tr><td>rdfxml</td><td>application/rdf+xml</td><td><a href="./<?php echo $example_lsid; ?>/rdfxml"><?php echo $example_lsid; ?>/rdfxml</td></tr>
	<tr><td>jsonld</td><td>application/ld+json</td><td><a href="./<?php echo $example_lsid; ?>/jsonld"><?php echo $example_lsid; ?>/jsonld</td></tr>
	<tr><td>n3</td><td>text/n3</td><td><a href="./<?php echo $example_lsid; ?>/n3"><?php echo $example_lsid; ?>/n3</td></tr>
	<tr><td>ntriples</td><td>application/n-triples</td><td><a href="./<?php echo $example_lsid; ?>/ntriples"><?php echo $example_lsid; ?>/ntriples</td></tr>
	<tr><td>turtle</td><td>text/turtle</td><td><a href="./<?php echo $example_lsid; ?>/turtle"><?php echo $example_lsid; ?>/turtle</td></tr>
	<tr><td>dot</td><td>text/vnd.graphviz</td><td><a href="./<?php echo $example_lsid; ?>/dot"><?php echo $example_lsid; ?>/dot</td></tr>
	</tbody>
	</table>

	</div>
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

