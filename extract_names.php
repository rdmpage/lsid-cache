<?php


// Extract taxonomic name from metadata

// Samsung external drive with RDF cache
$caches = array(
	'indexfungorum' => '/Volumes/Samsung_T5/rdf-archive/indexfungorum/rdf',
	'ipni_names' => '/Volumes/Samsung_T5/rdf-archive/ipni/rdf',
	'ipni_authors' => '/Volumes/Samsung_T5/rdf-archive/ipni/authors',
	'ion' => '/Volumes/Samsung_T5/rdf-archive/ion/rdf',
	'worms' => '/Volumes/Samsung_T5/rdf-archive/worms/rdf',
	'wsc' => '/Volumes/Samsung_T5/rdf-archive/nmbe/rdf',
);	

// Path to local storage of LSID is the reverse of the domain name
$domain_path = array(
	'indexfungorum' => array('org', 'indexfungorum', 'names'),
	'ion' => array('com', 'organismnames', 'name'),
	'ipni_names' => array('org', 'ipni', 'names'),
	'worms' => array('org', 'marinespecies', 'taxname'),
);


$database = 'ipni_names';
//$database = 'indexfungorum';
//$database = 'ion';
$database = 'worms';

// Fetch XML files
$basedir = $caches[$database];

$files1 = scandir($basedir);

$files1 = array('1311');

foreach ($files1 as $directory)
{
	// modulo 1000 directories
	if (preg_match('/^\d+$/', $directory))
	{	
		$files2 = scandir($basedir . '/' . $directory);

		// individual XML files
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.xml$/', $filename))
			{	
				$full_filename = $basedir . '/' . $directory . '/' . $filename;
				
				$xml = file_get_contents($full_filename);
				
				$dom = new DOMDocument;
				$dom->loadXML($xml);
				$xpath = new DOMXPath($dom);
		
				// get details
				
				$obj = new stdclass;
				$obj->name = '';
				$obj->id = '';
				$obj->wikidata = '';
				
				switch ($database)
				{
					case 'worms':
						$xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
						$xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');

						foreach($xpath->query ('//dc:title') as $node)
						{
							$obj->name = $node->firstChild->nodeValue;
						}
						
						foreach($xpath->query ('//rdf:Description/@rdf:about') as $node)
						{
							$obj->id = $node->firstChild->nodeValue;
							$obj->id = str_replace('urn:lsid:marinespecies.org:taxname:', 'worms:', $obj->id);
						}
						break;
				
					default:
						break;
				}
				
				
				// output
				$row = array();
				$keys = array('name', 'id', 'wikidata');
				foreach ($keys as $k)
				{
					$row[] = $obj->{$k};
				}
				
				//print_r($row);
				
				echo join("\t", $row) . "\n";

			}
		}

	}
}


?>


