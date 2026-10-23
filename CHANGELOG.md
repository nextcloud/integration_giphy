<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: CC0-1.0
-->
# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Removed

- Nextcloud 32 and PHP 8.1 support

## 2.3.0 - 2026-10-02

### Added

- Added support for Nextcloud 36
- Start GIF previews paused when the browser asks for reduced motion

### Changed

- Update @nextcloud/eslint-config to 9 and sync the lint workflows
- Update dependencies & translations

### Fixed

- Show the text of the GIF picker again while it searches

## 2.2.2 - 2026-07-31

### Added

- Added support for Nextcloud 35

### Changed

- Update dependencies & translations

### Fixed

- Keep the admin settings page working when the stored API key cannot be decrypted

## 2.2.1 - 2026-04-20

### Added

- Added support for Nextcloud 34

### Changed

- Update dependencies & translations

## 2.2.0 - 2025-11-13

### Added

- Added support for Nextcloud 33

### Changed

- Migrate to Vue 3 and @nextcloud/vue 9
- Use the new IExternalProvider in the search provider
- Update dependencies & translations

### Removed

- Nextcloud 30 and 31 support

## 2.1.0 - 2025-07-10

### Added

- Added support for Nextcloud 32

### Removed

- Nextcloud 28 and 29 support

## 2.0.2 - 2025-05-21

### Changed

- README and info.xml adjustments

### Fixed

- Use the product name "Smart Picker"
- Make getGifProxiedUrl safer

## 2.0.1 - 2024-10-18

### Fixed

- Crash when decrypting empty strings

## 2.0.0 - 2024-10-16

### Added

- Added support for Nextcloud 31

### Changed

- SPDX headers for licencing
- Switch to Vite
- Update dependencies

### Fixed

- Remove the default API key
- Encryption of the stored API key, encrypting only a non-empty API key
- Add password confirmation for the sensitive value

## 1.0.11 - 2024-07-24

### Added

- Added support for Nextcloud 30

### Changed

- Update dependencies

## 1.0.10 - 2023-11-30

### Added

- Added support for Nextcloud 29

### Fixed

- Avoid breaking the style with @nextcloud/vue v7

## 1.0.9 - 2023-11-21

### Changed

- Update dependencies

### Removed

- Nextcloud 27 support

### Fixed

- Fix the picker modal height for Nextcloud 28

## 1.0.8 - 2023-08-21

### Added

- Add a button to disable all GIFs in the current context

### Fixed

- Fix the casing of "GIF" in translated text

## 1.0.7 - 2023-07-06

### Added

- Added support for Nextcloud 28
- Allow users to avoid Giphy GIF links to be resolved and rendered by the Giphy integration
- Add a user setting to toggle unified search (opt-in)

### Removed

- Nextcloud 26 support

## 1.0.6 - 2023-04-26

### Fixed

- Keep the CSRF check for the image endpoint and fix CSRF in the widget
- Allow the picker to use the search provider even when it's disabled in the unified search menu
- Show an error in the widget if the GIF was not found
- Use the latest webpack config solving the @nextcloud/vue import bug
- Make GIF direct URL parsing more tolerant

## 1.0.3 - 2023-04-06

### Added

- Admin option to choose the rating filter used when searching and getting trending GIFs

## 1.0.2 - 2023-03-08

### Added

- Added support for Nextcloud 27

### Changed

- Lazy load reference scripts

### Fixed

- Fix potential redundant v-for keys because Giphy gives duplicate results

## 1.0.0 - 2022-12-19

### Added

- Initial release for Nextcloud 26
