# conduit-spotify

A Conduit component for Conduit spotify integration functionality with universal output format support.

## 🚀 Features

- **Universal Output Formats**: Terminal, JSON, and table formats
- **Smart Detection**: Auto-detects piped output for jq compatibility  
- **File Export**: Save output to files with `--output` option
- **Liberation Metrics**: Performance and efficiency tracking
- **Developer Experience**: Beautiful CLI interface with Laravel Prompts

## 📦 Installation

### Via Conduit (Recommended)
```bash
conduit install conduit-spotify
```

### Via Composer
```bash
composer global require jordanpartridge/conduit-spotify
```

## 🎯 Usage

### Basic Commands
```bash
# Run with beautiful terminal output
{{BINARY_NAME}} example

# Get JSON output for automation
{{BINARY_NAME}} example --format=json

# Export to file
{{BINARY_NAME}} example --output=data.json

# Pipe to jq (auto-detected)
{{BINARY_NAME}} example | jq '.[] | .name'
```

### Universal Format Support

Every command automatically supports:

- **Terminal**: Human-readable with emojis and colors
- **JSON**: Machine-readable for automation and jq processing
- **Table**: Structured data display with Laravel Prompts
- **File Output**: Export capabilities for reports and analysis

## 🏗️ Architecture

This component is built on the **Conduit Liberation Architecture**:

### AbstractConduitCommand Foundation
All commands extend `AbstractConduitCommand` which provides:

```php
// Automatic format routing
php {{BINARY_NAME}} command --format=json
php {{BINARY_NAME}} command --format=table  
php {{BINARY_NAME}} command | jq '.[]'  # Auto-detects piping!
```

### Universal Interface Pattern
```php
class MyCommand extends BaseCommand
{
    // Provide data
    public function getData(): array { /* ... */ }
    
    // Custom terminal output
    public function outputTerminal(array $data): int { /* ... */ }
    
    // JSON and table formats provided automatically!
}
```

## 🔧 Development

### Commands
```bash
# Install dependencies
composer install

# Code formatting  
./vendor/bin/pint

# Run tests
./vendor/bin/pest

# Component certification
./component certify
```

### Liberation Philosophy

This component follows the **Developer Liberation System** principles:

- **Eliminate Repetition**: Universal formats remove CLI boilerplate
- **Embrace Automation**: Perfect jq and tool integration  
- **Measure Impact**: Built-in liberation metrics
- **Consistent Experience**: Same patterns across all components

## 📊 Liberation Metrics

Run any command with `-v` to see liberation metrics:

```bash
{{BINARY_NAME}} example -v
# ⚡ Liberation Metrics:
#    Time saved: 2.5s per execution
#    Complexity reduction: 80%
#    Execution time: 0.045s
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following the Conduit architecture patterns
4. Run tests: `./vendor/bin/pest`
5. Run certification: `./component certify`
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built on [Laravel Zero](https://laravel-zero.com/) framework
- Powered by [Conduit Universal Interfaces](https://github.com/jordanpartridge/conduit-interfaces)
- Part of the [Conduit Developer Liberation System](https://github.com/jordanpartridge/conduit)