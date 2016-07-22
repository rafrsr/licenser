# Licenser

[![Build Status](https://travis-ci.org/rafrsr/licenser.svg?branch=master)](https://travis-ci.org/rafrsr/licenser)
[![Coverage Status](https://coveralls.io/repos/rafrsr/licenser/badge.svg?branch=master&service=github)](https://coveralls.io/github/rafrsr/licenser?branch=master)
[![Latest Stable Version](https://poser.pugx.org/rafrsr/licenser/version)](https://packagist.org/packages/rafrsr/licenser)
[![Latest Unstable Version](https://poser.pugx.org/rafrsr/licenser/v/unstable)](//packagist.org/packages/rafrsr/licenser)
[![Total Downloads](https://poser.pugx.org/rafrsr/licenser/downloads)](https://packagist.org/packages/rafrsr/licenser)
[![License](https://poser.pugx.org/rafrsr/licenser/license)](https://packagist.org/packages/rafrsr/licenser)

Automates the prepending of a license header doc block to your directory(ies) of source files.

- Accept a directory of source files or a path to a single source file to process
- Accept a file path containing your custom license doc block
- Can check your source files for the correct license information

### Install

1. [Install composer](https://getcomposer.org/download/)
2. Execute: `require rafrsr/licenser --dev`
3. Run `./bin/licenser /path/to/source/files /path/to/license/file` to process source files

### Using a built-in license type

Licenser supports the following built-in licenses and headers:

- The Apache 2.0 license (referred to as `apache2.0` in Licenser)
- The MIT license (referred to as `mit` in Licenser)
- Default - Is not a license, is a common header to add to any project (referred to as `default` in Licenser)
- Package - Similar to default but allow a package name (referred to as `package` in Licenser)

To use one of these built-in licenses you just replace the path to your custom licenses file with the name of the built-in license instead. 
For example if you wanted to use the MIT license then you would run something like:

````bash
./bin/licenser run /path/to/files mit
````

The `default` header is used when run something like this:
````bash
./bin/licenser run /path/to/files
````

### Value replacement in built-in licenses
When using a built-in license the Licenser will replace special placeholders with custom values. 
Placeholder can vary according to license:

| license  | placeholders  |   
|---|---|
| default  | author, package, version  |
| mit  | author  | 
| apache2.0  | author  | 
| symfony  | author, package  |

````bash
./bin/licenser /path/to/files -p author:"Author Name <email@example.com>" -p version:1.0
./bin/licenser /path/to/files mit -p author:"Author Name <email@example.com>"
./bin/licenser /path/to/files apache2.0 -p author:"Author Name <email@example.com>"
./bin/licenser /path/to/files symfony -p author:"Author Name <email@example.com>" -p package:MyPHPPackage
````
### Creating your custom license template

License template can be created using a simple text file. 
License templates are processed using [Twig](http://twig.sensiolabs.org/), then can use any twig feature.

##### e.g.
````
This file is part of the {{ package }}.

(c) {{ 'now'|date('Y') }}

@version {{ version }}

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
````

To process this license:
````bash
./bin/licenser /path/to/files /path/to/license -p package:MyPHPPackage -p version:1.0
````

> NOTE: parameters passed in the commandline can be used in the license template

### Checking files for correct license

Licenser also allows you to check your source files for correct license information.
It will warn you if there are any source files that do not have a license header that matches the options you provide.

````bash
./bin/licenser /path/to/files mit --only-check
````

By default the check only return if all files are ok or not, bu can use verbosity levels to view more details.

````bash
./bin/licenser /path/to/files mit --only-check -vv
````

> Verbosity levels are available in all actions

### Dry-run

Licenser also allows you to verify all available changes using a `dry-run`. Is a mix between normal process and `only-check`, 
verify all changes to see affected files before adding headers.

````bash
./bin/licenser /path/to/files mit --dry-run -vv
````

### YML configuration per project

Licenser support create a `.yml` file for each project with custom settings. 
Is helpful to use a global installation of Licenser and same commandline for all projects.

Create a yml file under your project directory

#### e.g:
````yml
#.licenser.yml
finder:
  in: 
    - src
    - tests
license: default
parameters:
   author: Rafael SR <https://github.com/rafrsr>
   package: Licenser
   version: '@Rafrsr\Licenser\Licenser::VERSION'
````

#### Usage
````bash
./bin/licenser --config=.licenser.yml
````

### YML Settings

- **finder:** used to create instance of Finder to locate source files
    - **in:** _[array, string]_ relative dir to find files
    - **name:** _[array, string]_ name of files to process (default: *.php)
- **license:**  _[string]_ name of build-in license to use or relative filename    
- **license_content:**  _[string]_ alternative to create your license inline without reference to any file
- **parameters:** _[array]_ array of parameters to pass to template  

> NOTE: can reference to a constant in parameters using `@` before the name of the constant, e.g: `@Rafrsr\Licenser\Licenser::VERSION`

### Caution

It is recommended that you have your source files under version control when running 
the tool as it is still experimental.

## Copyright

This project is licensed under the [MIT license](LICENSE).