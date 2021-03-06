<?php

namespace GmodStore\API;

use Exception;
use GmodStore\API\Endpoints\AddonEndpoint;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use function array_keys;
use function array_merge_recursive;
use function array_values;
use function call_user_func_array;
use function implode;
use function is_array;
use function lcfirst;
use function str_replace;
use function substr;

class Client
{
    /**
     * Base API URL.
     *
     * @var string
     */
    const API_URL = 'https://api.gmodstore.com/v2/';

    /**
     * Endpoint mappings used in __call() cause I'm lazy.
     *
     * @var array
     */
    protected static $endpoints = [
        'addon' => AddonEndpoint::class,
    ];

    /**
     * Aggregate Endpoint mappings to their singular endpoints used in __call() cause I'm lazy.
     *
     * @var array
     */
    protected static $aggregateEndpoints = [
        'addons' => AddonEndpoint::class,
    ];

    /**
     * Booted endpoints so they don't have to be instantiated again.
     *
     * @var array
     */
    protected static $bootedEndpoints = [];

    /**
     * @var string
     */
    protected $secret;

    /**
     * Client options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * @var array
     */
    protected $guzzleOptions = [];

    /**
     * Request method.
     *
     * @var string
     */
    protected $requestMethod = 'GET';

    /**
     * @var string
     */
    protected $endpointPath;

    /**
     * ?with relations to use.
     *
     * @var array
     */
    protected $withRelations = [];

    /**
     * ClientFixed constructor.
     *
     * @param       $secret
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct($secret, array $options = [])
    {
        if (is_array($secret)) {
            $options = $secret;
            $secret = $options['secret'] ?? null;
            unset($options['secret']);
        }

        if (empty($secret)) {
            throw new Exception('`$secret` not given');
        }

        $this->setSecret($secret);
        $this->parseOptions($options);
    }

    /**
     * @param array $options
     */
    protected function parseOptions(array $options = [])
    {
        if (isset($options['guzzle']) && $options['guzzle'] instanceof GuzzleClient) {
            $options['guzzleOptions'] = $this->guzzle->getConfig();
            $this->guzzle = $options['guzzle'];
        }

        $this->guzzleOptions = $options['guzzleOptions'] ?? [];

        $this->guzzle = new GuzzleClient($this->guzzleOptions);
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return \GmodStore\API\AggregateEndpoint|mixed
     */
    public function __call($name, $arguments)
    {
        $returnModel = false;

        if (substr($name, 0, 3) === 'get') {
            $returnModel = true;
            $name = lcfirst(substr($name, 3));
        }

        if (isset(self::$endpoints[$name]) || isset(self::$aggregateEndpoints[$name])) {
            if (!isset(self::$bootedEndpoints[$name])) {
                self::$bootedEndpoints[$name] = isset(self::$endpoints[$name]) ? new self::$endpoints[$name]($this, ...$arguments) : new self::$aggregateEndpoints[$name]($this);
            }

            if (isset(self::$aggregateEndpoints[$name])) {
                return new AggregateEndpoint(self::$bootedEndpoints[$name], $arguments);
            }

            return $returnModel ? call_user_func_array([self::$bootedEndpoints[$name], 'get'], $arguments) : self::$bootedEndpoints[$name];
        }

        throw new Exception('`'.$name.'` is not a valid method.');
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function setGuzzleOption($name, $value)
    {
        $this->guzzleOptions[$name] = $value;

        return $this;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function send()
    {
        return $this->guzzle->request($this->requestMethod, $this->endpointPath, $this->buildGuzzleOptions());
    }

    /**
     * @return array
     */
    protected function buildGuzzleOptions(): array
    {
        return array_merge_recursive($this->guzzleOptions, [
            'base_uri'    => self::API_URL,
            'headers'     => ['Authorization' => 'Bearer '.$this->getSecret()],
            'query'       => [
                'with' => implode(',', $this->withRelations),
            ],
            'synchronous' => RequestOptions::SYNCHRONOUS,
        ]);
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     *
     * @return \GmodStore\API\Client
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @param string $path
     * @param array  $params
     *
     * @return $this
     */
    public function setEndpoint($path, array $params = [])
    {
        $this->endpointPath = str_replace(array_keys($params), array_values($params), $path);

        return $this;
    }

    /**
     * @param array $with
     *
     * @return $this
     */
    public function setWith(array $with = [])
    {
        $this->withRelations = $with;

        return $this;
    }

    /**
     * @param string $requestMethod
     *
     * @return \GmodStore\API\Client
     */
    public function setRequestMethod(string $requestMethod)
    {
        $this->requestMethod = $requestMethod;

        return $this;
    }
}
