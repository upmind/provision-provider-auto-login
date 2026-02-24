<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\Generic\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read string|null $access_token Access bearer token to send in requests
 * @property-read string $login_endpoint_url Endpoint which generates login URLs
 * @property-read string|null $login_endpoint_http_method HTTP method to use for the login endpoint
 * @property-read boolean|null $has_create Whether or not this configuration has a create endpoint
 * @property-read string|null $create_endpoint_url Endpoint which creates a service account and returns a username
 * @property-read string|null $create_endpoint_http_method HTTP method to use for the create endpoint
 * @property-read boolean|null $has_suspend Whether or not this configuration has suspend + unsuspend endpoints
 * @property-read string|null $suspend_endpoint_url Endpoint which suspends a service account
 * @property-read string|null $suspend_endpoint_http_method HTTP method to use for the suspend endpoint
 * @property-read string|null $unsuspend_endpoint_url Endpoint which unsuspends a service account
 * @property-read string|null $unsuspend_endpoint_http_method HTTP method to use for the unsuspend endpoint
 * @property-read boolean|null $has_change_package Whether or not this configuration has a change package endpoint
 * @property-read string|null $change_package_endpoint_url Endpoint which changes the package of a service account
 * @property-read string|null $change_package_endpoint_http_method HTTP method to use for the change package endpoint
 * @property-read boolean|null $has_renew Whether or not this configuration has a renew endpoint
 * @property-read string|null $renew_endpoint_url Endpoint which renews a service account
 * @property-read string|null $renew_endpoint_http_method HTTP method to use for the renew endpoint
 * @property-read boolean|null $has_terminate Whether or not this configuration has a terminate endpoint
 * @property-read string|null $terminate_endpoint_url Endpoint which terminates a service account
 * @property-read string|null $terminate_endpoint_http_method HTTP method to use for the terminate endpoint
 * @property-read boolean|null $skip_ssl_verification Whether or not to allow invalid SSL certificates
 * @property-read string|null $extra_data_1 Extra data field 1
 * @property-read string|null $extra_data_2 Extra data field 2
 * @property-read string|null $extra_data_3 Extra data field 3
 * @property-read string|null $extra_secret_1 Extra secret field 1
 * @property-read string|null $extra_secret_2 Extra secret field 2
 * @property-read string|null $extra_secret_3 Extra secret field 3
 * @property-read bool|null $debug Whether or not to log all HTTP requests and responses
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'access_token' => ['nullable', 'string'],
            'login_endpoint_http_method' => ['required', 'string', 'in:post,put,patch,get'],
            'login_endpoint_url' => ['required', 'url'],
            'has_create' => ['boolean'],
            'create_endpoint_http_method' => ['required_if:has_create,1', 'string', 'in:post,put,patch,get'],
            'create_endpoint_url' => ['required_if:has_create,1', 'url'],
            'has_suspend' => ['boolean'],
            'suspend_endpoint_http_method' => ['required_if:has_suspend,1', 'string', 'in:post,put,patch,get,delete'],
            'suspend_endpoint_url' => ['required_if:has_suspend,1', 'url'],
            'unsuspend_endpoint_http_method' => ['required_if:has_suspend,1', 'string', 'in:post,put,patch,get,delete'],
            'unsuspend_endpoint_url' => ['required_if:has_suspend,1', 'url'],
            'has_change_package' => ['boolean'],
            'change_package_endpoint_http_method' => ['required_if:has_change_package,1', 'string', 'in:post,put,patch,get,delete'],
            'change_package_endpoint_url' => ['required_if:has_change_package,1', 'url'],
            'has_renew' => ['boolean'],
            'renew_endpoint_http_method' => ['required_if:has_renew,1', 'string', 'in:post,put,patch,get,delete'],
            'renew_endpoint_url' => ['required_if:has_renew,1', 'url'],
            'has_terminate' => ['boolean'],
            'terminate_endpoint_http_method' => ['required_if:has_terminate,1', 'string', 'in:post,put,patch,get,delete'],
            'terminate_endpoint_url' => ['required_if:has_terminate,1', 'url'],
            'skip_ssl_verification' => ['boolean'],
            'extra_data_1' => ['nullable', 'string'],
            'extra_data_2' => ['nullable', 'string'],
            'extra_data_3' => ['nullable', 'string'],
            'extra_secret_1' => ['nullable', 'string'],
            'extra_secret_2' => ['nullable', 'string'],
            'extra_secret_3' => ['nullable', 'string'],
            'debug' => ['boolean'],
        ]);
    }
}
