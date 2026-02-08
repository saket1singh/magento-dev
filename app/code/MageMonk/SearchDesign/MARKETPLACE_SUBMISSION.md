# Marketplace Submission Pack

## Extension Name
MageMonk SearchDesign

## Module Name
`MageMonk_SearchDesign`

## Short Description
Custom mini-search autocomplete experience for Magento storefront with suggestions, category matches, recommended products, and quick add-to-cart.

## Long Description
MageMonk SearchDesign upgrades the default mini-search experience with a structured dropdown that improves product discovery and conversion from the header search box.

Key capabilities:
- Search suggestions list
- Matching product categories
- Recommended product preview with image/SKU
- Add to Cart directly from autocomplete
- Shop All shortcut to full result page
- Admin enable/disable toggle

The module is built to follow Magento search behavior and works with engine-backed fulltext search flow configured in the store.

## Features
- Storefront custom autocomplete dropdown
- Add to Cart from dropdown with proper form key handling
- Safe redirect handling for add-to-cart (`uenc` based on product URL)
- Configurable enable/disable in admin:
  - `Stores > Configuration > MageMonk > Search Design`
  - config path: `searchdesign/general/enabled`
- Responsive styling for desktop/mobile

## Compatibility
- Adobe Commerce / Magento Open Source 2.4.x
- PHP 8.1+
- Supports Magento search engine-backed fulltext behavior (as configured in store)

## Installation
1. Place module in `app/code/MageMonk/SearchDesign`
2. Run:
   - `bin/magento setup:upgrade`
   - `bin/magento cache:flush`
3. Production mode:
   - `bin/magento setup:di:compile`
   - `bin/magento setup:static-content:deploy -f`

## Uninstall
1. `bin/magento module:disable MageMonk_SearchDesign`
2. Remove `app/code/MageMonk/SearchDesign`
3. `bin/magento setup:upgrade`
4. `bin/magento cache:flush`

## Security / Stability Notes
- Avoids nested form issues in autocomplete add-to-cart flow.
- Uses CSRF form key in add-to-cart request.
- Handles disabled module state cleanly.

## Tests & Quality Evidence
- PHPUnit:
  - `Test/Unit/Controller/Ajax/SuggestTest.php`
  - `Test/Unit/Model/SuggestDataProviderTest.php`
  - `Test/Unit/Model/SuggestionProviderTest.php`
  - `Test/Unit/Model/CategoryProviderTest.php`
  - `Test/Unit/Model/ProductProviderTest.php`
- MFTF smoke test:
  - `Test/Mftf/Test/SearchDesignAutocompleteAddToCartSmokeTest.xml`
  - `Test/Mftf/ActionGroup/StorefrontAddToCartFromSearchDesignAutocompleteActionGroup.xml`
- Coding standard:
  - `vendor/bin/phpcs --standard=Magento2 app/code/MageMonk/SearchDesign`

## Marketplace Assets Checklist
- [ ] Extension icon (512x512 PNG)
- [ ] Store listing tile image
- [ ] 4-8 storefront/admin screenshots
- [ ] Release notes/changelog (`CHANGELOG.md`)
- [ ] User guide (installation + configuration + usage)
- [ ] Support contact and SLA details

## Suggested Screenshot Set
1. Default storefront mini-search
2. Custom autocomplete open (suggestions + categories + products)
3. Add to Cart from dropdown
4. Admin config page (`MageMonk > Search Design`)
5. Disabled mode showing default Magento behavior

## Support
- Vendor: MageMonk
- Module: MageMonk SearchDesign
