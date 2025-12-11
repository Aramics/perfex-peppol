# Peppol Module Documentation

This directory contains the complete documentation for the Perfex CRM Peppol Module, built with MkDocs.

## Setup

### Prerequisites

```bash
pip install mkdocs mkdocs-material
```

### Local Development

```bash
# Navigate to the peppol module directory
cd modules/peppol

# Start the documentation server
mkdocs serve
```

The documentation will be available at `http://127.0.0.1:8000/`

### Building Static Documentation

```bash
# Build static HTML files
mkdocs build

# Output will be in site/ directory
```

## Documentation Structure

```
docs/
├── index.md                    # Main documentation homepage
├── getting-started/
│   ├── installation.md         # Installation guide
│   ├── configuration.md        # Configuration setup
│   └── first-steps.md          # Quick start guide
├── features/
│   ├── document-management.md  # Document sending/receiving
│   ├── directory-lookup.md     # Directory lookup feature (NEW)
│   ├── provider-integration.md # Provider setup
│   └── status-management.md    # Document status handling
├── user-guides/
│   ├── sending-documents.md    # How to send documents
│   ├── managing-directory.md   # Directory management guide (NEW)
│   ├── processing-responses.md # Response handling
│   └── creating-expenses.md    # Expense creation
├── administration/
│   ├── settings.md            # Module settings
│   ├── providers.md           # Provider management
│   ├── logs.md                # Log management
│   └── automation.md          # Automated processes
├── developer/
│   ├── architecture.md        # Technical architecture
│   ├── extending.md           # Extending the module
│   ├── custom-providers.md    # Creating providers
│   └── hooks.md               # Hook system
├── api/
│   ├── core-classes.md        # Core class reference
│   ├── providers.md           # Provider API
│   ├── models.md              # Model documentation
│   └── helpers.md             # Helper functions
├── troubleshooting.md         # Common issues
└── faq.md                     # Frequently asked questions
```

## New Directory Lookup Documentation

The documentation includes comprehensive coverage of the new Directory Lookup feature:

### Key Documentation Files

1. **[features/directory-lookup.md](features/directory-lookup.md)**
   - Complete feature overview
   - Technical implementation details
   - API reference
   - Troubleshooting guide

2. **[user-guides/managing-directory.md](user-guides/managing-directory.md)**
   - Step-by-step user instructions
   - Best practices
   - Workflow optimization
   - Common use cases

3. **[getting-started/first-steps.md](getting-started/first-steps.md)**
   - Quick start with directory lookup
   - Initial setup guidance
   - Common first-time issues

### Documentation Features

- **Comprehensive Coverage**: All aspects of the Directory Lookup feature
- **User-Focused**: Clear instructions for end users
- **Technical Details**: Architecture and implementation information
- **Troubleshooting**: Common issues and solutions
- **Best Practices**: Workflow optimization and tips
- **API Reference**: Technical integration details

## Contributing to Documentation

### Writing Guidelines

1. **User-Centric**: Write from the user's perspective
2. **Clear Structure**: Use headings, lists, and tables effectively
3. **Code Examples**: Include practical examples where relevant
4. **Screenshots**: Add visuals for complex UI elements (when possible)
5. **Cross-References**: Link between related documentation sections

### File Organization

- Keep related content in appropriate directories
- Use descriptive filenames
- Maintain consistent naming conventions
- Update the navigation in `mkdocs.yml` when adding new files

### Content Standards

- **Accuracy**: Ensure all information is current and correct
- **Completeness**: Cover all aspects of features
- **Clarity**: Use simple, clear language
- **Examples**: Provide practical examples and use cases
- **Updates**: Keep documentation current with feature changes

## Deployment

### GitHub Pages (Example)

```bash
# Build and deploy to GitHub Pages
mkdocs gh-deploy
```

### Manual Deployment

```bash
# Build static files
mkdocs build

# Upload site/ directory to your web server
```

## Configuration

The documentation is configured in `mkdocs.yml`:

- **Theme**: Material Design theme
- **Plugins**: Search, minification
- **Extensions**: Code highlighting, admonitions, etc.
- **Navigation**: Organized by user journey

## Maintenance

### Regular Updates

- Review documentation quarterly
- Update screenshots when UI changes
- Add new features as they're developed
- Fix broken links and outdated information

### Version Control

- Keep documentation in sync with code changes
- Tag documentation versions with releases
- Maintain changelog for documentation updates
- Review PRs for documentation accuracy

## Support

For documentation issues:

1. Check existing issues in the repository
2. Review the MkDocs documentation for formatting
3. Test documentation changes locally before committing
4. Follow the established writing guidelines

## Links

- [MkDocs Documentation](https://www.mkdocs.org/)
- [Material Theme](https://squidfunk.github.io/mkdocs-material/)
- [Markdown Guide](https://www.markdownguide.org/)
- [Peppol Official Documentation](https://peppol.eu/documentation/)