# Contributing to MASS CC Checker

First off, thank you for considering contributing to MASS CC Checker! 🎉

## Code of Conduct

This project and everyone participating in it is governed by respect and professionalism. Please be kind and courteous to all contributors.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

* **Use a clear and descriptive title**
* **Describe the exact steps to reproduce the problem**
* **Provide specific examples** to demonstrate the steps
* **Describe the behavior you observed** and what you expected to see
* **Include screenshots** if applicable
* **Specify your environment** (PHP version, browser, OS)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, include:

* **Use a clear and descriptive title**
* **Provide a detailed description** of the suggested enhancement
* **Explain why this enhancement would be useful**
* **List examples** of where similar features exist in other tools

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Make your changes** following the coding standards below
3. **Test your changes** thoroughly
4. **Update documentation** if needed
5. **Write clear commit messages**
6. **Submit a pull request** with a comprehensive description

## Development Setup

1. Clone your fork of the repository
```bash
git clone https://github.com/YOUR-USERNAME/MASS-CC-CHECKER.git
cd MASS-CC-CHECKER
```

2. Make sure you have PHP 7.4+ installed
```bash
php --version
```

3. Start a local PHP server for testing
```bash
php -S localhost:8000
```

## Coding Standards

### PHP
* Follow PSR-12 coding standards
* Use meaningful variable and function names
* Add comments for complex logic
* Keep functions small and focused
* Validate all user input
* Use prepared statements for database queries (if applicable)
* Handle errors gracefully

### JavaScript
* Use ES6+ features where appropriate
* Use meaningful variable names (camelCase)
* Add comments for complex logic
* Keep functions small and focused
* Handle errors with try-catch blocks

### HTML/CSS
* Use semantic HTML5 elements
* Keep CSS organized and commented
* Use responsive design principles
* Ensure accessibility (ARIA labels, alt text, etc.)

## Security

* **Never commit sensitive data** (API keys, passwords, etc.)
* **Validate all user input** on both client and server side
* **Use prepared statements** for any database queries
* **Implement rate limiting** for API endpoints
* **Follow OWASP security guidelines**

⚠️ **Important**: This tool is for **educational purposes only**. Do not use it for illegal activities.

## Testing

Before submitting a pull request:

1. Test all functionality manually
2. Test with different card formats and edge cases
3. Test error handling
4. Test rate limiting
5. Test on different browsers (Chrome, Firefox, Safari)
6. Test on mobile devices

## Documentation

* Update README.md if you change functionality
* Add comments to your code
* Update inline documentation
* Include examples in your pull request description

## Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally after the first line

### Examples:
```
Add export functionality for results
Fix rate limiting not working correctly
Update README with new features
```

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (see LICENSE file).

## Questions?

Feel free to open an issue with your question or contact the maintainer through GitHub.

---

Thank you for contributing! 🙏
