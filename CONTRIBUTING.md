# Contributing to InvoiceInk

Thank you for considering a contribution.

## Getting started

Fork the repository, clone your fork, and follow the setup in the
[README](README.md). Everything runs through Laravel Sail.

## Before opening a pull request

- Format your changes:

  ```bash
  sail pint --dirty && sail bun run lint && sail bun run format
  ```

- Run the tests:

  ```bash
  sail php artisan test --parallel
  sail bun run test
  ```

- Add or update tests for any change in behaviour.

## Commits and pull requests

Write commit messages as [Conventional Commits](https://www.conventionalcommits.org/).
Releases are generated from them: `feat:` bumps the minor version, `fix:` the
patch, and `feat!:` or a `BREAKING CHANGE:` footer the major.

Describe what changed and why in the pull request, and reference any issue it
closes (for example `Closes #12`).

## Code of conduct

By participating, you agree to follow the
[Contributor Covenant 2.1](https://www.contributor-covenant.org/version/2/1/code_of_conduct/).

## License

By contributing, you agree that your contributions are licensed under the
[MIT License](LICENSE).
