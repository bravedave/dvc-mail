<?php
/**
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 *      http://creativecommons.org/licenses/by/4.0/
 *
** */

use dvc\mail\config as mailconfig;

?>
<form method="POST" action="<?= strings::url() ?>">
    <input type="hidden" name="action" value="save-settings" />

    <div class="form-group row">
        <div class="col">
            <div class="form-check">
                <input type="radio" name="mode" class="form-check-input" value="imap"
                    id="<?= $uid = strings::rand() ?>"
                    <?php if ( 'imap' == mailconfig::$MODE) print 'checked' ?>
                    />

                <label class="form-check-label" for="<?= $uid ?>">
                    IMap

                </label>

            </div>

            <div class="form-check">
                <input type="radio" name="mode" class="form-check-input" value="ews"
                    id="<?= $uid = strings::rand() ?>"
                    <?php if ( 'ews' == mailconfig::$MODE) print 'checked' ?>
                    />

                <label class="form-check-label" for="<?= $uid ?>">
                    Exchange Web Services

                </label>

            </div>

        </div>

    </div>

    <div class="row">
        <div class="col">
            <button class="btn btn-primary">save</button>

        </div>

    </div>

</form>