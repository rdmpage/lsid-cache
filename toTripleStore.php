<?php

// For a data source extract all LSIDs from our cache and create a single file of triples



error_reporting(E_ALL);

require_once('vendor/autoload.php');

$format_string = 'ntriples';
$format = \EasyRdf\Format::getFormat($format_string);
$serialiserClass  = $format->getSerialiserClass();
$serialiser = new $serialiserClass();
$options = array();
$graph = new \EasyRdf\Graph();


// Path to local storage of LSID is the reverse of the domain name
$domain_path = array(
	'indexfungorum' => array('org', 'indexfungorum', 'names'),
	'ion' => array('com', 'organismnames', 'name'),
	'ipni_names' => array('org', 'ipni', 'names'),
	'worms' => array('org', 'marinespecies', 'taxname'),
);

// Pick a database to use
$database = 'indexfungorum';
$database = 'ion';
//$database = 'ipni_names';

$tmp_dir = dirname(__FILE__) . '/tmp';

// Fetch XML files
$basedir = dirname(__FILE__) . '/lsid/' . join('/', $domain_path[$database]);

$files1 = scandir($basedir);

// debug
//$files1 = array('7713');
$files1 = array('556');
//$files1 = array('12');
//$files1 = array('7714');

foreach ($files1 as $directory)
{
	// modulo 1000 directories
	if (preg_match('/^\d+$/', $directory))
	{		
		echo "\n--- $directory ---\n\n";	
	
		// gzip files
		$files2 = scandir($basedir . '/' . $directory);
		
		// debugging
		//$files2 = array('77134.xml.gz');
		
		// $files2 = array('800.xml.gz');
		
		foreach ($files2 as $archive)
		{
			if (preg_match('/.gz$/', $archive))
			{					
				$path = $basedir . '/' . $directory . '/' . $archive ;
				
				if (file_exists($path))
				{
					// decompress
					$xml_filename = str_replace('.gz', '', $archive);	
					
					echo "\n$xml_filename\n";
									
					$xml_filename = $tmp_dir . '/' . $xml_filename;
					
					$command = 'gunzip -c ' . $path  . ' >  ' . $xml_filename;					
					system($command);
					
					// fix
					$xml = file_get_contents($xml_filename);
					
					switch ($database)
					{
						case 'indexfungorum':
							// https://stackoverflow.com/a/1401716/9684
							$regex = '
							/
							  (
								(?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
								|   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
								|   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
								|   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
								){1,100}                        # ...one or more times
							  )
							| .                                 # anything else
							/x';
						
							$xml = preg_replace($regex, '$1', $xml);						
							break;
							
						case 'ion':
							// lacks URI as identifier
							$xml = preg_replace('/tdwg_tn:TaxonName rdf:about="(\d+)"/', 'tdwg_tn:TaxonName rdf:about="urn:lsid:organismnames.com:name:$1"', $xml);						
							
							// incorrect capitalisation
							$xml = preg_replace('/dc:Title/', 'dc:title', $xml);						
							$xml = preg_replace('/tdwg_co:PublishedIn/', 'tdwg_co:publishedIn', $xml);						
							break;
							
						case 'ipni_names':
							// https://stackoverflow.com/a/1401716/9684
							$regex = '
							/
							  (
								(?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
								|   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
								|   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
								|   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
								){1,100}                        # ...one or more times
							  )
							| .                                 # anything else
							/x';
						
							$xml = preg_replace($regex, '$1', $xml);						
						
							$xml = preg_replace('/&\s/', '&amp; ', $xml);						
							break;
					
						default:
							break;
					}
					
					// save
					file_put_contents($xml_filename, $xml);
					
					$upload = true;
					$use_named_graphs = true;
					//$upload = false;
					if ($upload)
					{
						// Oxigraph
						$triple_store_url = 'http://143.198.96.145:7878/store';
						$named_graph = '?default';
						$use_named_graphs = false;
												
						if ($use_named_graphs)
						{
							switch ($database)
							{
								case 'indexfungorum':
									$named_graph = '?graph=http://www.indexfungorum.org';
									break;

								case 'ipni':
									$named_graph = '?graph=https://www.ipni.org';
									break;

								case 'ion':
									$named_graph = '?graph=http://www.organismnames.com';
									break;
								
								default:
									$named_graph = '?default';
									break;
							}	
						}
					
						// send to triple store
						$command = "curl '$triple_store_url$named_graph' -H 'Content-Type:application/rdf+xml' --data-binary '@$xml_filename' --progress-bar";					
						$retval = 0;					
						system($command, $retval);
					
						echo "\nReturn value = $retval\n";						
					}
					
					// clean up
					// unlink($xml_filename);
				}		
			}
		}		
	}
}

?>


