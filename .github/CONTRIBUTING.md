# Contributing to Blaaiz Laravel SDK

Thank you for your interest in contributing to the Blaaiz Laravel SDK! We welcome contributions from the community.

## Code of Conduct
Please note that this project is released with a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.

## How Can I Contribute?

### Reporting Bugs
- Use the [Bug Report template](.github/ISSUE_TEMPLATE/bug_report.md)
- Include as much detail as possible
- Provide a minimal code example that reproduces the issue
- Include your environment information (PHP version, Laravel version, etc.)

### Suggesting Features
- Use the [Feature Request template](.github/ISSUE_TEMPLATE/feature_request.md)
- Explain the use case and benefits
- Provide example API usage
- Consider backwards compatibility

### Code Contributions

#### Development Setup
1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/blaaiz-laravel-sdk.git`
3. Install dependencies: `composer install`
4. Create a feature branch: `git checkout -b feature/your-feature-name`

#### Running Tests
```bash
# Run all tests
composer test

# Run specific test suites
vendor/bin/pest --filter="Unit"
vendor/bin/pest --filter="Integration"
vendor/bin/pest --filter="Feature"

# Run with coverage
vendor/bin/pest --coverage --min=80
```

#### Code Standards
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Add proper docblocks for public methods
- Write tests for all new functionality
- Maintain backwards compatibility unless it's a breaking change

#### Commit Messages
Use conventional commit format:
- `feat: add customer creation functionality`
- `fix: resolve issue with webhook signature validation`
- `docs: update installation instructions`
- `test: add integration tests for payouts`
- `refactor: improve error handling in services`

#### Pull Request Process
1. Ensure all tests pass locally
2. Update documentation if needed
3. Fill out the pull request template completely
4. Request review from maintainers
5. Address any feedback promptly

#### Testing Guidelines
- **Unit Tests**: Test individual classes/methods in isolation using mocks
- **Integration Tests**: Test actual API interactions (require API keys)
- **Feature Tests**: Test high-level functionality and workflows
- **Aim for 80%+ code coverage**

#### Documentation
- Update README.md if you add new features
- Add docblocks to public methods
- Include code examples for complex functionality
- Update changelog for significant changes

## Project Structure
```
src/
├── BlaaizClient.php           # HTTP client
├── Blaaiz.php                 # Main SDK class
├── BlaaizServiceProvider.php  # Laravel service provider
├── Exceptions/                # Custom exceptions
├── Facades/                   # Laravel facades
└── Services/                  # API service classes

tests/
├── Unit/                      # Unit tests
├── Integration/              # API integration tests
└── Feature/                  # Laravel feature tests

config/
└── blaaiz.php                # Configuration file
```

## API Guidelines
- Keep method signatures consistent across services
- Use proper type hints and return types
- Throw appropriate exceptions for error cases
- Follow Laravel conventions for service providers and facades

## Release Process
1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Create a GitHub release
4. Packagist will automatically update

## Getting Help
- Check existing issues and discussions
- Ask questions in GitHub Discussions
- Tag maintainers in issues for urgent matters

## Recognition
Contributors will be recognized in:
- CHANGELOG.md for significant contributions
- README.md contributors section
- Release notes

Thank you for contributing! 🎉