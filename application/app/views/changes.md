###### 20/03/2020
* fixed bug large mailboxes (> 500 msgs) would not go to page above 0
###### 20/03/2020
* added mail info trigger

###### 17/03/2020
* better display of forarded flag

###### 16/03/2020
* restrict the use of popstate to just mobiles where it assist navigation

###### 9/03/2020
* Added attachment name and contentn id to images so they can be extracted as attachments from the email

###### 9/03/2020
* Better checking for plain text email and simplier display
* License updated to MIT

###### 21/02/2020
1. imap_sort seems to struggle with large mailbox, particularly sent items
   * if there are more than 500, revert to using simpler routine

###### 21/02/2020
1. Searching for multiple words
   * by default a word is a term "Buy Offer" searchs for that phrase
   * separate by comma for a word list. e.g. Buy, Offer will find "Offer To Buy"

###### 20/02/2020
1. Introducing History stateFull API
   * when viewing a message, the state is pushed to history, the browsers back arrow now goes back to the message list

###### 11/02/2020
1. Adjusted cache time
2. Mitigated Cache Cleanup to only run every 5 minutes

###### 10/02/2020
1. Search feature
   a. now includes TEXT in search field
   b. added navigation to allow exiting search and returning to normal mail view
   c. Some searchs will be more successul than others
      * e.g.
        * the shorter the word the less likely it is to be unique - 3 letter words will probably yeild unexpected results
        * soft is probably going to take a long time, because all html messages contain the words microsoft - soft is a derivative
        * soft pillow will be more success because that term is unique
      * Having said all that, I have done work on looking extracting the the text of the message for results, but the results are slower
2. Bugs
   a. fixed glitch in naming of cache objects

###### 09/02/2020
1. New Quick Reply feature - incomplete and disabled by default

###### 08/02/2020
1. Fixed bug
   * with intro of caching, the _read_ status was being taken from cache
     * when marking as _read_ flush the cache
     * seen flag is read each parse, so if another client marks as seen, we also see
   * fix issue if two users were cleaning up cache simultaneously

###### 06/02/2020
1. Fixed bug introduced yesterday - message images were not being indexed properly
2. Move away from message id to uid as a method of accessing the message
3. Introduce caching to speed message header retrieval
4. Introduce optional flushing of the cache
   * there seems to state that expunge resets uid's - I don't think that is the case, if I'm wrong will need to turn this on

###### 05/02/2020
1. __imap\messages->safehtml()__
   * Seem to have discovered memory limitation in DOMDocument setAttribute
   * change to using str_replace which seem more robust
