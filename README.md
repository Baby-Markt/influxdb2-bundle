# InfluxDB2 Symfony Bundle

Symfony bundle integration of the official [InfluxDB 2.x client](https://github.com/influxdata/influxdb-client-php).

![Build 1.0.x](https://github.com/Baby-Markt/influxdb2-bundle/actions/workflows/php.yml/badge.svg?branch=1.0.x-dev)
[![codecov](https://codecov.io/gh/Baby-Markt/influxdb2-bundle/branch/1.0.x-dev/graph/badge.svg?token=N8MOLOBNW9)](https://codecov.io/gh/Baby-Markt/influxdb2-bundle)
[![Packagist Version](https://img.shields.io/packagist/v/babymarkt/influxdb2-bundle)](https://packagist.org/packages/babymarkt/influxdb2-bundle)
[![License](https://img.shields.io/github/license/Baby-Markt/influxdb2-bundle.svg)](https://github.com/Baby-Markt/influxdb2-bundle/blob/master/LICENSE)
![PHP from Packagist](https://img.shields.io/packagist/php-v/babymarkt/influxdb2-bundle)

#### Note: Use this symfony bundle with InfluxDB 2.x and InfluxDB 1.8+ ([see details on client repo](https://github.com/influxdata/influxdb-client-php/blob/master/README.md#influxdb-18-api-compatibility)).

## Installation

You need to require this library through composer:

```bash
composer require babymarkt/influxdb2-bundle
```

If you are using [Symfony Flex](https://github.com/symfony/flex), the following will happen automatically. Otherwise,
you have to enable the bundle on the `AppKernel` class manually:

```php
// config/bundles.php
return [
    // ...
    Babymarkt\Symfony\Influxdb2Bundle\BabymarktInfluxdb2Bundle::class => ['all' => true],
];

```

## Configuration

Let's start with a minimal setup:

```yaml
babymarkt_influxdb2:
  clients:
    connections:
      default:
        url: https://localhost:8086
```

Or in the short version:

```yaml
babymarkt_influxdb2:
  clients:
    url: https://localhost:8086
```

This creates the following services for you:

* a InfluxDb2 Client `babymarkt_influxdb2.default_client`
* a default Write-API `babymarkt_influxdb2.default_write_api`
* and a default Query-API `babymarkt_influxdb2.default_query_api`

Full configuration reference:

```yaml
babymarkt_influxdb2:

  # The clients will be named by connection names.
  clients:
    # If not set, the first connection will be taken.
    default_connection: ~
    connections:
      your_client_name:

        # InfluxDB server API url (ex. http://localhost:8086).
        url: ~ # Required

        # Auth token to access your instance.
        token: ~

        # Default destination bucket for writes.
        bucket: ~

        # Default organization bucket for writes.          
        org: ~

        # Precision for the unix timestamps within the body line-protocol.
        precision: 'ns'

        # Turn on/off SSL certificate verification. Set to `false` to disable certificate verification.
        verifySSL: true

        # Enable verbose logging of http requests.
        debug: false

        # Log output.
        logFile: ~

        # Default tags.
        tags:
          - first-tag
          - second-tag
          #...

        # The number of seconds to wait while trying to connect to a server. Use 0 to wait indefinitely.
        timeout: 10

        # Pass ~ string to specify an HTTP proxy, or an array to specify different proxies for different protocols.
        proxy: ~

        # GuzzleHttp Client options to allow following redirects.
        allow_redirects:
          max: 5
          strict: ~
          referer: ~
          protocols: [ 'http', 'https' ]

  apis:
    write:
      # If not set, the first option_set will be taken.
      default_option_set: ~
      option_sets:
        # The Write-API name
        your-optionset-name:
          # The client connection to use for writes. Defaults to the default_connection.
          connection: ~

          # The Write-API options (see https://github.com/influxdata/influxdb-client-php#writing-data)
          options:
            # (writeType) Type of write SYNCHRONOUS / BATCHING.
            write_type: ~

            # (batchSize) The number of data point to collect in batch.
            batch_size: ~

            # (retryInterval) The number of milliseconds to retry unsuccessful write. The retry interval is "exponentially" used when the InfluxDB server does not specify "Retry-After" header.
            retry_interval: ~

            # (jitterInterval) The number of milliseconds before the data is written increased by a random amount.
            jitter_interval: ~

            # (maxRetries) The number of max retries when write fails.
            max_retries: ~

            # (maxRetryDelay) Maximum delay when retrying write in milliseconds.
            max_retry_delay: ~

            # (maxRetryTime) Maximum total retry timeout in milliseconds.
            max_retry_time: ~

            # (exponentialBase) The base for the exponential retry delay.
            exponential_base: ~
```

## Service usage

### Clients
Default client injection by class name: 
```php
namespace App;
class GenericMetricsWriter {
    public function __construct(protected \InfluxDB2\Client $client) {
        // ...
    }
}
```

Specific clients can be retrieved by injecting via service definition:
```yaml
services:
  App\GenericMetricsWriter:
    arguments: ['@babymarkt_influxdb.your_client_name_client']
```

or by getting from client registry:

```php
namespace App;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
class GenericMetricsWriter {
    public function __construct(protected ClientRegistry $registry) {
        /** @var \InfluxDB2\Client $client */
        $client = $this->registry->getClient('your_client_name');
        // ...
    }
}
```

### APIs
In the same way, you get the Write- and Query-APIs:
```php
namespace App;
class GenericMetricsWriter {
    // Injects the default Write- and Query-API.
    public function __construct(
        protected \InfluxDB2\WriteApi $writeApi,
        protected \InfluxDB2\QueryApi $queryApi) {
        // ...
    }
}
```
Specific APIs can be retrieved by injecting via service definition:
```yaml
services:
  App\GenericMetricsWriter:
    arguments:
      - '@babymarkt_influxdb.your_name_write_api'
      - '@babymarkt_influxdb.your_name_query_api'
```
or by getting from API registry:
```php
namespace App;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ApiRegistry;
class GenericMetricsWriter {
    public function __construct(protected ApiRegistry $registry) {
        /** @var \InfluxDB2\WriteApi $writeAPi */
        $writeAPi = $this->registry->getWriteApi('your_name');
        /** @var \InfluxDB2\QueryApi $queryApi */
        $queryApi = $this->registry->getQueryApi('your_name');
        // ...
    }
}
```

### Additional InfluxDB2 APIs
The official InfluxDB2 client library provides many additional API services. Although no Symfony services are defined 
for these, they can be obtained at any time via a client instance and require no further configuration.

Here is an example on the ReadyService that returns the status of a InfluxDB2 instance:
```php
namespace App;
use InfluxDB2\Service\HealthService;class GenericMetricsWriter {
    public function __construct(protected \InfluxDB2\Client $client) {
        /** @var ReadyService $readyService */
        $readyService = $this->client->createService(ReadyService::class);
        $ready = $readyService->getReady();
        echo $ready->getStatus(); // => "ready"
    }
}
```
For more information, see the [API documentation](https://docs.influxdata.com/influxdb/v2.1/reference/api/) of InfluxDB2.

## Console Commands
This bundle comes with some console commands for managing entities via the InfluxDB2 API. All commands have 
the option `--client`|`-c` to select the InfluxDB2 client to use.

### `babymarkt_influxdb:setup`
Sets up the initial user, organisation and bucket for a new instance.
```
Options:
  -c, --client[=CLIENT]      The client to use. [default: "default"]
      --user[=USER]          Initial username
      --password[=PASSWORD]  Initial user password
      --org[=ORG]            Initial organisation name
      --token[=TOKEN]        Initial admin token
      --bucket[=BUCKET]      Initial bucket
      --duration[=DURATION]  Initial bucket duration [default: 0]
```
### `babymarkt_influxdb:ping`
Checks the status and version of an InfluxDB instance.
```
Options:
  -c, --client[=CLIENT]      The client to use. [default: "default"]
```
### `babymarkt_influxdb:ready`
Get the readiness of an instance at startup.
```
Options:
  -c, --client[=CLIENT]      The client to use. [default: "default"]
```
### `babymarkt_influxdb:buckets:list`
Lists all available buckets of an instance.
```
Options:
  -c, --client[=CLIENT]      The client to use. [default: "default"]
```
### `babymarkt_influxdb:buckets:retrieve`
Provides all information about a bucket.
```
Arguments:
  bucket                 The bucket name or ID.

Options:
  -c, --client[=CLIENT]  The client to use. [default: "default"]
```
### `babymarkt_influxdb:buckets:create`
Creates a new bucket.
```
Options:
  -c, --client[=CLIENT]            The client to use. [default: "default"]
      --name[=NAME]                The bucket name.
      --description[=DESCRIPTION]  The bucket description.
      --org[=ORG]                  The organization name or id.
      --duration[=DURATION]        The duration in seconds for how long data will be kept in the database. 0 means infinite. [default: 0]
      --schema-type[=SCHEMA-TYPE]  The schema type. Allowed values are "implicit" or "explicit". [default: "implicit"]
```
### `babymarkt_influxdb:buckets:update`
Updates an existing bucket.
```
Arguments:
  bucket                           The bucket name or ID to update.

Options:
  -c, --client[=CLIENT]            The client to use. [default: "default"]
      --name[=NAME]                The bucket name.
      --description[=DESCRIPTION]  The bucket description.
      --duration[=DURATION]        The duration in seconds for how long data will be kept in the database. 0 means infinite. [default: 0]
```
### `babymarkt_influxdb:buckets:delete`
Deletes an existing bucket.
```
Arguments:
  bucket                 The bucket name or id.

Options:
  -c, --client[=CLIENT]  The client to use. [default: "default"]
```
### `babymarkt_influxdb:orgs:list`
Lists all available organizations of an instance.
```
Options:
  -c, --client[=CLIENT]      The client to use. [default: "default"]
```
### `babymarkt_influxdb:orgs:retrieve`
Provides all information about an organization.
```
Arguments:
  org                    The organization name or ID.

Options:
  -c, --client[=CLIENT]  The client to use. [default: "default"]
```
### `babymarkt_influxdb:orgs:create`
Creates a new organization.
```
Options:
  -c, --client[=CLIENT]            The client to use. [default: "default"]
      --name[=NAME]                The organization name.
      --description[=DESCRIPTION]  The organization description.
```
### `babymarkt_influxdb:orgs:update`
Updates an existing organization.
```
Arguments:
  org                              The organization name or ID.

Options:
  -c, --client[=CLIENT]            The client to use. [default: "default"]
      --name[=NAME]                The organization name.
      --description[=DESCRIPTION]  The organization description.
```
### `babymarkt_influxdb:orgs:delete`
Deletes an existing organization.
```
Arguments:
  org                    The organization name or ID.

Options:
  -c, --client[=CLIENT]  The client to use. [default: "default"]
```

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/Baby-Markt/influxdb2-bundle.

## License

The bundle is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).
