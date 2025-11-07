# Cawl Online Payments

## Redirect Payment (Single payment buttons)

[![M2 Coding Standard](https://github.com/Worldline-Plugins/cawl-plugin-magento-redirect-payments/actions/workflows/coding-standard.yml/badge.svg?branch=develop)](https://github.com/Worldline-Plugins/cawl-plugin-magento-redirect-payments/actions/workflows/coding-standard.yml)
[![M2 Mess Detector](https://github.com/Worldline-Plugins/cawl-plugin-magento-redirect-payments/actions/workflows/mess-detector.yml/badge.svg?branch=develop)](https://github.com/Worldline-Plugins/cawl-plugin-magento-redirect-payments/actions/workflows/mess-detector.yml)

This module is an extension of the [hosted checkout](https://github.com/Worldline-Plugins/cawl-plugin-magento-hostedcheckout) Cawl payment solution.

This solution is also included into [main plugin for adobe commerce](https://github.com/Worldline-Plugins/cawl-plugin-magento).

### Change log:

#### 1.1.18
- Remove MealVouchers configuration from hosted checkout
- Fix mobile payment method information not being shown in order details

#### 1.1.17
- Fix print invoice issue
- Update payment brand logos

#### 1.1.16
- Allow order creation on amount discrepancies

#### 1.1.15
- Add quote ID to request payload
- Fix wrong IP address being sent on checkout
- Decrease maximum payment method logos
- Add compatibility with 2.4.8-p2

#### 1.1.14
- Fix issue with sending email

#### 1.1.13
- Fix wrong handling of payment specific information on order page

#### 1.1.12
- Fix comma separated email validation in notification settings

#### 1.1.11
- Fix issue with showing split payment amounts on order details page for Mealvoucher transactions
- Fix issue with showing Mealvoucher in full redirect

#### 1.1.10
- Fix logo issue for CB on checkout page
- Fix PHP >= 8.2 issue with not sending parameter by reference

#### 1.1.9
- Add Mealvoucher payment product
- Add CVCO (Cheque Vacances Connect Online) payment product

#### 1.1.8
- Add compatibility with PHP 8.4
- Update SDK version

#### 1.1.7
- Update the hosted-checkout CAWL module to version 1.1.7

#### 1.1.6
- Update the hosted-checkout CAWL module to version 1.1.6

#### 1.1.5
- Update plugin translations

#### 1.1.4
- Add 3DS exemption types to the plugin

#### 1.1.3
- Update the hosted-checkout CAWL module to version 1.1.3

#### 1.1.2
- Update the hosted-checkout CAWL module to version 1.1.2

#### 1.1.1
- Update the hosted-checkout CAWL module to version 1.1.1

#### 1.1.0
- Fixed validation for HTML template ID configuration. It is no longer required to have extension on HTML templates.
- Fixed issue where items quantities in decimals were not taken into account.
- Improved handling of orders where the total amount does not match the sum of line items amount due to the rounding.

#### 1.0.0
- New Redirect payments: integrate single payment buttons directly on Magento checkout.
