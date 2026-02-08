# MageMonk SearchDesign

## Overview
`MageMonk_SearchDesign` customizes the storefront mini-search autocomplete with:
- Suggestions list
- Matched categories
- Recommended products
- Quick add-to-cart from dropdown

The module supports Magento fulltext search flow and follows the configured search engine.

## Installation
1. Place module under `app/code/MageMonk/SearchDesign`.
2. Run:
   - `bin/magento setup:upgrade`
   - `bin/magento cache:flush`
3. In production mode also run:
   - `bin/magento setup:di:compile`
   - `bin/magento setup:static-content:deploy -f`

## Configuration
Admin path:
- `Stores > Configuration > MageMonk > Search Design`

Config key:
- `searchdesign/general/enabled`

## Compatibility Notes
- Uses Magento quick search UI.
- Product matching uses catalog fulltext collection, respecting engine-backed search behavior.

## Uninstall (manual)
1. Disable module:
   - `bin/magento module:disable MageMonk_SearchDesign`
2. Remove code directory:
   - `app/code/MageMonk/SearchDesign`
3. Run:
   - `bin/magento setup:upgrade`
   - `bin/magento cache:flush`
