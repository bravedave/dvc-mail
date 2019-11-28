/**
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 *      http://creativecommons.org/licenses/by/4.0/
 *
 */
/*jshint esversion: 6 */
(function() {
    String.prototype.getEmail = function() {
        if (this.length < 3) {
            return '';

        }

        /**
         *	if the email is in format
         *	"David Bray <david@brayworth.com.au>",
         *	strip all before the <
         */

        let e = String(this);
        if (/</.test(e) && />$/.test(e)) {
            e = String( e.replace(/^.*</, '').replace(/>$/, ''));

        }

        // console.log( e);

        // let rex = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
        // https://stackoverflow.com/questions/46155/how-can-an-email-address-be-validated-in-javascript
        let rex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        if (/.con$/i.test(e.toLowerCase())) {
            return '';	// dickhead

        }

        if ( rex.test( e.toLowerCase())) {
            return e;

        }

    };

})();