<?php

namespace Riot;

use Httpful\Request;
use Riot\Api\Exception;

abstract class Api {

  const DEFAULT_HOST = 'prod.api.pvp.net';

  /**
   * @var string
   */
  protected $uriTemplate = '/api/lol/{region}/{version}/{endpoint}';

  /**
   * @var Riot\Constants
   */
  protected $constants;

  /**
   * Array of params to be injected into URI template
   * keys are {key} in template
   * values are what should be injected
   *
   * @var array
   */
  protected $defaultParams;

  /**
   * Whether this endpoint requires a region to be specified
   *
   * @var bool
   */
  protected $requiresRegion = true;

  /**
   * API key to be used with the request
   *
   * @var string
   */
  protected $apiKey;

  /**
   * @param string $apiKey Developer API key
   * @param string $region Region that calls should be made for only required if $this->requiresRegion
   * @throws \InvalidArgumentException If no API key given or if $this->requiresRegion && no region given
   * @throws \LogicException if overridden availableRegions() returns an array of values that are not a subset of $this->regions
   */
  public function __construct( $apiKey, $region = 'na', $host = '' ) {

    if ( empty( $apiKey ) ) {
      throw new \InvalidArgumentException( 'No api key given' );
    }

    $this->apiKey = $apiKey;

    if ( $this->requiresRegion && !in_array( $region, $this->availableRegions() ) ) {
      throw new \InvalidArgumentException( 'Invalid region given, or default value of "na" is incompatible.' );
    }

    $this->region = $region;
    $this->constants = new Constants;

    $intersect = array_intersect( array_values( $this->constants->getRegions() ), $this->availableRegions() );

    if ( array_values( $intersect ) !== $this->availableRegions() ) {
      $unknown = array_diff( $this->availableRegions(), $intersect );
      $unknown = implode( ',', $unknown );
      throw new \LogicException( "Unknown region(s) given: [ {$unknown} ]");
    }

    $this->defaultParams = $this->getDefaultParams();
    $this->host = ( empty( $host ) ) ? static::DEFAULT_HOST : $host;

  } // __construct

  /**
   * @return string Version of this object
   */
  public function getVersion() {

    return static::VERSION;

  } // getVersion

  /**
   * @return string Name of the endpoint
   */
  public function getEndpoint() {

    return static::ENDPOINT;

  } // getVersion

  /**
   * @todo: this is garbage, clean it.
   * @param array $params
   * @param string $functionEnd
   * @param bool $https
   * @return string
   */
  protected function buildEndpointUri( array $params = array(), $functionEnd = '', $https = true ) {

    $params    = array_merge( $this->defaultParams, $params );
    $ssl       = ( $https ) ? 'https' : 'http';
    $endpoint  = $ssl . '://' . $this->host . '/' . ltrim( $this->uriTemplate, '/' );
    $endpoint  = rtrim( $endpoint, '/' );
    $endpoint .= ( empty( $functionEnd ) ) ? '' : '/' . ltrim( $functionEnd, '/' );
    $endpoint  = rtrim( $endpoint, '/' );

    foreach ( $params as $var => $val ) {
      $endpoint = str_replace( "{{$var}}", $val, $endpoint );
    }

    return "{$endpoint}?api_key={$this->apiKey}";

  } // buildEndpointUri

  /**
   * Returns default params to get injected into $this->uriTemplate
   *
   * @return array
   */
  protected function getDefaultParams() {

    return array(
        'version' => $this->getVersion(),
        'endpoint'=> $this->getEndpoint(),
        'region'  => $this->region
    );

  } // getDefaultParams

  /**
   * @param string $uri
   * @param string $method Httpful\Request static fx
   * @return Httpful\Response
   */
  protected function request( $uri, $method = 'get' ) {

    $response = Request::{$method}( $uri )->send();

    if ( $response->code !== 200 ) {
      throw new Exception( strtoupper( $method ) . "[{$uri}]", $response->code );
    }

    return $response;

  } // request

  /**
   * Define default action. It is encouraged that this function acts as a router function
   */
  abstract public function defaultAction();

  /**
   * Returns a subset of $this->regions
   *
   * @return array
   */
  abstract public function availableRegions();

} // Api
