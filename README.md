[![WordPress tested 6.4](https://img.shields.io/badge/WordPress-v6.4%20tested-0073aa.svg)](https://wordpress.org/) [![PHPCS WPCS](https://img.shields.io/badge/PHPCS-WordPress%20Coding%20Standards❌-lightgrey.svg)](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards) [![PHPStan ](.github/phpstan.svg)](https://github.com/szepeviktor/phpstan-wordpress) [![PHPUnit ](https://img.shields.io/badge/PHPUnit-22%25-dc3545.svg)](https://brianhenryie.github.io/bh-wc-zelle-gateway/)

# Zelle Payment Gateway for WooCommerce

> [Download plugin zip](https://github.com/BrianHenryIE/bh-wc-zelle-gateway/releases)

`wp-admin/plugins.php`

![Plugin entry on plugins.php](./.github/screenshot-1.png)

`wp-admin/admin.php?page=wc-settings&tab=checkout&section=zelle`

![Plugin settings screen](./.github/screenshot-2.png)

`checkout/`

![Gateway at WooCommerce checkout](./.github/screenshot-3.png)

`checkout/order-received/123/`

![Thank You page with payment instructions](./.github/screenshot-4.png)

`wp-admin/admin.php?page=wc-orders&action=edit&id=123`

![Admin order UI with order awaiting payment](./.github/screenshot-5.png)


## Notes

```bash
wp option delete bh-wc-zelle-gateway-last-imap-reconcile-run-time;
wp cron event run bh_wc_zelle_gateway_check_for_payment_emails;
```

The billing address does not get saved to the shipping address if there is no shipping method available.

https://twitter.com/jdcmedlock/status/1606707029388972032

## TODO:

* Lots