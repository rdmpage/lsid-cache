# Oxigraph

https://crates.io/crates/oxigraph_server


## Installing

### Local

Start the server in the same directory that you want the server’s files stored. If you cd to that directory, then:

```oxigraph_server -f .```

The endpoint is http://localhost:7878

### Cloud

Create a DigitalOcean droplet manually, open console, and then install Docker:

```
apt install docker.io
```

Then start the Oxigraph server

```
docker run -d --init --rm -v $PWD/data:/data -p 7878:7878 oxigraph/oxigraph -b 0.0.0.0:7878 -f /data
```

## Uploading data

Can upload LSIDs directly as XML, e.g.

```
curl http://143.198.96.145:7878/store?default -H 'Content-Type:application/rdf+xml' --data-binary '@77130.xml'
```

```
curl http://143.198.96.145:7878/store?default -H 'Content-Type:application/rdf+xml' --data-binary '@wfo.xml'
```

Using a named graph:

```
curl http://143.198.96.145:7878/store?graph=https://list.worldfloraonline.org -H 'Content-Type:application/rdf+xml' --data-binary '@wfo.xml'
```

## Remove triples

curl http://143.198.96.145:7878/update -X POST -H 'Content-Type: application/sparql-update' --data 'DELETE WHERE { ?s ?p ?o }' 

### Delete data from a specific source
curl http://143.198.96.145:7878/update -X POST -H 'Content-Type: application/sparql-update' --data 'DELETE WHERE { ?s <http://purl.org/dc/elements/1.1/creator> <http://www.organismnames.com> . ?s ?p ?o .  }' 


## Clear named graph

curl http://143.198.96.145:7878/update -X POST -H 'Content-Type: application/sparql-update' --data 'CLEAR GRAPH <https://list.worldfloraonline.org>'



———


PREFIX prefix: <http://prefix.cc/>
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
prefix : <http://rs.tdwg.org/ontology/voc/TaxonName#>
prefix tcom: <http://rs.tdwg.org/ontology/voc/Common#>
prefix wfo: <https://list.worldfloraonline.org/terms/>
SELECT * WHERE 
{ 
  ?s ?p "Hakea salicifolia" . 
  
  ?s ?x ?y .
}
  
PREFIX prefix: <http://prefix.cc/>
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
prefix : <http://rs.tdwg.org/ontology/voc/TaxonName#>
prefix tcom: <http://rs.tdwg.org/ontology/voc/Common#>
prefix wfo: <https://list.worldfloraonline.org/terms/>
SELECT * WHERE 
{ 
  ?s ?p "Hakea salicifolia" . 
  
  ?s ?x ?y .
}
  

PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
prefix tcom: <http://rs.tdwg.org/ontology/voc/Common#>

SELECT * WHERE {
  ?s <http://purl.org/dc/elements/1.1/creator> <http://www.organismnames.com> .
  ?s ?p ?o . 
} 
limit 10

PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX wfo: <https://list.worldfloraonline.org/terms/>
PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
PREFIX tcom: <http://rs.tdwg.org/ontology/voc/Common#>

SELECT * WHERE {
  ?name schema:sameAs ?ipni .
  ?name wfo:fullName ?fullName .
  ?ipni tcom:publishedIn ?publishedIn1 . 
  ?ipni tn:hasBasionym ?basionym . 
  ?basionym tn:nameComplete ?basionymName . 
  ?basionym tcom:publishedIn ?publishedIn2 . 
} 
LIMIT 10

### bad!!!

PREFIX prefix: <http://prefix.cc/>
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
prefix : <http://rs.tdwg.org/ontology/voc/TaxonName#>
prefix tcom: <http://rs.tdwg.org/ontology/voc/Common#>
prefix wfo: <https://list.worldfloraonline.org/terms/>
SELECT * WHERE 
{ 
  VALUES ?taxon { <https://wfo-list.rbge.info/wfo-0001230030-2019-05> }
  ?taxon wfo:fullName ?name .
  ?taxon wfo:hasSynonym ?synonym .
  ?synonym wfo:fullName ?fullName .
  ?synonym wfo:authorship ?authorship .
  OPTIONAL {
    ?synonym schema:sameAs ?ipni .
    ?ipni tcom:publishedIn ?pub .
  }
  
  

  
} 



