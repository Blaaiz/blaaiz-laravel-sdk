# Releasing

This SDK uses [release-please](https://github.com/googleapis/release-please) to automate releases. Nobody bumps the version, writes the changelog entry, or creates a tag by hand.

## 1. How a release works

1. Contributors merge pull requests with [Conventional Commit](https://www.conventionalcommits.org/) messages. A `feat:` commit starts a minor release. A `fix:` commit starts a patch release. A `feat!:` commit, or a commit with a `BREAKING CHANGE:` footer, starts a major release. Only `feat`, `fix`, `perf`, and `revert` commits start a release. Other types, such as `docs:`, `test:`, `chore:`, and `ci:`, do not. release-please ignores a commit message that is not a Conventional Commit.
2. On each push to `main`, release-please opens or updates a pull request titled `chore(release): release X.Y.Z`. This PR bumps every version string and adds an entry to the top of `CHANGELOG.md`.
3. A maintainer reviews the release PR. The maintainer makes sure that it changes only version strings and `CHANGELOG.md`, and that the latest CI run on `main` passed. Then the maintainer merges it.
4. The merge makes the workflow create tag `vX.Y.Z` and the matching GitHub Release.
5. The `notify-release` job runs in the same workflow run and confirms the version. Packagist reads the new tag through its GitHub webhook and publishes the package. No separate publish step runs for this SDK.

## 2. Rules

- Do not change the version by hand.
- Do not create a tag or a GitHub Release by hand.

## 3. Keep the SDKs on one version

All Blaaiz SDKs use the same version number for the same set of features. To set a specific version, add a `Release-As: X.Y.Z` footer to a commit on `main`.

## 4. Files that contain the version

- `src/BlaaizClient.php` — two `User-Agent` header literals
- `tests/Unit/BlaaizClientTest.php` — the assertion that checks the `User-Agent` header
- `CHANGELOG.md` — release-please prepends each new release here

release-please finds and updates these files through the `extra-files` list in `release-please-config.json`. `composer.json` has no `version` key, because Packagist reads the version from the Git tag. release-please does not add one, but it can rewrite the formatting of `composer.json`.

## 5. About the release PR

GitHub does not run CI on a pull request that `GITHUB_TOKEN` opens, so the release PR shows no checks. The PR changes only version strings and `CHANGELOG.md`. After a maintainer merges it, CI runs on `main` as normal, and the `notify-release` job waits for the test job to pass first.

## 6. Required setup

- In **Settings > Actions > General > Workflow permissions**, select **Allow GitHub Actions to create and approve pull requests**. release-please needs this permission to open the release PR.
- The `notify-release` job uses no repository secrets. Packagist picks up each new release through its GitHub webhook, so the workflow does not need a Packagist token.

## 7. If a job fails after the merge

Packagist reads the new tag as soon as release-please creates it. The tests on `main` do not hold it back. If the tagged code is broken, merge a fix. release-please then prepares the next patch release.

## 8. Manual fallback

Publishing a GitHub Release by hand for an existing tag still runs the `notify-release` job, through the workflow's `release: published` trigger.
