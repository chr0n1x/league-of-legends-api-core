<?php

namespace Riot\Api;

class Exception extends \Exception {

  public function __construct( $message = '', $code = 0, Exception $e ) {

    $this->message = ( empty( $message ) ) ? 'Unexpected API error' : $message;

    if ( !empty( $code ) ) {
      $consts = new Constants;
      $this->message .= "[{$code}]" . $consts->getMessageForApiResponse( $code );
    }

  } // __construct

} // Exception
