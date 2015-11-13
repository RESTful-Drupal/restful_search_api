[![Build Status](https://travis-ci.org/RESTful-Drupal/restful_search_api.svg?branch=7.x-2.x)](https://travis-ci.org/RESTful-Drupal/restful_search_api)

# RESTful Search API
Expose your Search API results with your RESTful API.

## Features
Building a search engine integration with faceted search in Drupal is really
easy with Search API and all the helper modules around it. With this module you
can expose your search to your decoupled consumer.

### Get the output that you want for your search
This falls in line with the philosophy of the RESTful module, where you get the
exact output that you need. To do so you need to be declarative about it by
implementing `publicFieldsInfo`.

First of all configure your search index in Drupal using Search API to index the
fields that you want to make searchable and you want to output. Once you have
Search API configured, implement `publicFieldsInfo` to control how you expose
each field that you need to expose with your HTTP API. 

#### How do I know the properties to map to?
You will notice in the examples that you have to indicate the name of the
property. Some times it is complicated to know what properties you have
available in the search results that Search API returns, to be mapped by
RESTful. In those situations it is helpful to use the `pass_through` option in
your resource definition. That will output every available property regardless
of the mappings that you have done in `publicFieldsInfo`. 

### Sort by any public property
You just need to provide the sort query string to sort your results:

```
curl https://www.example.org/api/search/lorem?sort=-created,id
```

Prepend a `-` in front of the sort key to sort in descending order.

You can use any property that supports sorting in your search index, that will
be passed to Search API.

Additionally you can use any public property in your final output to sort the
results. If the selected property does not support sorting in Search API the
sorting will be applied to the output generated after making the query to Search
API without sorting. Use this type of sorting only if you really need to, the
preferred method is to use the supported sort properties in Search API.

### Filter by your facets
Add some facets to the Search API configuration and use them in your search
queries. To do so use
[the same format used in RESTful](https://github.com/RESTful-Drupal/restful#filter-1).

If a search has relevant facet information attached, those will be sent along
with the search results.

```javascript
// https://www.example.org/api/basic_search/elit?filter[comment_count][value]=2&filter[comment_count][operator]=">="
{
    "count": 23,
    "facets": {
        "author": [
            { "filter": "\"0\"", "count": 12 },
            { "filter": "\"1\"", "count": 11 }
        ],
        "comment_count": [
            { "filter": "\"2\"", "count": 13 },
            { "filter": "\"3\"", "count": 10 }
        ]
    },
    "data": [
        { "entity_id": 704, "version_id": 704, "relevance": 0.013727446 },
        …
    ]
}
```
## Additional information
See [this post in Medium](https://medium.com/@e0ipso/restful-drupal-with-search-api-f370050a26bb) with some additional information.
