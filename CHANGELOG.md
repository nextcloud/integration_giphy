<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: CC0-1.0
-->
# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## 2.0.1 - 2024-10-18

### Fixed

- Crash when decrypting empty strings

## 2.0.0 - 2024-10-16

### Changed

- SPDX headers for licencing
- switched to Vite
- update npm pkgs

### Fixed

- removed default API key
- encryption of stored api key, encrypting only non-empty API key
- added password confirmation for sensitive value

## 1.0.11 - 2024-07-24

### Changed

- update npm pkgs
- added NC 30 support

## 1.0.10 - 2023-11-30

### Fixed

- avoid breaking the style with nc/vue v7

## 1.0.9 - 2023-11-21

### Changed

- update npm pkgs

### Fixed

- fix picker modal height for 28

## 1.0.8 - 2023-08-21

### Added

- Add button to disable all GIFs in the current context [#15](https://github.com/nextcloud/integration_giphy/pull/15) @kyteinsky

### Fixed

- Fix casing of 'GIF' in translated text [#13](https://github.com/nextcloud/integration_giphy/pull/13) @rakekniven

## 1.0.7 - 2023-07-06
### Added
- Allow users to avoid Giphy GIF links to be resolved and rendered by the Giphy integration [#9](https://github.com/nextcloud/integration_giphy/pull/9) @julien-nc
- Add user setting to toggle unified search (opt-in) [#12](https://github.com/nextcloud/integration_giphy/pull/12) @julien-nc

## 1.0.6 – 2023-04-26
### Fixed
- keep CSRF check for image endpoint and fix CSRF in the widget
- allow the picker to use the search provider even when it's disabled in the unified search menu
- show error in the widget if the GIF was not found
- use latest webpack config solving the nc/vue import bug
- make gif direct url parsing more tolerant

## 1.0.3 – 2023-04-06
### Added
- admin option to choose the rating filter used when searching and getting trending GIFs

## 1.0.2 – 2023-03-08
### Changed
- lazy load reference scripts

### Fixed
- fix potential redundant v-for keys because giphy gives duplicate results

## 1.0.0 – 2022-12-19
### Added
* the app
