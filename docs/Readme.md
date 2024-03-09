# Documentation

## Auto generated documentation

### User manual via Sphinx

[![Documentation Status](https://readthedocs.org/projects/php-library-template/badge/?version=latest)](https://php-library-template.readthedocs.io/en/latest/?badge=latest)

The user manual can be auto generated with [Sphinx](https://www.sphinx-doc.org) from the markdown sources contained in this directory, in order to upload it to [Read the Docs](https://readthedocs.org).
The online documentation can be found at [php-library-template.readthedocs.io](https://php-library-template.readthedocs.io/en/main/) ([project page](https://readthedocs.org/projects/php-library-template/)), a local copy is built during CI and stored in the [`readthedocs` branch](https://github.com/chillerlan/php-library-template-core/tree/readthedocs).


#### Run Sphinx locally

Requirements:

- [Python](https://www.python.org/downloads/) >= v3.10
  - [Sphinx](https://www.sphinx-doc.org/en/master/usage/installation.html) >= v6.0
  - [Sphinx RTD theme](https://pypi.org/project/sphinx-rtd-theme/) >= 1.2
  - [MyST Parser](https://myst-parser.readthedocs.io/en/latest/intro.html) >= 2.0 (see [Sphinx Markdown configuration](https://www.sphinx-doc.org/en/master/usage/markdown.html#markdown))

Follow these steps to create the user manual:

- install Sphinx: `pip install sphinx myst-parser sphinx-rtd-theme`
- run in the `/docs` (this) directory:
  - on Windows: `.\make.bat html` (make sure `sphinx-build.exe` is in `PATH`)
  - on Linux: `make html`
- open [../.build/sphinx/html/index.html](../.build/sphinx/html/index.html) in a browser
- yay!


### API docs via phpDocumentor

[![pages-build-deployment](https://github.com/chillerlan/php-library-template/actions/workflows/pages/pages-build-deployment/badge.svg?branch=gh-pages)](https://github.com/chillerlan/php-library-template/actions/workflows/pages/pages-build-deployment)

The API documentation can be auto generated with [phpDocumentor](https://www.phpdoc.org/).
There is an [online version available](https://chillerlan.github.io/php-library-template/) via the [`gh-pages` branch](https://github.com/chillerlan/php-library-template/tree/gh-pages)
that is [automatically deployed](https://github.com/chillerlan/php-library-template/deployments) on each push to main.


#### Run phpDocumentor locally

If you'd like to create local docs, please follow these steps:

- [download phpDocumentor](https://github.com/phpDocumentor/phpDocumentor/releases) v3+ as .phar archive
- run it in the repository root directory:
  - on Windows `c:\path\to\php.exe c:\path\to\phpDocumentor.phar --config=phpdoc.xml`
  - on Linux just `php /path/to/phpDocumentor.phar --config=phpdoc.xml`
- open [../.build/phpdocs/index.html](../.build/phpdocs/index.html) in a browser
- profit!


## Sources

The markdown sources for the [Read the Docs online manual](https://php-library-template.readthedocs.io) can of course be browsed on GitHub too!


### Usage

- [Overview](./Usage/Overview.md)
- [Installation](./Usage/Installation.md)
- [Quickstart](./Usage/Quickstart.md)


### Appendix

- [How to contribute](./Appendix/Contribute.md)
- [License](./Appendix/License.rst)


## License

The documentation is licensed under the [Creative Commons Attribution 4.0 International (CC BY 4.0) License](https://creativecommons.org/licenses/by/4.0/).
