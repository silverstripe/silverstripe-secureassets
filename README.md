# Secure Assets Module

[![Build Status](https://travis-ci.org/silverstripe-labs/silverstripe-secureassets.svg)](https://travis-ci.org/silverstripe-labs/silverstripe-secureassets)

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

 * SilverStripe 3.1+

## Installation Instructions

 1. Extract the module to your website directory, or install using
    composer: `composer require silverstripe/secureassets dev-master`
 2. Run /dev/build?flush=1

## Credit

This is a fairly heavy re-write of a community
module (also called secure files module) by
Hamish Campbell. Check that module out if you want 
fine-grained per member access control

https://github.com/hamishcampbell/silverstripe-securefiles.
