CHANGELOG
=========

1.2.0
-----
- Added seedable installation support 
- lib for `elasticsearch` bumped to next major version
- README.md updated with indexing script
- Class properties automatically indexed
- Created separated indexes for Items, Tests, Test-Takers, Groups and Deliveries
- Added a query builder to handle searches on different indexes
- Updated indexes when custom properties are changed/deleted using IndexUpdater Interface.
- Added DACL to indexation
- Added User roles to the search query

1.1.1
-----
- Total count of results with Elasticsearch >= 7.0

1.1.0
-----
- Code style fixes
- New IndexerInterface

1.0.2
-----
- Fixed configs for map

1.0.0
-----
- For new search interface, requires tao-core >= v16.0.0
