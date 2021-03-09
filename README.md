# LSID cache

Resolve Life Science Identifiers (LSIDs) using a local cache.

## Background 
[LSIDs](https://en.wikipedia.org/wiki/LSID) are a type of globally unique identifier that emerged from the life sciences community. It was adopted by several taxonomic databases in the mid 2000’s. When a LSID is resolved it returns information about the entity identified by that LSID (e.g., a taxonomic name), typically in [RDF](https://en.wikipedia.org/wiki/Resource_Description_Framework). For a variety of reasons the adoption of LSIDs has been limited. They are non-trivial to set up, require specialised software to resolve, and return RDF rather than human-readable content. However there are millions of LSIDs “in the wild” and this service aims to make them resolvable by storing the contents of each LSID to disk and providing a simple HTTP interface to resolve that LSID.

## How it works
I have built up an archive of the XML metadata for millions of LSIDs. A simple approach would be to simply put these millions of files onto a web server, but that number of files can quickly get messy. So I’ve grouped them into sets of up to 1000, gzipped them into archives, and put those on a server. When you request a LSID the service works out which archive has the metadata you want, extracts the metadata from that archive, and sends you the metadata (in the format you requested).



