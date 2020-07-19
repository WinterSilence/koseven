<?php

/**
 * The following options must be set:
 * string   type    Driver Type
 * string   key     Secret Passphrase
 */
return [
    'default' => [
        'type' => 'openssl',
        'key' => null,
        'cipher' => Encrypt_Engine_Openssl::AES_256_CBC,
    ],
    //	  'sodium' => [
    //		'type'    => 'sodium',
    //		'key'     => NULL,
    //		'cipher'  => Encrypt_Engine_Sodium::AES_256_GCM
    //    ]
    //    /**
    //     * Mcrypt is deprecated and should not be used,
    //     * however it requires additional options:
    //     *
    //     * integer  mode    encryption mode, one of MCRYPT_MODE_*
    //     * integer  cipher  encryption cipher, one of the Mcrypt cipher constants
    //     */
    //    'mcrypt' => [
    //        'type'   => 'mcrypt',
    //        'key'    => NULL,
    //        'cipher' => MCRYPT_RIJNDAEL_128,
    //        'mode'   => MCRYPT_MODE_CBC,
    //    ],
];
