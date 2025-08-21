# PHP publiccode.yml parser

PHP bindings for the [publiccode-parser-go](https://github.com/italia/publiccode-parser-go) library. Parse and validate `publiccode.yml` files in your PHP projects.

## Installation

You can install the package via composer:

```bash
composer require bfabio/publiccode-parser-php
```

## Usage

### Validation

```php
use Bfabio\PublicCodeParser\Parser;

$parser = new Parser();

if ($parser->isValid('/path/to/publiccode.yml')) {
    echo "publiccode.yml is valid\n!";
} else {
    echo "publiccode.yml is NOT valid\n!";
};

// Access parsed data
echo $publicCode->getName(); // Get software name
echo $publicCode->getDescription('it'); // Get Italian description
echo $publicCode->getDescription('en'); // Get English description
```

### Parsing

```php
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\Exception\ValidationException;
use Bfabio\PublicCodeParser\Exception\ParserException;

$parser = new Parser();

try {
    $publicCode = $parser->parseFile('/path/to/publiccode.yml');

    // // or parse from string
    // $yamlContent = file_get_contents('/path/to/publiccode.yml');
    // $publicCode = $parser->parse($yamlContent);

    echo "publiccode.yml file is valid!\n";
} catch (ValidationException $e) {
    echo "publiccode.yml file is NOT valid: " . $e->getMessage() . "\n";

    // Get detailed validation errors
    foreach ($e->getErrors() as $error) {
        echo "- " . $error . "\n";
    }
} catch (ParserException $e) {
    echo "publiccode.yml file is NOT valid: " . $e->getMessage() . "\n";
}
```

### Working with the PublicCode object

```php
$parser = new Parser();

$publicCode = $parser->parseFile('/path/to/publiccode.yml');

$name = $publicCode->getName();
$url = $publicCode->getUrl();
$landingUrl = $publicCode->getLandingUrl();

$descriptionIt = $publicCode->getDescription('it');
$descriptionEn = $publicCode->getDescription('en');
$allDescriptions = $publicCode->getAllDescriptions();

$platforms = $publicCode->getPlatforms(); // ['web', 'android', 'ios', etc.]
```

### Advanced config

```php
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\ParserConfig;

// Create parser with custom config
$options = new ParserConfig();

$options->setDisableNetwork(true); // Disable remote existance checks for URLs

$parser = new Parser($options);
```

### Using in Drupal

Example of using the parser in a Drupal module:

```php
namespace Drupal\my_module\Service;

use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\Exception\ParserException;

class PublicCodeService {
    
    private $parser;
    
    public function __construct() {
        $this->parser = new Parser();
    }
    
    public function validatePublicCode($filePath) {
        try {
            $publicCode = $this->parser->parseFile($filePath);
            return [
                'valid' => true,
                'data' => $publicCode
            ];
        } catch (ParserException $e) {
            return [
                'valid' => false,
                'errors' => $e->getErrors()
            ];
        }
    }
}
```

## License

Licensed under the EUPL 1.2. Please see [License File](LICENSE) for more information.
