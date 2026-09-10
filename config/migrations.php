<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Migration execution control
    |--------------------------------------------------------------------------
    |
    | The legacy schema baseline has been reviewed and its migration ledger
    | reconciled. Keep this disabled for normal controlled migrations; it can
    | be enabled again immediately if a schema incident requires a freeze.
    |
    */

    'frozen' => env('MIGRATIONS_FROZEN', false),

];
