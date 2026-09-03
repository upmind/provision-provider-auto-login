# [Upmind Provision Providers](https://github.com/upmind-automation) - Auto Login

[![Latest Version on Packagist](https://img.shields.io/packagist/v/upmind/provision-provider-auto-login.svg?style=flat-square)](https://packagist.org/packages/upmind/provision-provider-auto-login)

This provision category contains functions to facilitate basic online service account creation/management including an automatic login feature.

- [Installation](#installation)
- [Usage](#usage)
  - [Quick-start](#quick-start)
- [Supported Providers](#supported-providers)
- [Functions](#functions)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)
- [Upmind](#upmind)

## Installation

```bash
composer require upmind/provision-provider-auto-login
```

## Usage

This library makes use of [upmind/provision-provider-base](https://packagist.org/packages/upmind/provision-provider-base) primitives which we suggest you familiarize yourself with by reading the usage section in the README.

### Quick-start

The easiest way to see this provision category in action and to develop/test changes is to install it in [upmind/provision-workbench](https://github.com/upmind-automation/provision-workbench#readme).

Alternatively you can start using it for your business immediately with [Upmind.com](https://upmind.com/start) - the ultimate web hosting billing and management solution.

**If you wish to develop a new Provider, please refer to the [WORKFLOW](WORKFLOW.md) guide.**

## Supported Providers

The following providers are currently implemented:
  - Generic (a generic highly configurable provider)
  - [SpamExperts](https://api.antispamcloud.com/api/help.php)
  - [SafeWeb](https://www.safeweb.co/)
  - [Sitepro](https://site.pro/)

## Functions

| Function | Parameters | Return Data | Description |
|---|---|---|---|
| login() | [_AccountIdentifierParams_](src/Data/AccountIdentifierParams.php) | [_LoginResult_](src/Data/LoginResult.php) | Obtain a signed login URL for the service that the system client can redirect to |
| create() | [_CreateParams_](src/Data/CreateParams.php) | [_CreateResult_](src/Data/CreateResult.php) | Creates an account and returns the `username` which can be used to identify the account in subsequent requests, plus other account information |
| suspend() | [_AccountIdentifierParams_](src/Data/AccountIdentifierParams.php) | [_EmptyResult_](src/Data/EmptyResult.php) | Suspend an account |
| unsuspend() | [_AccountIdentifierParams_](src/Data/AccountIdentifierParams.php) | [_EmptyResult_](src/Data/EmptyResult.php) | Unsuspend an account |
| changePackage() | [_ChangePackageParams_](src/Data/ChangePackageParams.php) | [_ChangePackageResult_](src/Data/ChangePackageResult.php) | Change the package of an account |
| renew() | [_AccountIdentifierParams_](src/Data/AccountIdentifierParams.php) | [_EmptyResult_](src/Data/EmptyResult.php) | Renew an account's service |
| terminate() | [_AccountIdentifierParams_](src/Data/AccountIdentifierParams.php) | [_EmptyResult_](src/Data/EmptyResult.php) | Permanently delete an account |

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

 - [Harry Lewis](https://github.com/uphlewis)
 - [All Contributors](../../contributors)

## License

GNU General Public License version 3 (GPLv3). Please see [License File](LICENSE.md) for more information.

## Upmind

Sell, manage and support web hosting, domain names, ssl certificates, website builders and more with [Upmind.com](https://upmind.com/start) - the ultimate web hosting billing and management solution.
