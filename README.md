# Secure Assets Module

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-secureassets.svg)](https://travis-ci.org/silverstripe/silverstripe-secureassets)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Introduction

A module for adding access restrictions to folders
that mirrors the access restrictions of sitetree pages

This is a fork of the community module (Also called Secure Files)
located at https://github.com/hamishcampbell/silverstripe-securefiles.

This should work with IIS 7+, but it has not been extensively tested.

See the [usage documentation](docs/en/index.md) for more information.

## Maintainer Contact

 * Hamish Friedlander `<hamish (at) silverstripe (dot) com>`
 * Sean Harvey `<sean (at) silverstripe (dot) com>`

## Requirements

 * SilverStripe [^3.1](https://getcomposer.org/doc/articles/versions.md#caret-version-range-)
 
 (3.1 up to but _not_ including 4)
 
 **NOTE:** Since SilverStripe 4.0.0 this module has been superseded by core functionality (provided by [`silverstripe/assets`](https://github.com/silverstripe/silverstripe-assets) - a part of [recipe-core](https://github.com/silverstripe/recipe-core)).

## Installation Instructions

 1. Extract the module to your website directory, or install using
    composer: `composer require silverstripe/secureassets dev-master`
 2. Run /dev/build?flush=1

## Upgrade Instructions
As above, this module is no longer needed in the SilverStripe 4.x release line. 

Run `dev/build` followed by `dev/tasks/MigrateFileTask`. All secure assets will be migrated to 
your updated project.

## Credit

This is a fairly heavy re-write of a community
module (also called secure files module) by
Hamish Campbell. Check that module out if you want 
fine-grained per member access control

https://github.com/hamishcampbell/silverstripe-securefiles.
