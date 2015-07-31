NEOS Playground
===============

Nodecruncher
------------

Use this ComamndController to create some NEOS Pages in bulk-mode for performance benchmarking and other purposes.

### Usage Example

```
while true ; do ./flow nodecruncher:create 200 --page "$(date)" --verbose --main-count 20 --batch-size 10 ; done
```

This will create 200 TYPO3.Neos:Document Pages having 20 Text Nodes in the main ContentCollection each (total
of 4201 nodes).

