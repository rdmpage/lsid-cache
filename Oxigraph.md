# Oxigraph


## Installing

Create a DigitalOcean droplet manually, open console, and then:

```
apt install docker.io

docker run -d --init --rm -v $PWD/data:/data -p 7878:7878 oxigraph/oxigraph -b 0.0.0.0:7878 -f /data
```

## Uploading data

Can upload LSIDs directly as XML, e.g.

```
curl http://143.198.96.145:7878/store?default -H 'Content-Type:application/rdf+xml' --data-binary '@77130.xml'
```

## Remove triples

curl http://143.198.96.145:7878/update -X POST -H 'Content-Type: application/sparql-update' --data 'DELETE WHERE { ?s ?p ?o }' 
