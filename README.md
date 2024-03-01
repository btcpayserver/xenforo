# BTCPay Payment Provider for XenForo 2
[![Maintained](https://img.shields.io/maintenance/yes/2024?style=flat-square)](https://github.com/021-projects/xf2-btcpay/pulse)
[![GitHub License](https://img.shields.io/github/license/021-projects/xf2-btcpay?color=brightgreen&style=flat-square)](https://github.com/021-projects/xf2-btcpay/blob/main/LICENSE)
[![GitHub contributors](https://img.shields.io/github/contributors-anon/021-projects/xf2-btcpay?style=flat-square)](https://github.com/021-projects/xf2-btcpay/graphs/contributors)

[![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/021-projects/xf2-btcpay?sort=semver&style=flat-square)](https://github.com/021-projects/xf2-btcpay/releases)
[![GitHub all releases](https://img.shields.io/github/downloads/021-projects/xf2-btcpay/total?style=flat-square)](https://github.com/021-projects/xf2-btcpay/releases)

## Requirements
- PHP 8.0+
- XenForo 2.2+
- [BTCPay Server](https://btcpayserver.org/) ([self-hosted](https://docs.btcpayserver.org/Deployment/) / [third party](https://docs.btcpayserver.org/Deployment/ThirdPartyHosting/))
- [Store On BTCPay Server](https://docs.btcpayserver.org/CreateStore/)
- [Connected Wallet on BTCPay Server](https://docs.btcpayserver.org/WalletSetup/)

### Notes
- The add-on does not support recurring payments
- The add-on does not support refunds
- The add-on does not support partial payments

## Installation
1. Download the add-on from the [releases page](https://github.com/021-projects/xf2-btcpay/releases)
2. Install the add-on via [control panel](https://xenforo.com/docs/xf2/add-ons/#control-panel-installation-21) or [manually](https://xenforo.com/docs/xf2/add-ons/#manual-installation)

## Configuration

### Payment Profile
1. Go to XenForo Admin Panel (/admin.php)
2. Click on [Setup] -> [Payment Profiles]
3. Click on [Add Payment Profile]
4. Choose "BTCPay Server" in the "Provider" dropdown
5. Click on "Proceed..."
6. In the field "Host", enter the full URL of your host (including the https) – https://btcpay.mydomain.com

### Create API Key
1. Go to your BTCPay Server
2. Click on [Account] -> Manage Account on the bottom left 
3. Go to the tab "API Keys"
4. Click [Generate Key] 
5. Check the following permissions:
   - Create an invoice (btcpay.store.cancreateinvoice)
   - View your stores (btcpay.store.canviewstoresettings)
6. Click on [Generate API Key]
7. Copy the generated API Key to your BTCPay Server payment profile settings form

### Setup Webhook
1. Go to your BTCPay Server
2. Click on [Settings]
3. Go to the tab "Webhooks"
4. Click [Create Webhook]
5. Enter the following URL, replacing "https://mydomain.com" with your forum URL, in the "Payload URL" field: https://mydomain.com/payment_callback.php?_xfProvider=btcPayServer
6. Below "Which events would you like to trigger this webhook?" choose "Send me specific events" and select "An invoice has been settled"
7. Click on the eye icon near the "Secret" field and copy the secret to your BTCPay Server payment profile settings form
8. Click on [Add Webhook]

Note: When testing a webhook, it may produce a 403 error - this is normal.

### Payment Profile (continued)

1. Go to your BTCPay Server
2. Click on [Settings]
3. Copy the "Store ID" to your BTCPay Server payment profile settings form
4. Click on [Save]

## Usage

You can use this integration wherever payment goes through XenForo payment profiles.

In most cases, a newly created payment profile requires activation in certain contexts.
Let's look at an example of activating a newly created profile for user upgrades:

1. Go to XenForo Admin Panel (/admin.php)
2. Click on [Setup] -> [Users] -> [User upgrades]
3. Click on the user upgrade you want to activate the payment profile for
4. Select the payment profile you created in the "Payment profile" select box
5. Click on [Save]
