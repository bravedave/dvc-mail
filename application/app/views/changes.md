### 06/02/2020
1. Fixed bug introduced yesterday - message images were not being indexed properly
2. Move away from message id to uid as a method of accessing the message
3. Introduce caching to speed message header retrieval
4. Introduce optional flushing of the cache
   * there seems to state that expunge resets uid's - I don't think that is the case, if I'm wrong will need to turn this on

### 05/02/2020
1. __imap\messages->safehtml()__
   * Seem to have discovered memory limitation in DOMDocument setAttribute
   * change to using str_replace which seem more robust
