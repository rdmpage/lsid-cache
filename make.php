<?php


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
$database = 'indexfungorum';
//$database = 'ion';
//$database = 'worms';

// Fetch XML files
$basedir = $caches[$database];

$files1 = scandir($basedir);


// ipni
if (0)
{
	// add/redo a specific range
	$files1 = array();
	for ($i = 77218; $i <= 77254; $i++)
	{
		$files1[] = $i;
	}
}

// ion
if (0)
{
	// add/redo a specific range
	$files1 = array();
	for ($i = 5562; $i <= 5600; $i++)
	{
		$files1[] = $i;
	}
}

// IF
if (1)
{
	// add/redo a specific range
	$files1 = array();
	for ($i = 824; $i <= 824; $i++)
	{
		$files1[] = $i;
	}
}


foreach ($files1 as $directory)
{
	// modulo 1000 directories
	if (preg_match('/^\d+$/', $directory))
	{	
		$files2 = scandir($basedir . '/' . $directory);
		
		print_r($files2);

		// individual XML files
		
		$contents = '';
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.xml$/', $filename))
			{	
				$full_filename = $basedir . '/' . $directory . '/' . $filename;
				
				$xml = file_get_contents($full_filename);
				
				// make sure XML document is on one line by replacing end of lines 
				$xml = preg_replace("/\R/", " ", $xml) . "\n";
							
				$contents .= $xml;
			}
		}
		
		// Store archive in modulo 10000 directory
				
		$destination_dir = floor($directory / 10);
		$path = 'lsid/' . join('/', $domain_path[$database]) . '/' . $destination_dir;
		
		if (!file_exists($path))
		{
			$oldumask = umask(0); 
			mkdir($path, 0777);
			umask($oldumask);
		}
		
		$path .= '/' . $directory . '.xml.gz';
		
		file_put_contents($path, gzencode($contents));
		
	}
}


?>


