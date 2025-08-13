# 💩 Component Skeleton

[![Tests](https://github.com/conduit-ui/conduit-component/actions/workflows/test.yml/badge.svg)](https://github.com/conduit-ui/conduit-component/actions/workflows/test.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel Zero](https://img.shields.io/badge/Laravel%20Zero-12.0-red)](https://laravel-zero.com)

The official skeleton for creating components for THE SHIT ecosystem - a rapidly expandable developer toolkit.

## 🚀 Quick Start

### Using THE SHIT Scaffold Command (Recommended)

```bash
# From THE SHIT root directory
php 💩 component:scaffold my-component
```

### Manual Setup

```bash
# Clone the skeleton
git clone https://github.com/conduit-ui/conduit-component.git my-component
cd my-component

# Install dependencies
composer install

# Test it works
./component list
```

## 📁 Structure

```
my-component/
├── app/
│   └── Commands/       # Your component commands (App namespace)
├── tests/              # Pest tests
├── component           # Executable (always named 'component')
├── composer.json       # Dependencies and autoloading
└── 💩.json            # Component manifest
```

## 🎯 Key Principles

### 1. Laravel Zero Conventions
- **Namespace**: Always use `App\` namespace
- **Directory**: Commands go in `app/Commands/`
- **Executable**: Always named `component`
- **No custom namespaces**: Avoid `ConduitComponents\MyThing\`

### 2. Component Manifest (💩.json)
```json
{
    "name": "my-component",
    "description": "What your component does",
    "version": "1.0.0",
    "shit_acronym": "My Yarn Component",
    "commands": {
        "my-component:do-thing": "Description of what this command does"
    },
    "requires": {
        "php": "^8.2",
        "laravel-zero/framework": "^12.0"
    }
}
```

**Important**: No `executable` field! THE SHIT knows to look for `component`.

### 3. Command Structure

All commands should extend the provided base classes:

```php
namespace App\Commands;

class MyCommand extends BaseCommand
{
    protected $signature = 'do-thing {argument} {--option}';
    protected $description = 'Does an amazing thing';
    
    public function handle()
    {
        // Your logic here
        $this->info('Thing done!');
        return self::SUCCESS;
    }
}
```

## 🧪 Testing

The skeleton includes a comprehensive test suite:

```bash
# Run tests
./vendor/bin/pest

# With coverage
./vendor/bin/pest --coverage

# Run specific test
./vendor/bin/pest tests/Feature/SkeletonTest.php
```

## 🎨 Code Quality

```bash
# Format code
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test

# Static analysis
./vendor/bin/phpstan analyse
```

## 🔧 Development Workflow

1. **Create your component**
   ```bash
   php 💩 component:scaffold awesome-tool
   ```

2. **Add your commands**
   ```bash
   cd 💩-components/awesome-tool
   php component make:command DoSomethingCommand
   ```

3. **Update manifest**
   - Edit `💩.json` to list your commands
   - Use descriptive command names

4. **Test locally**
   ```bash
   ./component list
   ./component do-something
   ```

5. **Install in THE SHIT**
   - Component is auto-discovered if in `💩-components/` folder
   - Run `php 💩 list` to see your commands

## 📝 Common Issues & Solutions

### "Unable to detect application namespace"
**Solution**: Ensure `composer.json` has:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/"
    }
}
```

### "Component binary not found"
**Solution**: 
- File must be named `component` (not `my-component`)
- Must be executable: `chmod +x component`
- Don't add `executable` field to `💩.json`

### Commands not showing in THE SHIT
**Solution**: 
- Check `💩.json` lists your commands
- Ensure component is in `💩-components/` directory
- Component folder name should match manifest name

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing`)
5. Open a Pull Request

## 📄 License

MIT License - see LICENSE file for details

## 🙏 Credits

Built with [Laravel Zero](https://laravel-zero.com) by THE SHIT community.

---

*Remember: THE SHIT components should do one thing perfectly. Keep it simple, keep it focused.*