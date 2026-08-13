# Entity Clone

This is a PHP package holding code assets to deal with database structure and the rules of package.

## What will you see here

The Doctrine bundle packages for database management, including migration, entities and repositories.

You may inherit the data from Doctrine packages to control migrations through parent project.

## Database dependency

Don't you forget that this project is heavily dependenty upon a real database.

Take a look to the `env.example` to be aware of what variables it is required to work.

## Start by a command

Although this package is designed to work *as a package*, it provides by its own a basic database management script. You can install the package with composer and take advantage of `bin/doctrine-migrations.php` script to make the first database manipulation. This file also will teach you the basics at how to call the scripts through the parent project, that is the ideal way to use this package.

