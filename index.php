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
		<style>
		body {
			padding:20px;
		}
		</style>
	</head>
	<body>
	<div class="container">
	<h1>Life Science Identifier (LSID) Resolver<small>Persistent identifiers for taxonomic names</small></h1>

	<p>A project by Rod Page, source code on <a href="https://github.com/rdmpage/lsid-cache">GitHub</a>.</p>
	
	<form action=".">
	<div class="row">
  <input type="search" style="width:80%;" id="lsid" name="lsid" placeholder="urn:lsid:organismnames.com:name:1776318"/>
  <input type="hidden" name="format" value="jsonld" />
  <input class="primary" type="submit" value="Resolve" />
  </div>
	</form>
	
	
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
	
	<table>
	<tr><th></th><th>Source</th><th>Example</th></tr>
	<tr><td><img width="48" src="images/ion.svg"></td><td>Index of Organisms Names (ION)</td><td><a href="./urn:lsid:organismnames.com:name:1776318/jsonld">urn:lsid:organismnames.com:name:1776318</a></td></tr>
	<tr><td><img width="48" src="images/ipni.svg"></td><td>International Plant Names Index (IPNI)</td><td><a href="./urn:lsid:ipni.org:names:298405-1/jsonld">urn:lsid:ipni.org:names:298405-1</a></td></tr>
	<tr><td><img width="48" src="images/if.svg"></td><td>Index Fungorum</td><td><a href="./urn:lsid:indexfungorum.org:names:356289/jsonld">urn:lsid:indexfungorum.org:names:356289</a></td></tr>
	<!--<tr><td><img width="48" src="images/worms.svg"></td><td>World Register of Marine Species (WoRMS)</td><td><a href="./urn:lsid:marinespecies.org:taxname:1311580/jsonld">urn:lsid:marinespecies.org:taxname:1311580</a></td></tr> -->
	</table>
		
	<h2>How to resolve a LSID</h2>
	
	<p>To resolve a LSID, such as <mark><?php echo $example_lsid; ?></mark> you just
	append it to this server address, i.e. 
	<mark><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/"; ?></mark>
	creating the URL	
	<mark><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $example_lsid; ?></mark>. 
	</p>
	<p>By default the LSID metadata is returned in RDFXML. You can ask for other formats by appending "/" and then the name of the format,
	or by using content negotiation.</p>	

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
	
	// echo $integer_id ;
	
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
			// Need to handle cases (e.g., ION) where the URI is not a LSID but simply the integer id
			if (preg_match('/about=\s*"(' . $lsid . '|' . $id . ')"/', $lines[$i]))
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

// Do any post processing of XML that we need to do...
if (preg_match('/urn:lsid:(?<domain>[^:]+)/', $lsid, $m))
{
	
	switch ($m['domain'])
	{
		// ION may lack LSID prefix
		case 'organismnames.com':
			$xml = preg_replace('/tdwg_tn:TaxonName rdf:about="(\d+)"/', 'tdwg_tn:TaxonName rdf:about="urn:lsid:organismnames.com:name:$1"', $xml);
			break;
			
		default:
			break;
	}
}

if (0)
{
	echo '<pre>';
	echo htmlentities($xml);
	echo '</pre>';
}

$graph = new \EasyRdf\Graph();

$graph->parse($xml);
output($graph, $lsid, $format);


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
function output($graph, $lsid, $format_string){

    $format = \EasyRdf\Format::getFormat($format_string);

    $serialiserClass  = $format->getSerialiserClass();
    $serialiser = new $serialiserClass();
    
    // if we are using GraphViz then we add some parameters 
    // to make the images nicer
    if(preg_match('/GraphViz/', $serialiserClass)){
        $serialiser->setAttribute('rankdir', 'LR');
    }
    
    $options = array();
    
    if ($format_string == 'jsonld')
    {
    	$context = new stdclass;
		
		// $context->{'@vocab'} = "http://rs.tdwg.org/ontology/voc/TaxonName#";
		
		// TDWG
		$context->tn 		= "http://rs.tdwg.org/ontology/voc/TaxonName#";
		$context->tcom 		= "http://rs.tdwg.org/ontology/voc/Common#";
		$context->tteam 	= "http://rs.tdwg.org/ontology/voc/Team#";
		$context->tpub 		= "http://rs.tdwg.org/ontology/voc/PublicationCitation#";
		
		// Darwin Core
		$context->dwc 		= "http://rs.tdwg.org/dwc/terms/";
		
		// Dublin Core
		$context->dc 		= "http://purl.org/dc/elements/1.1/";
		$context->dcterms 	= "http://purl.org/dc/terms/";
		
		// RDF and OWL
		$context->rdfs 		= "http://www.w3.org/2000/01/rdf-schema#";
		$context->owl		= "http://www.w3.org/2002/07/owl#";	
		
		// Context	
		$options['context'] = $context;
		$options['compact'] = true;
		
		// Frame document if we can (need to know document type)		
		$name_type = 'http://rs.tdwg.org/ontology/voc/TaxonName#TaxonName';
		
		// Worms doesn't have rdf:type so don't frame
		if (preg_match('/marinespecies.org:taxname:/', $lsid))
		{
			$name_type = 'http://rs.tdwg.org/ontology/voc/TaxonName#TaxonName';		
		}
		else
		{
			$frame = (object)array(
				'@context' => $context,
				'@type' => $name_type
			);
				
			$options['frame']= $frame;
		}
    }
    
    $data = $serialiser->serialise($graph, $format_string, $options);
    
    header('Content-Type: ' . $format->getDefaultMimeType());

    print_r($data);
    exit;

}

