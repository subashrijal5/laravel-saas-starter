# Contributing

Thanks for your interest in contributing! Here's how to get started.

## Setup

```bash
git clone <repo> && cd saas-starter
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Development Workflow

1. Fork the repo and create a branch from `main`
2. Make your changes
3. Write or update tests for your changes
4. Run tests: `php artisan test --compact`
5. Format PHP: `vendor/bin/pint`
6. Submit a pull request

## Code Conventions

- **Action pattern** -- Business logic goes in `app/Actions/`, controllers stay thin
- **PHP** -- Follow PSR-12, use typed properties and return types, run Pint before committing
- **Frontend** -- TypeScript, functional React components, use existing shadcn/ui components
- **Tests** -- Pest 4 feature tests, use factories, aim for meaningful coverage
- **Naming** -- Descriptive names (`isRegisteredForDiscounts`, not `discount()`)
- **Comments** -- Only when the "why" isn't obvious from the code itself

## Pull Requests

- Keep PRs focused on a single change
- Include tests for new features or bug fixes
- Update documentation if your change affects usage
- Reference any related issues

## Reporting Issues

- Search existing issues before opening a new one
- Include steps to reproduce, expected vs actual behavior
- Mention your PHP, Node, and Laravel versions

## Code of Conduct

Be respectful and constructive. We're all here to build something useful.
