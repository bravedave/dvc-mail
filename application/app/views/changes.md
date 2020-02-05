### 05/02/2020
1. __imap\messages->safehtml()__
   * Seem to have discovered memory limitation in DOMDocument setAttribute
   * change to using str_replace which seem more robust
