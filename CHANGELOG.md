# Changelog

All notable changes to the package will be documented in this file.

## [v5.1.8](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.8) - 2025-12-18

- Add Accept header to Generic provider requests

## [v5.1.7](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.7) - 2024-10-17

- Add optional `extra` array to CreateResult

## [v5.1.6](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.6) - 2024-10-01

- Add changePackage() function
- Add renew() function

## [v5.1.5](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.5) - 2024-09-19

- Update Generic provider UsernameResponseHandler to always cast return data to strings

## [v5.1.4](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.4) - 2024-07-31

- Update Generic provider
  - Add `skip_ssl_verification` configuration param
  - Add `extra_data_N` configuration params which get passed in requests as `configuration_extra.data_N`
  - Ensure `extra` params are always merged into request parameters

## [v5.1.3](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.3) - 2024-04-29

- Throw an explicit error if domain name is not passed as service identifier in SpamExperts create()

## [v5.1.2](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.2) - 2023-11-08

- Update ResponseHandlers/AbstractHandler::assertSuccess() to attempt to extract an error message

## [v5.1.1](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1.1) - 2023-11-08

- Fix Generic/Provider::login() return URL property

## [v5.1](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.1) - 2023-11-03

- Update AccountIdentifierParams add optional package_identifier

## [v5.0](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v5.0) - 2023-09-19

- Move Marketgoo provider to [upmind/provision-provider-seo](https://github.com/upmind-automation/provision-provider-seo)

## [v4.0](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v4.0) - 2023-09-13

- Refactor Category function parameter + return datasets
  - Delete LoginParams and TerminateParams in favour of AccountIdentifierParams with optional `extra` array
  - Replace usage of ResultData with EmptyResult
- Add `suspend()` and `unsuspend()` functions
- Add Example provider
- Add optional `customer_name` and `promo_codes` to CreateParams
- Add marketgoo provider

## [v3.0.3](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v3.0.3) - 2022-12-02

Add Category icon and SpamExperts Provider logo

## [v3.0.2](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v3.0.2) - 2022-12-02

Add optional HTTP request/response debug logging to Generic and SpamExperts providers

## [v3.0.1](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v3.0.1) - 2022-10-15

Enable compatibility with upmind/provision-provider-base v3

## [v3.0](https://github.com/upmind-automation/provision-provider-auto-login/releases/tag/v3.0) - 2022-04-29

Initial public release
