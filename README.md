# Secure Assets Module

## Introduction

A module for adding access restrictions to folders
that mirrors the access restrictions of sitetree pages

This is a fork of the community module (Also called Secure Files)
located at https://github.com/hamishcampbell/silverstripe-securefiles.

This should work with IIS 7+, but it has not been extensively tested.

## Maintainer Contact

 * Hamish Friedlander `<hamish (at) silverstripe (dot) com>`
 * Sean Harvey `<sean (at) silverstripe (dot) com>`

## Requirements

 * SilverStripe 3.1+

## Installation Instructions

 1. Extract the module to your website directory.
 2. Run /dev/build?flush=1

## Usage Overview

Adds access fields to the edit view of a Folder in the Files CMS
section.

Securing files will cause extra load on your
webserver and your database, as the framework will check
the datatabase for access permissions, and pass the
file data through the framework when it is output to the user.

## Credit

This is a fairly heavy re-write of a community
module (also called secure files module) by
Hamish Campbell. Check that module out if you want 
fine-grained per member access control

https://github.com/hamishcampbell/silverstripe-securefiles.
