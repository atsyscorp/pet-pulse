<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Public site (landing page)
    |--------------------------------------------------------------------------
    |
    | Where the "Solicitar demostración" calls to action point. WhatsApp is an
    | international number without "+", spaces or dashes (e.g. 573001234567).
    | Leave it empty to show only the e-mail link.
    |
    */

    'contact_email' => env('PETPULSE_CONTACT_EMAIL', 'contacto@atsys.co'),
    'whatsapp' => env('PETPULSE_WHATSAPP'),

];
