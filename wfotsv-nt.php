<?php

// Recreate WFO RDF from TSV file so we don't hit the server

error_reporting(E_ALL);


require_once(dirname(__FILE__) . '/vendor/autoload.php');

$format_string = 'ntriples';
$format = \EasyRdf\Format::getFormat($format_string);
$serialiserClass  = $format->getSerialiserClass();
$serialiser = new $serialiserClass();
$options = array();
$graph = new \EasyRdf\Graph();

\EasyRdf\RdfNamespace::set('wfo', 'https://list.worldfloraonline.org/terms/');


$headings = array();

// Whcih classification do we follow?					
$classification = '2019-05';

// Map columns to RDF terms
$nom_fields = array(
	'taxonRank' 				=> 'wfo:rank',
	'scientificName' 			=> 'wfo:fullName',
	'scientificNameAuthorship'	=> 'wfo:authorship',
	'family' 					=> 'wfo:familyName',
	'genus' 					=> 'wfo:genusName',
	'specificEpithet' 			=> 'wfo:specificEpithet',
	'namePublishedIn' 			=> 'wfo:publicationCitation',
	'namePublishedInID' 		=> 'wfo:publicationID',
	'scientificNameID' 			=> 'wfo:nameID',
	'originalNameUsageID' 		=> 'wfo:hasBasionym' // this doesn't seem to exist in the TSV?
);			


$row_count = 0;

$filename = "Ornithoboea.tsv";
$filename = '2019-05.txt';
$filename = 'Monticalia.tsv';

$xml_counter = 1;
$xml_doc = '';


$chunk_files = array();
$chunk_size  = 10000;
$delay       = 5;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	print_r($row);
	
	$go = is_array($row) && count($row) > 1;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			//print_r($obj);	
			
			
			// Output as WFO RDF
			
			
			// We have a name, whcih we then connect to the relevant taxon
			
			$taxon_name_uri = 'https://list.worldfloraonline.org/' . $obj->taxonID;
						
			$graph = new \EasyRdf\Graph();	
			
			$taxon_name = $graph->resource($taxon_name_uri, 'wfo:TaxonName');
    		
    		foreach ($obj as $k => $v)
    		{
    			switch ($k)
    			{
    				// simple literals
    				case 'scientificName':
      				case 'scientificNameAuthorship':	  				
    				case 'namePublishedIn':
    				case 'namePublishedInID':
    				case 'family':
    				case 'genus':
    				case 'specificEpithet':
    					$taxon_name->add($nom_fields[$k], $v);
    					break;
    			
    				// literal and/or URI
    				case 'scientificNameID':
     					$taxon_name->add($nom_fields[$k], $v);
     					
     					// Use IPNI uri
						if (preg_match('/\d+-\d/', $v))
						{
							$taxon_name->addResource('schema:sameAs', 'urn:lsid:ipni.org:names:' . $v);
						}

    					break;
    					
    				case 'originalNameUsageID':
    					$taxon_name->addResource($nom_fields[$k], 'https://list.worldfloraonline.org/' . $v);
    					break;
    					    					
    				case 'taxonRank':
    					$taxon_name->addResource($nom_fields[$k], 'https://list.worldfloraonline.org/terms/' . strtolower($v));
    					break;
   			    			
    				default:
    					break;
    			}
    		
    		}
    		
			if (isset($obj->taxonomicStatus))
			{
				if ($obj->taxonomicStatus == 'Synonym')
				{
					if (isset($obj->acceptedNameUsageID))
					{
						$taxon_uri = 'https://wfo-list.rbge.info/' . $obj->acceptedNameUsageID . '-' . $classification;
						$taxon_concept = $graph->resource($taxon_uri, 'wfo:TaxonConcept');
						
						$taxon_concept->addResource('wfo:hasSynonym', $taxon_name);
						$taxon_name->addResource('wfo:isSynonymOf', $taxon_concept);
					}
				}
				else
				{
					$taxon_uri = 'https://wfo-list.rbge.info/' . $obj->taxonID . '-' . $classification;
					
					$taxon_concept = $graph->resource($taxon_uri, 'wfo:TaxonConcept');
					$taxon_concept->addResource('wfo:hasName', $taxon_name);
					
					$taxon_name->addResource('wfo:acceptedNameFor', $taxon_concept);
					$taxon_name->addResource('wfo:currentPreferredUsage', $taxon_concept);
				
					$taxon_concept->add('wfo:editorialStatus', $obj->taxonomicStatus);
				}			
			
				// classification is part-whole relationship
				if (isset($obj->parentNameUsageID))
				{
					$parent_uri = 'https://wfo-list.rbge.info/' . $obj->parentNameUsageID . '-' . $classification;
					$parent_concept = $graph->resource($parent_uri, 'wfo:TaxonConcept');
				
					$taxon_concept->addResource('dcterms:isPartOf', $parent_concept);
					$parent_concept->addResource('dcterms:hasPart', $taxon_concept);
				}
			
				// this classification
				$taxon_concept->addResource('wfo:classification', 'https://list.worldfloraonline.org/'  . $classification);
			}
			
			// serialise
			$xml = $serialiser->serialise($graph, $format_string, $options);  
			
			$xml_doc .= $xml;
			
			if ($xml_counter++ % $chunk_size == 0)
			{
				$xml_filename = "wfo-$xml_counter.nt";
				
				$chunk_files[] = $xml_filename;
				
				echo "Writing $xml_filename ...\n";
				
				file_put_contents($xml_filename, $xml_doc);
				$xml_doc = '';
			}
			
			if ($row_count > 35000)
			{
				//break;
			}
		}
	}	
	$row_count++;	
	
}	


if ($xml_doc != '')
{
	$xml_counter++;
	$xml_filename = "wfo-$xml_counter.nt";
	$chunk_files[] = $xml_filename;

	echo "Writing $xml_filename ...\n";

	file_put_contents($xml_filename, $xml_doc);
}

if (0)
{
	// Oxigraph
	$triple_store_url = 'http://143.198.96.145:7878/store';
	$named_graph = '?default';

	echo "--- curl upload.sh ---\n";
	$curl = "#!/bin/sh\n\n";
	foreach ($chunk_files as $xml_filename)
	{
		$curl .= "echo '$xml_filename'\n";
	
		$curl .= "curl '$triple_store_url$named_graph' -H 'Content-Type:application/rdf+xml' --data-binary '@$xml_filename' --progress-bar | tee /dev/null\n";					
		$curl .= "echo ''\n";
		$curl .= "sleep $delay\n";
	}

	file_put_contents(dirname(__FILE__) . '/upload.sh', $curl);
}

if (1)
{
	// Blazegraph
	$triple_store_url = 'http://localhost:55000/blazegraph/sparql';
	$named_graph = '';

	echo "--- curl upload.sh ---\n";
	$curl = "#!/bin/sh\n\n";
	foreach ($chunk_files as $xml_filename)
	{
		$curl .= "echo '$xml_filename'\n";
	
		$curl .= "curl '$triple_store_url$named_graph' -H 'Content-Type:text/rdf+n3' --data-binary '@$xml_filename' --progress-bar | tee /dev/null\n";					
		$curl .= "echo ''\n";
		$curl .= "sleep $delay\n";
	}

	file_put_contents(dirname(__FILE__) . '/upload.sh', $curl);
}


?>

