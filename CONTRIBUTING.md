# Contributing

Thank you for your interest in contributing to this project.

This document describes how to set up the project, work with tasks, and submit changes.

---

## Requirements

* Docker installed and running
* Git installed
* Access to the repository

---

## Project Setup

This project runs fully in Docker.

To bootstrap the project:

* run `docker-deploy.sh`

This will:

* start Docker containers
* install Composer dependencies
* generate autoload files

---

## Docker Commands

The project provides helper scripts:

* `docker-compose-up.sh` — starts the Docker environment
* `docker-compose-down.sh` — stops and removes containers
* `docker-deploy.sh` — full project setup

---

## Local Development Workflow

Recommended workflow:

1. Start the environment
2. Work within the assigned task branch
3. Make changes
4. Run local checks
5. Ensure all checks pass
6. Submit a pull request

---

## Local Quality Checks

Before submitting a pull request, run:

* `docker-run-unit-tests.sh` — unit tests
* `docker-run-phpstan.sh` — static analysis (PHPStan)
* `docker-run-phpcs.sh` — code style checks (PHPCS)
* `docker-run-checks.sh` — all checks

---

## Pull Requests

All changes must be submitted via pull requests.

Before opening a PR:

* ensure all local checks pass
* ensure changes are covered by tests
* keep changes focused and related to a single task

Automated CI checks will run:

* PHPStan
* PHPCS
* PHPUnit
* test coverage report

---

## Code Quality Standards

This project enforces strict rules:

* all CI checks must pass
* test coverage must be **100%**
* code must follow PSR standards
* all business logic must be tested

---

## Branching & Task Workflow

All work is strictly tied to task tracking system identifiers.

---

### Task Format

All tasks follow the format:

* `AEGISORA-<task number>`

Example:

* `AEGISORA-123`

---

### Branch Naming Convention

Branch names are strictly equal to the task ID:

* `AEGISORA-<task number>`

Examples:

* `AEGISORA-123`
* `AEGISORA-456`

No prefixes, suffixes, or additional text are allowed.

Each branch corresponds exactly to one task.

---

### Branch Creation Policy

All branches are created by the maintainer only.

Developers do not create branches manually.

Workflow:

1. Developer requests a task (feature or bugfix)
2. Maintainer creates a task in the tracking system
3. Maintainer creates a corresponding branch
4. Developer works only within the assigned branch

---

### Commit Message Convention

All commits must start with the task identifier:

* `[AEGISORA-<task number>] <message>`

Examples:

* `[AEGISORA-123] add login endpoint`
* `[AEGISORA-456] fix null pointer exception`

---

### Rules

* every commit must reference a task
* no commits without a valid `AEGISORA-<task number>`
* no work outside assigned branch
* one branch = one task

---

## Rejection Criteria

A pull request will not be accepted if:

* CI checks fail
* test coverage is below 100%
* code style violations exist
* commits are not linked to a task
* branch name does not match `AEGISORA-<task number>`
* scope includes multiple unrelated changes

---

## Notes

If you are unsure about implementation details, open a discussion before starting work.

We appreciate clean, predictable, and well-tested contributions 🚀
