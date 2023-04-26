# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## 1.0.5 – 2023-04-26
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
