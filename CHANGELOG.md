# Changelog

All notable changes to the Conduit Component Skeleton will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive test suite for skeleton validation
- GitHub Actions CI/CD workflow for automated testing
- Detailed README with troubleshooting guide
- This CHANGELOG file for version tracking

### Changed
- **BREAKING**: Changed namespace from custom `ConduitComponents\*` to standard `App\`
- **BREAKING**: Changed autoload directory from `src/` to `app/`
- **BREAKING**: Removed `executable` field from manifest template
- Updated BaseCommand to extend Laravel Command directly
- Simplified DelegatedCommand to remove interface dependencies

### Removed
- Spotify-specific code (SpotifyService.php)
- Dependencies on non-existent interfaces (ConduitInterfaces, CommandInterface)
- Custom namespace generation in favor of standard `App` namespace

### Fixed
- "Unable to detect application namespace" error
- Component binary not found errors
- Interface dependency conflicts

## [1.0.0] - 2024-08-01

### Added
- Initial skeleton structure
- Base command classes
- Example command implementation
- Laravel Zero 12.0 support
- Component manifest template

---

## Guidelines for Future Releases

### Version Numbering
- **Major (X.0.0)**: Breaking changes to skeleton structure
- **Minor (0.X.0)**: New features, non-breaking improvements
- **Patch (0.0.X)**: Bug fixes, documentation updates

### When to Release
- After significant skeleton improvements
- When Laravel Zero major version updates
- After fixing critical bugs

### Release Process
1. Update version in composer.json
2. Update version in 💩.json template
3. Tag release on GitHub
4. Update this CHANGELOG