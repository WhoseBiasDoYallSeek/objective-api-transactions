SQLite support added

Usage:

- Create + seed DB locally:
  php scripts/seed_database.php

- Create + seed DB inside Docker (recommended):
  - docker compose build  # or `make build`
  - docker compose up -d  # or `make up`
  - docker compose run --rm slim php scripts/seed_database.php  # or `make seed`

- Run tests inside Docker:
  - docker compose run --rm slim vendor/bin/phpunit  # or `make test`

- Default DB location: `var/database.sqlite` (configurable in `app/settings.php`)

Tip (zsh): se você prefere usar o comando antigo `docker-compose`, adicione um alias em `~/.zshrc`:

  alias docker-compose='docker compose'

Depois rode `source ~/.zshrc` para aplicar o alias.

Notes:
- Tests for SQLite will be skipped if `pdo_sqlite` is not available in the PHP runtime.
- The Docker image installed for this project includes `pdo_sqlite` so tests and the seeder will work inside the container.