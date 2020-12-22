# No Longer Maintained

# Elasticpress Autosuggest Endpoint

### Setup:
- Elasticpress PHP Client is necessary (for Wordpress, see [ElasticPress by 10up](https://github.com/10up/ElasticPress))
- Add elasticsearch index mapping
```
PUT <index>/_mapping
{
  "properties": {
    "post_content": {
      "type": "text",
      "fields": {
        "term_suggest": {
          "type": "text",
          "analyzer": "shingle_analyzer"
        }
      }
    }
  }
}
```

### Elasticpress Autosuggest Settings:
- Default endpoint is http(s)://yourdomainname.com/wp-json/elasticpress/autosuggest/ 
- Use the default endpoint (or whatever you specified in register_rest_route) as the endpoint URL in the admin (ElasticPress / Autosuggest / Settings).
