# PHP publiccode.yml parser

PHP bindings for the [publiccode-parser-go](https://github.com/italia/publiccode-parser-go) library. Parse and validate `publiccode.yml` files in your PHP projects.

## Installation

You can install the package via composer:

```bash
composer require bfabio/publiccode-parser-php
```

The package will automatically build the required shared library during installation using the post-install-cmd script.

## Usage

### Basic parsing

```php
use Bfabio\PublicCodeParser\Parser;

$parser = new Parser();

// Parse from file
$publicCode = $parser->parseFile('/path/to/publiccode.yml');

// Parse from string
$yamlContent = file_get_contents('/path/to/publiccode.yml');
$publicCode = $parser->parse($yamlContent);

// Access parsed data
echo $publicCode->getName(); // Get software name
echo $publicCode->getDescription('it'); // Get Italian description
echo $publicCode->getDescription('en'); // Get English description
```

### Validation

```php
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\Exception\ValidationException;

$parser = new Parser();

try {
    $publicCode = $parser->parseFile('/path/to/publiccode.yml');
    echo "PublicCode file is valid!\n";
} catch (ValidationException $e) {
    echo "Validation failed: " . $e->getMessage() . "\n";

    // Get detailed validation errors
    foreach ($e->getErrors() as $error) {
        echo "- " . $error . "\n";
    }
}
```

### Working with the PublicCode object

```php
$publicCode = $parser->parseFile('/path/to/publiccode.yml');

$name = $publicCode->getName();
$url = $publicCode->getUrl();
$landingUrl = $publicCode->getLandingUrl();

$descriptionIt = $publicCode->getDescription('it');
$descriptionEn = $publicCode->getDescription('en');
$allDescriptions = $publicCode->getAllDescriptions();

$platforms = $publicCode->getPlatforms(); // ['web', 'android', 'ios', etc.]
```

### Advanced options

```php
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\ParserOptions;

// Create parser with custom options
$options = new ParserOptions();
$options->setDisableNetwork(true); // Disable remote file validation

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
