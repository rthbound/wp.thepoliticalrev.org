# [wp.thepoliticalrev.org](https://github.com/politicalrev/wp.thepoliticalrev.org)

Welcome to the Github repository for The Political Revolution's Wordpress setup. It is built upon the [Sage 8.5](https://github.com/roots/sage/releases/latest) starter theme and sports a modern development workflow.

This file describes the steps to install this Wordpress repo and setup all the components on the same ([Debian](https://www.debian.org/) or [Ubuntu](https://www.ubuntu.com/)) machine. The steps should be largely similar for using [MAMP](https://www.mamp.info/en/) or [homebrew](http://brew.sh/).


## Features

* Pre-hardened security
* Audit log of all user actions
* Database-driven code snippets
* Federated OAuth login/registration
* Cloudflare API integration
* [gulp](http://gulpjs.com/) build script that compiles both Sass and Less, checks for JavaScript errors, optimizes images, and concatenates and minifies files
* [BrowserSync](http://www.browsersync.io/) for keeping multiple browsers and devices synchronized while testing, along with injecting updated CSS and JS into your browser while you're developing
* [Bower](http://bower.io/) for front-end package management
* [asset-builder](https://github.com/austinpray/asset-builder) for the JSON file based asset pipeline
* [Bootstrap](http://getbootstrap.com/)
* [Theme wrapper](https://roots.io/sage/docs/theme-wrapper/)
* ARIA roles and microformats
* Posts use the [hNews](http://microformats.org/wiki/hnews) microformat
* [Multilingual ready](https://roots.io/wpml/) and over 30 available [community translations](https://github.com/roots/sage-translations)
* Cleaner output of `wp_head` and enqueued assets
* Cleaner HTML output of navigation menus
* Root relative URLs
* Nice search (`/search/query/`)
* Google CDN jQuery snippet from [HTML5 Boilerplate](http://html5boilerplate.com/)
* Google Analytics snippet from [HTML5 Boilerplate](http://html5boilerplate.com/)


## Requirements

Make sure all dependencies have been installed before moving on:

* [PHP](http://php.net/manual/en/install.php) >= 7.x
* [Composer](https://getcomposer.org/download/) >= 1.1
* [Node.js](http://nodejs.org/) >= 4.5
* [gulp](http://gulpjs.com/) >= 3.8.10
* [Bower](http://bower.io/) >= 1.3.12


## Installation

Installation is straight foward.

1. Clone the repository.
2. Set the root/webroot to the repository folder.
3. Navigate to http://localhost:8080/ and follow the instructions.

A pre-populated database is not currently being provided.


## Theme setup

Edit `lib/setup.php` to enable or disable theme features, setup navigation menus, post thumbnail sizes, post formats, and sidebars.


## Theme development

Sage uses [gulp](http://gulpjs.com/) as its build system and [Bower](http://bower.io/) to manage front-end packages.


### Install gulp and Bower

Building the theme requires [node.js](http://nodejs.org/download/). We recommend you update to the latest version of npm: `npm install -g npm@latest`.

From the command line:

1. Install [gulp](http://gulpjs.com) and [Bower](http://bower.io/) globally with `npm install -g gulp bower`
2. Navigate to the theme directory, then run `npm install`
3. Run `bower install`

You now have all the necessary dependencies to run the build process.


### Available gulp commands

* `gulp` — Compile and optimize the files in your assets directory
* `gulp watch` — Compile assets when file changes are made
* `gulp --production` — Compile assets for production (no source maps).


### Using BrowserSync

To use BrowserSync during `gulp watch` you need to update `devUrl` at the bottom of `assets/manifest.json` to reflect your local development hostname.

For example, if your local development URL is `http://project-name.dev` you would update the file to read:
```json
...
  "config": {
    "devUrl": "http://project-name.dev"
  }
...
```
If your local development URL looks like `http://localhost:8888/project-name/` you would update the file to read:
```json
...
  "config": {
    "devUrl": "http://localhost:8888/project-name/"
  }
...
```


## Documentation

Detailed documentation for this project will is located in the [docs/](https://github.com/politicalrev/wp.thepoliticalrev.org/tree/master/docs) folder.

Sage 8.5 documentation is available at [https://roots.io/sage/docs/](https://roots.io/sage/docs/).


## Contributing

Contributions are welcome from everyone. We have [contributing guidelines](https://github.com/roots/guidelines/blob/master/CONTRIBUTING.md) to help you get started.
